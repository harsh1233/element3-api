<?php

namespace App\Http\Controllers;

use Excel;
use Response;
use SimpleXMLElement;
use App\Models\Contact;
use App\Models\Country;
use App\Models\EldaDetail;
use Illuminate\Http\Request;
use App\Models\EldaFunctions;
use App\Models\ContactAddress;
use App\Models\ContactBankDetail;
use App\Exports\EldaDetailsExport;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\EldaFTPProcoessDetails;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;

trait EldaFuctions
{

    // This function takes defalut value, length and type of field from array and return result based on given type.
    // This function can be used in generating lines
     public function uploadEldaFile($directory, $name, $file)
    {
        $url = $directory . "/" . $name;
        Storage::disk('s3')->put($url, file_get_contents($file));
        $public_url = Storage::disk('s3')->url($url);
        return $public_url;
    }
    
    public function EldaGenerateLineUsingField($value, $totallength, $type)
    {
        //Length OF Message
        $lengthofvalue = strlen($value);

        //Remaining Length from  Message
        $remaininglength =  $totallength - $lengthofvalue;

        if ($type == 'n') {
            $final =  str_pad($value, $totallength, 0); //Append 0 of totallength behind value
        }

        if ($type == 'an') {
            $generate = str_repeat("&nbsp;", $remaininglength);  //Generate blank space of remaininglength
            $final =  $value . $generate;  //Append Value and Generated blank space
        }

        if ($type == 'a') {
            $generate = str_repeat("&nbsp;", $remaininglength);  //Generate blank space of remaininglength
            $final = $generate . $value; //Append Value and Generated blank space
        }

        return $final;
    }

    //This function is used to generate Identification part of first 20 word
    //This function takes first 2 word and line number
    public function GenerateIdentificationPart($first2word, $linenumber)
    {
        $abc =  str_pad($linenumber, 7, '0', STR_PAD_LEFT); //Append 0 six times before line number
        $abc =  str_pad($abc, 9, $first2word, STR_PAD_LEFT); //Append first two word before generated string
        return str_pad($abc, 20, 'ED915749918'); // append ED915749918 behind generated string and return 20 word
    }

    //This function is used to Generate Reference Value of 40 word
    //This function taked value of code
    // public function GenerateReferenceValue($code)
    // {
    //     $abc =  str_pad($code, 4, 'EC', STR_PAD_LEFT); //Append EC before code and return 4 word
    //     $abc =  str_pad($abc, 5, '-'); //Append - behind generated string and return 5 word
    //     $abc =  str_pad($abc, 23, substr(str_shuffle("0123456789"), 0, 18)); //Append random number  behind generated string and return 23 word
    //     return  $abc; // return  generated Value

    // }

    public function GenerateReferenceValue($id)
    {
        $elda = EldaDetail::where('contact_id', $id)->latest()->first();
        if ($elda['elda_insurance_reference_number']) {
            return $elda['elda_insurance_reference_number'];
        } else {
            $a = "";
            for ($i = 0; $i < 18; $i++) {
                $a .= mt_rand(0, 9);
            }
            $eldareefe = EldaDetail::where('contact_id', $id)
                ->update([
                    'elda_insurance_reference_number' => $a
                ]);
            return $a;
        }
    }

    //This function generate first line in regisration
    public function EldaRegistrationGenerateFirstLine()
    {
        $firtchar =   $this->EldaGenerateLineUsingField('', 2, 'an'); //generate 2 blank space
        $generatefield =  $this->EldaGenerateLineUsingField('windows-1252', 50, 'an'); //geerate  windows-1252 and rest of word from 50 word remains blank space and return 50 word string
        return $firtchar . "01" . $generatefield . "<br>"; //append blank space with 01 and windows-1252
    }

    //This function generate second line in regisration
    public function EldaRegistrationGenerateSecondLine($id)
    {
        $elda_detail = EldaDetail::where('contact_id', $id)->where('status', 'register')->with('contact_detail')
            ->latest()->first();
        $creation_date = "";
        $creation_time = "";
        if ($elda_detail['date']) {
            $creation_date =  date("dmY", strtotime($elda_detail['date']));
        }

        if ($elda_detail['created_at']) {
            $creation_time =  date("His", strtotime($elda_detail['created_at']));
        }

        $second_line = config('eldaconstants.elda_generate_registration_second_line'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart(00, 1);
            }
            if ($value['name'] == "creation_date") {
                $value['default_value'] =    $creation_date;
            }
            if ($value['name'] == "creation_time") {
                $value['default_value'] =    $creation_time;
            }
            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        $generate = str_repeat("&nbsp;", 373);  //Generate blank space of remaininglength

        return $result_response . $generate . "<br>";
    }

    //This function  is for third line in Registration of gkk file
    public   function EldaRegistrationGenerateThirdine($id)
    {
        $account_no1 =  ContactBankDetail::select('account_no', 'iban_no')->where('contact_id', $id)->get();
        $account_no = "";
        foreach ($account_no1 as $elda) {
            if ($elda['account_no']) {
                $account_no = $elda['account_no'];
            }
        }

        $elda = EldaDetail::where('contact_id', $id)->where('status', 'register')->with('contact_detail')
            ->latest()->first();

        $first_name = "";
        $lastname = "";
        $email = "";
        $employer_phone_number = "";
        $creation_date = "";
        $elda_insurance_number = "";
        $dob = "";
        $free_service_contract = "";

        if ($elda['contact_detail']['first_name']) {
            $first_name = $elda['contact_detail']['first_name'];
        }
        if ($elda['contact_detail']['last_name']) {
            $lastname = $elda['contact_detail']['last_name'];
        }
        if ($elda['contact_detail']['email']) {
            $email =  $elda['contact_detail']['email'];
        }
        if ($elda['contact_detail']['mobile1']) {
            $employer_phone_number =  $elda['contact_detail']['mobile1'];
        }
        if ($elda['date']) {
            $creation_date =  date("dmY", strtotime($elda['date']));
        }
        if ($elda['elda_insurance_number']) {
            $elda_insurance_number = $elda['elda_insurance_number'];
        }
        if ($elda['contact_detail']['dob']) {
            $dob =  date("dmY", strtotime($elda['contact_detail']['dob']));
        }

        if ($elda['is_free_service_contract'] == 0) {
            $free_service_contract = "N";
        } else {
            $free_service_contract = "Y";
        }
        $elda_insurance_reference_number = $elda['elda_insurance_reference_number'];


        $second_line = config('eldaconstants.elda_generate_registration_third_line'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart("M3", 2);
            }
            // if ($value['name'] == "reference_value") {
            //     $value['default_value'] =  "ECVR-" . $elda_insurance_reference_number;
            // }

            if ($value['name'] == "reference_value") {

                $value['default_value'] = "ECVR-" .  $this->GenerateReferenceValue($id);
            }


            // if ($value['name'] == "contribution_account_number") {
            //     $value['default_value'] =   $account_no;
            // }
            if ($value['name'] == "employer_name") {
                $value['default_value'] =  $first_name . $lastname;
            }
            if ($value['name'] == "On/off_date_and_change_date") {
                $value['default_value'] =  $creation_date;
            }

            if ($value['name'] == "employer_phone_number") {
                $value['default_value'] =   $employer_phone_number;
            }
            if ($value['name'] == "Employer_email_address") {
                $value['default_value'] =  $email;
            }

            if ($value['name'] == "Reference_value_of_the_VSNR_requirement") {
                if ($elda_insurance_number == "") {

                    $value['default_value'] =  "ECVS-" .  $this->GenerateReferenceValue($id);
                } else {
                    $value['default_value'] =  '';
                }
            }

            if ($value['name'] == "Insurance_number") {
                $value['default_value'] =  $elda_insurance_number;
            }

            if ($value['name'] == "Date_of_birth") {
                $value['default_value'] =   $dob;
            }

            if ($value['name'] == "first_name") {
                $value['default_value'] =  $first_name;
            }

            if ($value['name'] == "family_name") {
                $value['default_value'] =  $lastname;
            }

            if ($value['name'] == "Free_service_contract") {
                $value['default_value'] = $free_service_contract;
            }

            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        return $result_response . "<br>";
    }

    //This function  is for fourth line in Registration of gkk file
    public   function EldaRegistrationGenerateFourthLine()
    {
        $second_line = config('eldaconstants.elda_generate_registration_fourth_line'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart(99, 3);
            }
            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        $generate = str_repeat("&nbsp;", 527);  //Generate blank space of remaininglength

        return $result_response . $generate . "<br>";
    }

    //This function  is for second line in Request An InsurancNo of gkk file
    public   function EldaRequestAnInsurancNoSecond($id)
    {
        $elda_detail = EldaDetail::where('contact_id', $id)->where('status', 'register')->with('contact_detail')
            ->latest()->first();
        $creation_date = "";
        $creation_time = "";
        if ($elda_detail['date']) {
            $creation_date =  date("dmY", strtotime($elda_detail['date']));
        }

        if ($elda_detail['created_at']) {
            $creation_time =  date("His", strtotime($elda_detail['created_at']));
        }
        $second_line = config('eldaconstants.elda_request_an_insurancno_second'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart(00, 1);
            }
            if ($value['name'] == "creation_date") {
                $value['default_value'] =    $creation_date;
            }
            if ($value['name'] == "creation_time") {
                $value['default_value'] =    $creation_time;
            }
            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        $generate = str_repeat("&nbsp;", 442);  //Generate blank space of remaininglength
        //  $generate = str_repeat("&nbsp;", 373);  //Generate blank space of remaininglength

        return $result_response . $generate . "<br>";
    }

    //This function  is for third line in Request An InsurancNo of gkk file
    public   function EldaRequestAnInsurancNoThird($id)
    {
        $account_no1 =  ContactBankDetail::select('account_no', 'iban_no')->where('contact_id', $id)->get();
        $account_no = "";
        foreach ($account_no1 as $elda) {
            if ($elda['account_no']) {
                $account_no = $elda['account_no'];
            }
        }

        $address =  ContactAddress::select('type', 'street_address1', 'street_address2', 'city', 'state', 'country', 'zip')->where('type', "P")->where('contact_id', $id)->get();
        $city_zip_code = "";
        $city = "";
        $streetname = "";

        foreach ($address as $elda) {
            if ($elda['zip']) {
                $city_zip_code = $elda['zip'];
            }
            if ($elda['city']) {
                $city = $elda['city'];
            }
            if ($elda['street_address1']) {
                $streetname = $elda['street_address1'];
            }

            if($elda['country']){
                $country = Country::find($elda['country']);
                $country_code = strtoupper($country->code);

            }else{
                $country_code = 'DE';
            }
        }

        $elda = EldaDetail::where('contact_id', $id)->where('status', 'register')->with('contact_detail')
            ->latest()->first();

        $first_name = "";
        $lastname = "";
        $email = "";
        $employer_phone_number = "";
        $elda_insurance_number = "";
        $dob = "";
        $nationality = "";
        $gender = "";
        $free_service_contract = "";

        if ($elda['contact_detail']['first_name']) {
            $first_name = $elda['contact_detail']['first_name'];
        }
        if ($elda['contact_detail']['last_name']) {
            $lastname = $elda['contact_detail']['last_name'];
        }
        if ($elda['contact_detail']['email']) {
            $email =  $elda['contact_detail']['email'];
        }
        if ($elda['contact_detail']['mobile1']) {
            $employer_phone_number =  $elda['contact_detail']['mobile1'];
        }

        if ($elda['elda_insurance_number']) {
            $elda_insurance_number = $elda['elda_insurance_number'];
        }
        if ($elda['contact_detail']['dob']) {
            $dob =  date("dmY", strtotime($elda['contact_detail']['dob']));
        }
        if ($elda['contact_detail']['nationality']) {
            // $nationality =   substr($elda['contact_detail']['nationality'], 0, 3);
            $nationality = $country_code;
        }

        if ($elda['contact_detail']['gender'] == "M") {
            $gender = 1;
        } else {
            $gender = 2;
        }

        if ($elda['is_free_service_contract'] == 0) {
            $free_service_contract = "N";
        } else {
            $free_service_contract = "Y";
        }

        $second_line = config('eldaconstants.elda_request_an_insurancno_third'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart("VS", 2);
            }
            if ($value['name'] == "reference_value") {
                $value['default_value'] = "ECVS-" .  $this->GenerateReferenceValue($id);
            }
            // if ($value['name'] == "contribution_account_number") {
            //     $value['default_value'] =   $account_no;
            // }
            if ($value['name'] == "employer_name") {
                $value['default_value'] =  $first_name . $lastname;
            }

            if ($value['name'] == "employer_phone_number") {
                $value['default_value'] =   $employer_phone_number;
            }
            if ($value['name'] == "Employer_email_address") {
                $value['default_value'] =  $email;
            }

            if ($value['name'] == "Insurance_number") {
                $value['default_value'] =  $elda_insurance_number;
            }
            if ($value['name'] == "Date_of_birth") {
                $value['default_value'] =   $dob;
            }
            if ($value['name'] == "first_name") {
                $value['default_value'] =  $first_name;
            }
            if ($value['name'] == "family_name") {
                $value['default_value'] =  $lastname;
            }
            if ($value['name'] == "former_family_name") {
                $value['default_value'] =  $lastname;
            }
            if ($value['name'] == "gender") {
                $value['default_value'] =  $gender;
            }
            if ($value['name'] == "nationality") {
                $value['default_value'] =  $nationality;
            }
            if ($value['name'] == "city_zip_code") {
                $value['default_value'] =  $city_zip_code;
            }
            if ($value['name'] == "place_of_residence_placename") {
                $value['default_value'] =  $city;
            }
            if ($value['name'] == "place_of_residence_streetname") {
                $value['default_value'] =  $streetname;
            }

            if ($value['name'] == "Free_service_contract") {
                $value['default_value'] = $free_service_contract;
            }
            // if ($value['name'] == "Employment_area") {
            //     $value['default_value'] =   $Employment_area;
            // }
            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        return $result_response . "<br>";
    }

    //This function  is for Fourth line in Request An InsurancNo of gkk file
    public   function EldaRequestAnInsurancNoFourth()
    {
        $second_line = config('eldaconstants.elda_generate_registration_fourth_line'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart(99, 3);
            }
            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        $generate = str_repeat("&nbsp;", 596);  //Generate blank space of remaininglength
        // $generate = str_repeat("&nbsp;", 527);  //Generate blank space of remaininglength

        return $result_response . $generate . "<br>";
    }

    //This function  is for third line in DeRegistration of gkk file
    public   function EldaDeRegistrationGenerateThirdine($id)
    {
        $account_no1 =  ContactBankDetail::select('account_no', 'iban_no')->where('contact_id', $id)->get();
        $account_no = "";
        foreach ($account_no1 as $elda) {
            if ($elda['account_no']) {
                $account_no = $elda['account_no'];
            }
        }

        $elda = EldaDetail::where('contact_id', $id)->where('status', 'deregister')->with('contact_detail')
            ->latest()->first();

        $first_name = "";
        $lastname = "";
        $email = "";
        $employer_phone_number = "";
        $elda_insurance_number = "";
        $dob = "";
        $creation_date = "";

        if ($elda['contact_detail']['first_name']) {
            $first_name = $elda['contact_detail']['first_name'];
        }
        if ($elda['contact_detail']['last_name']) {
            $lastname = $elda['contact_detail']['last_name'];
        }
        if ($elda['contact_detail']['email']) {
            $email =  $elda['contact_detail']['email'];
        }
        if ($elda['contact_detail']['mobile1']) {
            $employer_phone_number =  $elda['contact_detail']['mobile1'];
        }
        if ($elda['elda_insurance_number']) {
            $elda_insurance_number = $elda['elda_insurance_number'];
        }
        if ($elda['contact_detail']['dob']) {
            $dob =  date("dmY", strtotime($elda['contact_detail']['dob']));
        }
        if ($elda['date']) {
            $creation_date =  date("dmY", strtotime($elda['date']));
        }

        $elda_insurance_reference_number = $elda['elda_insurance_reference_number'];


        $second_line = config('eldaconstants.deregistration_elda_third_line'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart("M4", 2);
            }
            // if ($value['name'] == "reference_value") {
            //     $value['default_value'] =  "ECVR-" . $elda_insurance_reference_number;
            // }

            if ($value['name'] == "reference_value") {
                $value['default_value'] = "ECVR-" .  $this->GenerateReferenceValue($id);
            }
            // if ($value['name'] == "contribution_account_number") {
            //     $value['default_value'] =   $account_no;
            // }
            if ($value['name'] == "employer_name") {
                $value['default_value'] =  $first_name . $lastname;
            }
            if ($value['name'] == "On/off_date_and_change_date") {
                $value['default_value'] =  $creation_date;
            }
            if ($value['name'] == "End_of_employment") {
                $value['default_value'] =  $creation_date;
            }

            if ($value['name'] == "employer_phone_number") {
                $value['default_value'] =   $employer_phone_number;
            }
            if ($value['name'] == "Employer_email_address") {
                $value['default_value'] =  $email;
            }

            if ($value['name'] == "Reference_value_of_the_VSNR_requirement") {
                if ($elda_insurance_number == "") {

                    $value['default_value'] =  "ECVS-" .  $this->GenerateReferenceValue($id);
                } else {
                    $value['default_value'] =  '';
                }
            }
            
            if ($value['name'] == "Insurance_number") {
                $value['default_value'] =  $elda_insurance_number;
            }
            if ($value['name'] == "Date_of_birth") {
                $value['default_value'] =   $dob;
            }
            if ($value['name'] == "first_name") {
                $value['default_value'] =  $first_name;
            }
            if ($value['name'] == "family_name") {
                $value['default_value'] =  $lastname;
            }

            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        return $result_response . "<br>";
    }

    //This function  is for third line in Cancel Registration of gkk file
    public function EldaCancelRegistrationGenerateThirdine($id)
    {
        $account_no1 =  ContactBankDetail::select('account_no', 'iban_no')->where('contact_id', $id)->get();
        $account_no = "";
        foreach ($account_no1 as $elda) {
            if ($elda['account_no']) {
                $account_no = $elda['account_no'];
            }
        }

        $elda = EldaDetail::where('contact_id', $id)->where('status', 'register')->with('contact_detail')
            ->latest()->first();

        $first_name = "";
        $lastname = "";
        $email = "";
        $employer_phone_number = "";
        $elda_insurance_number = "";
        $dob = "";
        $creation_date = "";

        if ($elda['contact_detail']['first_name']) {
            $first_name = $elda['contact_detail']['first_name'];
        }
        if ($elda['contact_detail']['last_name']) {
            $lastname = $elda['contact_detail']['last_name'];
        }
        if ($elda['contact_detail']['email']) {
            $email =  $elda['contact_detail']['email'];
        }
        if ($elda['contact_detail']['mobile1']) {
            $employer_phone_number =  $elda['contact_detail']['mobile1'];
        }
        if ($elda['elda_insurance_number']) {
            $elda_insurance_number = $elda['elda_insurance_number'];
        }
        if ($elda['contact_detail']['dob']) {
            $dob =  date("dmY", strtotime($elda['contact_detail']['dob']));
        }
        if ($elda['date']) {
            $creation_date =  date("dmY", strtotime($elda['date']));
        }

        $elda_insurance_reference_number = $elda['elda_insurance_reference_number'];

        $second_line = config('eldaconstants.cancelregistration_elda_third_line'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart("S3", 2);
            }
            // if ($value['name'] == "reference_value") {
            //     $value['default_value'] =  "ECVR-" . $elda_insurance_reference_number;
            // }

            if ($value['name'] == "reference_value") {
                $value['default_value'] = "ECVR-" .  $this->GenerateReferenceValue($id);
            }
            if ($value['name'] == "reference_value_of_the_original_report") {
                $value['default_value'] = "ECVR-" .  $this->GenerateReferenceValue($id);
            }
            // if ($value['name'] == "contribution_account_number") {
            //     $value['default_value'] =   $account_no;
            // }
            if ($value['name'] == "employer_name") {
                $value['default_value'] =  $first_name . $lastname;
            }
            if ($value['name'] == "On/off_date_and_change_date") {
                $value['default_value'] =  $creation_date;
            }

            if ($value['name'] == "employer_phone_number") {
                $value['default_value'] =   $employer_phone_number;
            }
            if ($value['name'] == "Employer_email_address") {
                $value['default_value'] =  $email;
            }
           
            if ($value['name'] == "Reference_value_of_the_VSNR_requirement") {
                if ($elda_insurance_number == "") {

                    $value['default_value'] =  "ECVS-" .  $this->GenerateReferenceValue($id);
                } else {
                    $value['default_value'] =  '';
                }
            }

            if ($value['name'] == "Insurance_number") {
                $value['default_value'] =  $elda_insurance_number;
            }
            if ($value['name'] == "Date_of_birth") {
                $value['default_value'] =   $dob;
            }
            if ($value['name'] == "first_name") {
                $value['default_value'] =  $first_name;
            }
            if ($value['name'] == "family_name") {
                $value['default_value'] =  $lastname;
            }

            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        return $result_response . "<br>";
    }

    //This function  is for third line in Cancellation of gkk file
    public function EldaCancellationGenerateThirdine($id)
    {
        $account_no1 =  ContactBankDetail::select('account_no', 'iban_no')->where('contact_id', $id)->get();
        $account_no = "";
        foreach ($account_no1 as $elda) {
            if ($elda['account_no']) {
                $account_no = $elda['account_no'];
            }
        }

        $elda = EldaDetail::where('contact_id', $id)->where('status', 'deregister')->with('contact_detail')
            ->latest()->first();

        $first_name = "";
        $lastname = "";
        $email = "";
        $employer_phone_number = "";
        $elda_insurance_number = "";
        $dob = "";
        $creation_date = "";

        if ($elda['contact_detail']['first_name']) {
            $first_name = $elda['contact_detail']['first_name'];
        }
        if ($elda['contact_detail']['last_name']) {
            $lastname = $elda['contact_detail']['last_name'];
        }
        if ($elda['contact_detail']['email']) {
            $email =  $elda['contact_detail']['email'];
        }
        if ($elda['contact_detail']['mobile1']) {
            $employer_phone_number =  $elda['contact_detail']['mobile1'];
        }
        if ($elda['elda_insurance_number']) {
            $elda_insurance_number = $elda['elda_insurance_number'];
        }
        if ($elda['contact_detail']['dob']) {
            $dob =  date("dmY", strtotime($elda['contact_detail']['dob']));
        }
        if ($elda['date']) {
            $creation_date =  date("dmY", strtotime($elda['date']));
        }
        $elda_insurance_reference_number = $elda['elda_insurance_reference_number'];

        $second_line = config('eldaconstants.cancellation_elda_third_line'); //Get array values for second line
        $result_response = "";
        foreach ($second_line as $value) {
            if ($value['name'] == "identification_part") {
                $value['default_value'] = $this->GenerateIdentificationPart("S4", 2);
            }
            // if ($value['name'] == "reference_value") {
            //     $value['default_value'] =  "ECVR-" . $elda_insurance_reference_number;
            // }

            if ($value['name'] == "reference_value") {
                $value['default_value'] = "ECVR-" .  $this->GenerateReferenceValue($id);
            }
            if ($value['name'] == "reference_value_of_the_original_report") {
                $value['default_value'] = "ECVR-" .  $this->GenerateReferenceValue($id);
            }
            // if ($value['name'] == "contribution_account_number") {
            //     $value['default_value'] =   $account_no;
            // }
            if ($value['name'] == "employer_name") {
                $value['default_value'] =  $first_name . $lastname;
            }
            if ($value['name'] == "On/off_date_and_change_date") {
                $value['default_value'] =  $creation_date;
            }

            if ($value['name'] == "employer_phone_number") {
                $value['default_value'] =   $employer_phone_number;
            }
            if ($value['name'] == "Employer_email_address") {
                $value['default_value'] =  $email;
            }
          
            if ($value['name'] == "Reference_value_of_the_VSNR_requirement") {
                if ($elda_insurance_number == "") {

                    $value['default_value'] =  "ECVS-" .  $this->GenerateReferenceValue($id);
                } else {
                    $value['default_value'] =  '';
                }
            }

            if ($value['name'] == "Insurance_number") {
                $value['default_value'] =  $elda_insurance_number;
            }
            if ($value['name'] == "Date_of_birth") {
                $value['default_value'] =   $dob;
            }
            if ($value['name'] == "first_name") {
                $value['default_value'] =  $first_name;
            }
            if ($value['name'] == "family_name") {
                $value['default_value'] =  $lastname;
            }

            $generatefield = $this->EldaGenerateLineUsingField($value['default_value'], $value['length'], $value['type']);
            $result_response .= $generatefield;
        }
        return $result_response . "<br>";
    }

    /**Elda files processing */
    public function eldaFilesProcess($gkk_name, $id, $type, $base_path, $gkk_url, $status)
    {
        Log::info("Elda files process job execution started!.");
        DB::beginTransaction();
            $elda_detail = EldaDetail::where('contact_id', $id)->where('status', $status)->with('contact_detail')->latest()->first();

            $input_details['gkk_file'] = $gkk_url;
            $input_details['process_name'] = $type;
            $input_details['contact_id'] = $id;
            $input_details['elda_insurance_number'] = $elda_detail->elda_insurance_number;
            $input_details['elda_insurance_reference_number'] = $elda_detail->elda_insurance_reference_number;

            /**Uplaod file on FTP threw queue php function */
            clearstatcache();
            $ftpHost   = env('FTP_HOST');
            $ftpUsername = env('FTP_USERNAME');
            $ftpPassword = env('FTP_PASSWORD');

            $connId = @ftp_ssl_connect($ftpHost) or Log::info("Couldn't connect to".$ftpHost);

            // login to FTP
            $login_result = @ftp_login($connId, $ftpUsername, $ftpPassword);

            //Check FTP connection is establized or not
            if ((!$connId) || (!$login_result)) {
                Log::info("FTP Connection Failed");
                return;
            }

            @ftp_pasv($connId, true) or Log::info("Unable switch to passive mode");

            // local & server file path
            $localDirFilePath = $gkk_name;

            // Uploading a GKK file in server
            @ftp_put($connId, '/' . $gkk_name, $localDirFilePath, FTP_ASCII);

            // sleep(15);

            // Downloading delta.ret file from server
            $file_name = 'elda_'.$elda_detail['contact_detail']['first_name'].'_'.strtotime(date('Y-m-d H:i:s')).'.ret';
            @ftp_get($connId, $file_name, 'elda.ret', FTP_BINARY);

            $url = $this->uploadEldaFile($base_path, $file_name, $file_name);
            $input_details['ret_file'] = $url;

            //Returns cuurent directory name
            $cuurent_direcotry = @ftp_pwd($connId);

            //Returns an array of arrays with file infos from the directory
            $files = @ftp_mlsd($connId, ".");

            if (is_array($files)) {
                $count = count($files);
                for ($i = 0; $i < $count; $i++) {
                    $data = @ftp_get($connId, $files[$i]['name'], $files[$i]['name'], FTP_BINARY);
                    /**Function for Upload GKK file into s3 AWS storage */
                    $url = $this->uploadEldaFile($base_path, $files[$i]['name'], $files[$i]['name']);
                    $ext = pathinfo($url, PATHINFO_EXTENSION);
                    if($ext === 'xml'){
                        $input_details['xml_file'] = $url;

                        /**Get xml elda details */
                        $xml_data = new SimpleXMLElement($url, null, true);
                        if($xml_data){
                            if(isset($xml_data->status)){
                                $input_details['status'] = $xml_data->status;
                            }
                            $part = $xml_data->xpath("//ns1:code");
                            if($part){
                                if(isset($part[0]->elda_text)){
                                    $input_details['xml_elda_text'] = $part[0]->elda_text;
                                }
                            }
                        }
                        /**End */
                    }else{
                        $input_details['output_txtfile'] = $url;
                    }
                    unlink($files[$i]['name']);
                }
            }
            /**Unlink local temp files */
            unlink(public_path($gkk_name));
            unlink($file_name);

            // close FTP connection
            @ftp_close($connId);
            /**End */
            EldaFTPProcoessDetails::create($input_details);

        DB::commit();
        Log::info("Elda files process job successfully executed!.");
    }
}
