<?php

namespace App\Http\Controllers\API;

use File;
use Excel;
use Image;
use Response;
use App\Models\Contact;
use App\Models\EldaDetail;
use App\Jobs\GkkMoveWithFtp;
use Illuminate\Http\Request;
use App\Models\EldaFunctions;
use App\Jobs\EldaFilesProcess;
use App\Exports\EldaDetailsExport;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Http\Controllers\EldaFuctions;
use App\Models\EldaFTPProcoessDetails;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response as Download;

class EldaContoller extends Controller
{
    use Functions, EldaFuctions;

    /**Get elda function names */
    public function getFunctionsNames()
    {
        $functions = EldaFunctions::get();

        /**Return success response */
        return $this->sendResponse(true, __('strings.list_message', ['name' => 'Elda functions']), $functions);
    }

    /**Register elda details */
    public function registerEldaDetails(Request $request)
    {
        /**Validation Rules */
        $v = validator($request->all(), [
            'function_id' => 'required|exists:elda_functions,id',
            'comment' => 'nullable|max:100',
            'elda_insurance_number' => 'nullable',
            'is_requested_number' => 'boolean',
            'joining_date' => 'required|date',
            'employement_area' => 'nullable|in:employee,worker',
            'pension_contribution_from' => 'required|date',
            'is_free_service_contract' => 'boolean',
            'contact_id' => 'required|exists:contacts,id,deleted_at,NULL',
            'minority' => 'boolean',
        ]);
        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /**Get necessary details */
        $input_details = $request->only('function_id', 'comment', 'elda_insurance_number', 'is_requested_number', 'joining_date', 'employement_area', 'pension_contribution_from', 'is_free_service_contract', 'contact_id', 'minority') + ['status' => 'register', 'date' => date('Y-m-d')];

        $elda_detail = EldaDetail::where('contact_id', $request->contact_id)
            ->orderBy('id', 'DESC')->first();

        if ($elda_detail && $elda_detail->status == 'register') {
            return $this->sendResponse(false, __('strings.already_registered_elda'));
        }

        EldaDetail::create($input_details);

        /**Return success response */
        return $this->sendResponse(true, __('strings.create_sucess', ['name' => 'Elda registeration']));
    }

    /**De-register elda details */
    public function deRegisterEldaDetails(Request $request)
    {
        /**Validation Rules */
        $v = validator(
            $request->all(),
            [
                'contact_id' => 'required|exists:elda_details,contact_id'
            ],
            [
                'contact_id.exists' => __('strings.user_have_no_elda_detail')
            ]
        );
        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $elda_detail = EldaDetail::where('contact_id', $request->contact_id)
            ->orderBy('id', 'DESC')->first();

        if ($elda_detail->status == 'deregister') {
            return $this->sendResponse(false, __('strings.already_deregistered_elda'));
        }

        $input_details['function_id'] = $elda_detail->function_id;
        $input_details['comment'] = $elda_detail->comment;
        $input_details['elda_insurance_number'] = $elda_detail->elda_insurance_number;
        $input_details['is_requested_number'] = $elda_detail->is_requested_number;
        $input_details['joining_date'] = $elda_detail->joining_date;
        $input_details['employement_area'] = $elda_detail->employement_area;
        $input_details['pension_contribution_from'] = $elda_detail->pension_contribution_from;
        $input_details['is_free_service_contract'] = $elda_detail->is_free_service_contract;
        $input_details['contact_id'] = $request->contact_id;
        $input_details['minority'] = $elda_detail->minority;

        $input_details['status'] = 'deregister';
        $input_details['date'] = date('Y-m-d');

        EldaDetail::create($input_details);

        /**Return success response */
        return $this->sendResponse(true, __('strings.create_sucess', ['name' => 'Elda de-registeration']));
    }

    /**List */
    public function list(Request $request)
    {
        /**Validation Rules */
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
        ]);
        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $elda_details = EldaDetail::query();

        $elda_details = $elda_details->with('function_detail', 'contact_detail');

        /**Filtered by contact id */
        if ($request->contact_id) {
            $elda_details = $elda_details->where('contact_id', $request->contact_id);
        }

        /**Search in status */
        if ($request->status) {
            $status = $request->status;
            $elda_details = $elda_details->where('status', $status);
        }

        /**Normal search */
        if ($request->search) {
            $search = $request->search;
            $elda_details = $elda_details->where(function ($query) use ($search) {
                $query->where('comment', 'like', "%$search%");
                $query->orWhere('elda_insurance_number', 'like', "%$search%");
                $query->orWhere('employement_area', 'like', "%$search%");
            });
            $elda_details_count = $elda_details->count();
        }

        /**Date base filter */
        if ($request->start_date) {
            $start_date  = $request->start_date;
            $elda_details = $elda_details->where(function ($query) use ($start_date) {
                // $query->whereDate('joining_date','>=', $start_date);
                // $query->orWhereDate('pension_contribution_from','>=', $start_date);
                $query->orWhereDate('date', '>=', $start_date);
            });
        }

        if ($request->end_date) {
            $end_date  = $request->end_date;
            $elda_details = $elda_details->where(function ($query) use ($end_date) {
                // $query->whereDate('joining_date','<=', $end_date);
                // $query->orWhereDate('pension_contribution_from','<=', $end_date);
                $query->orWhereDate('date', '<=', $end_date);
            });
        }
        /** */

        /**Get total records count */
        $elda_details_count = $elda_details->count();

        /**For pagination */
        if ($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $elda_details->skip($perPage * ($page - 1))->take($perPage);
        }

        $elda_details = $elda_details
        ->orderBy('id', 'DESC')
        ->get();

        /**Export list */
        if ($request->is_export) {
            if (!count($elda_details)) {
                return __('strings.record_not_found');;
            }
            return Excel::download(new EldaDetailsExport($elda_details->toArray()), 'EldaDetails.xls');
        }

        $data = [
            'elda_details' => $elda_details,
            'count' => $elda_details_count
        ];

        /**Return success response with details */
        return $this->sendResponse(true, __('strings.list_message', ['name' => 'Elda details']), $data);
    }

    /**Delete */
    public function delete($id)
    {
        $elda = EldaDetail::find($id);

        if (!$elda) {
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Elda details']));
        }
        $elda->delete();

        /**Return success response */
        return $this->sendResponse(true, __('strings.delete_sucess', ['name' => 'Elda details']));
    }

    /**Txt export */
    function txtExport(Request $request)
    {
        /**Validation Rules */
        $v = validator($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);
        /**If mismatch validation then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $elda_details = EldaDetail::query();

        if ($request->start_date) {
            $elda_details = $elda_details->whereDate('date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $elda_details = $elda_details->whereDate('date', '<=', $request->end_date);
        }

        $elda_details = $elda_details
            ->where('status', 'register')
            ->with('contact_detail')->get();

        if (!count($elda_details)) {
            return __('strings.record_not_found');;
        }

        $elda_details_data = array();
        $i = 0;
        foreach ($elda_details as $elda) {
            $elda_details_data[$i][] = $elda['elda_insurance_number'];
            $elda_details_data[$i][] = date('d/m/Y', strtotime($elda['contact_detail']['dob']));
            $elda_details_data[$i][] = $elda['contact_detail']['last_name'];
            $elda_details_data[$i][] = $elda['contact_detail']['first_name'];
            $elda_details_data[$i][] = date('d/m/Y', strtotime($elda['date']));
            $elda_details_data[$i][] = ($elda['employement_area'] == 'employee' ? '02' : '01');
            $elda_details_data[$i][] = ($elda['minority'] ? 'Yes' : 'No');
            $elda_details_data[$i][] = ($elda['is_free_service_contract'] ? 'Yes' : 'No');
            $elda_details_data[$i][] = date('d/m/Y', strtotime($elda['pension_contribution_from']));
            $i = $i + 1;
        }

        $this->write_tabbed_file('EldaDetails.txt', $elda_details_data, true);

        $headers = array(
            'Content-Type: application/pdf',
        );

        /**Download file */
        return Response::download('EldaDetails.txt', 'EldaDetails.txt', $headers);
    }

    /**For manage txt file formate */
    public function write_tabbed_file($filepath, $elda_details_data, $save_keys = false)
    {
        $content = '';
        reset($elda_details_data);
        foreach ($elda_details_data as $key => $val) {
            // $key = str_replace("\t", " ", $key);
            $val = str_replace("\t", " ", $val);
            // if ($save_keys){ $content .=  $key."\t"; }
            // create line:
            $content .= (is_array($val)) ? implode("\t", $val) : $val;
            $content .= "\n";
        }
        if (file_exists($filepath) && !is_writeable($filepath)) {
            return false;
        }
        if ($fp = fopen($filepath, 'w+')) {
            fwrite($fp, $content);
            fclose($fp);
        } else {
            return false;
        }
        return true;
    }

    /**For manage gkk file formate */
    public function generate_gkk_file($filepath, $firstline)
    {
        $firstline =   html_entity_decode($firstline); //convert &nbsp into space.
        $breaks = array("<br>");
        $firstline = str_ireplace($breaks, "\r\n", $firstline); //convert <br> into newline.
        $firstline = iconv("Windows-1252", "UTF-8", $firstline); //convert string  from UTF-8 to Windows-1252.
        $firstline = str_replace('Â', '', $firstline);
        $firstline = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $firstline); // Remove Â from string

        // $filepath = public_path($filepath);
        $filepath = $filepath;

        if (file_exists($filepath) && !is_writeable($filepath)) {
            return false;
        }
        $contents = '';
        //Save our content to the file.
        file_put_contents($filepath, $contents);
        if ($fp = fopen($filepath, 'w+')) {
            fwrite($fp, $firstline);
            fclose($fp);
            chmod($filepath, 0777); 
        } else {
            return false;
        }
        return true;
    }

    /**Elda Registration details */
    public function EldaRegistration($id)
    {
        $elda_detail = EldaDetail::where('contact_id', $id)->where('status', 'register')->with('contact_detail')
        ->orderBy('id', 'DESC')->first();

        /**S3 elda uploading base path */
        $base_path = config('constants.ELDA_S3_PATH').''.$elda_detail['contact_detail']['first_name'].'_'.strtotime(date('Y-m-d H:i:s'));

        $name = "EldaRegistration_".$elda_detail['contact_detail']['first_name']."_".strtotime(date('Y-m-d H:i:s')).".gkk";
        $type = "Registration";
        $status = "register";
        /** */

        $firstline = $this->EldaRegistrationGenerateFirstLine();

        $secondline = $this->EldaRegistrationGenerateSecondLine($id);

        $thirdline = $this->EldaRegistrationGenerateThirdine($id);

        $fourthline = $this->EldaRegistrationGenerateFourthLine();

        $alllinedata =  $firstline . $secondline . $thirdline . $fourthline;
        /**Generate gkk file */
        $this->generate_gkk_file($name, $alllinedata);

        /**Upload GKK file into s3 AWS storage */
        $url = $this->uploadEldaFile($base_path, $name, $name);
        $input_details['gkk_file'] = $url;

        /**Uplaod file on FTP threw queue job */
        //EldaFilesProcess::dispatch($name, $id, $type, $base_path, $url, $status);
        $this->eldaFilesProcess($name, $id, $type, $base_path, $url, $status);
        /**End */

        header("Content-Disposition: attachment; filename=" . basename($input_details['gkk_file']));
        
        return readfile($input_details['gkk_file']);
    }

    /**Elda RequestAnInsuranceNo details */
    public function EldaRequestAnInsuranceNo($id)
    {
        $elda_detail = EldaDetail::where('contact_id', $id)->where('status', 'register')->with('contact_detail')
        ->orderBy('id', 'DESC')->first();

        /**S3 elda uploading base path */
        $base_path = config('constants.ELDA_S3_PATH').''.$elda_detail['contact_detail']['first_name'].'_'.strtotime(date('Y-m-d H:i:s'));

        $name = "EldaRequestAnInsuranceNo_".$elda_detail['contact_detail']['first_name']."_".strtotime(date('Y-m-d H:i:s')).".gkk";
        $type = "RequestAnInsuranceNo";
        $status = "register";
        /** */

        $firstline = $this->EldaRegistrationGenerateFirstLine();

        $secondline = $this->EldaRequestAnInsurancNoSecond($id);

        $thirdline = $this->EldaRequestAnInsurancNoThird($id);

        $fourthline = $this->EldaRequestAnInsurancNoFourth();

        $alllinedata =  $firstline . $secondline . $thirdline . $fourthline;

        $this->generate_gkk_file($name, $alllinedata);

        /**Upload GKK file into s3 AWS storage */
        $url = $this->uploadEldaFile($base_path, $name, $name);
        $input_details['gkk_file'] = $url;

        /**Uplaod file on FTP threw queue job */
        //EldaFilesProcess::dispatch($name, $id, $type, $base_path, $url, $status);
        $this->eldaFilesProcess($name, $id, $type, $base_path, $url, $status);
        /**End */

        header("Content-Disposition: attachment; filename=" . basename($input_details['gkk_file']));
        
        return readfile($input_details['gkk_file']);
    }

    /**Elda Deregistration details */
    public function EldaDeregistration($id)
    {
        $elda_detail = EldaDetail::where('contact_id', $id)->where('status', 'deregister')->with('contact_detail')
        ->orderBy('id', 'DESC')->first();

        /**S3 elda uploading base path */
        $base_path = config('constants.ELDA_S3_PATH').''.$elda_detail['contact_detail']['first_name'].'_'.strtotime(date('Y-m-d H:i:s'));

        $name = "EldaDeregistration_".$elda_detail['contact_detail']['first_name']."_".strtotime(date('Y-m-d H:i:s')).".gkk";
        $type = "Deregistration";
        $status = "deregister";
        /** */

        $firstline = $this->EldaRegistrationGenerateFirstLine();

        $secondline = $this->EldaRegistrationGenerateSecondLine($id);

        $thirdline = $this->EldaDeRegistrationGenerateThirdine($id);

        $fourthline = $this->EldaRegistrationGenerateFourthLine();

        $alllinedata =  $firstline . $secondline . $thirdline . $fourthline;

        $this->generate_gkk_file($name, $alllinedata);

        /**Upload GKK file into s3 AWS storage */
        $url = $this->uploadEldaFile($base_path, $name, $name);
        $input_details['gkk_file'] = $url;

        /**Uplaod file on FTP threw queue job */
        //EldaFilesProcess::dispatch($name, $id, $type, $base_path, $url, $status);
        $this->eldaFilesProcess($name, $id, $type, $base_path, $url, $status);
        /**End */

        header("Content-Disposition: attachment; filename=" . basename($input_details['gkk_file']));
        
        return readfile($input_details['gkk_file']);
    }

    /**Elda CancelRegistration details */
    public function EldaCancelRegistration($id)
    {
        $elda_detail = EldaDetail::where('contact_id', $id)->where('status', 'register')->with('contact_detail')
        ->orderBy('id', 'DESC')->first();

        /**S3 elda uploading base path */
        $base_path = config('constants.ELDA_S3_PATH').''.$elda_detail['contact_detail']['first_name'].'_'.strtotime(date('Y-m-d H:i:s'));

        $name = "EldaCancelRegistration_".$elda_detail['contact_detail']['first_name']."_".strtotime(date('Y-m-d H:i:s')).".gkk";
        $type = "CancelRegistration";
        $status = "register";
        /** */

        $firstline = $this->EldaRegistrationGenerateFirstLine();

        $secondline = $this->EldaRegistrationGenerateSecondLine($id);

        $thirdline = $this->EldaCancelRegistrationGenerateThirdine($id);

        $fourthline = $this->EldaRegistrationGenerateFourthLine();

        $alllinedata =  $firstline . $secondline . $thirdline . $fourthline;

        $this->generate_gkk_file($name, $alllinedata);

       /**Upload GKK file into s3 AWS storage */
       $url = $this->uploadEldaFile($base_path, $name, $name);
       $input_details['gkk_file'] = $url;

       /**Uplaod file on FTP threw queue job */
       //EldaFilesProcess::dispatch($name, $id, $type, $base_path, $url, $status);
       $this->eldaFilesProcess($name, $id, $type, $base_path, $url, $status);
       /**End */

       header("Content-Disposition: attachment; filename=" . basename($input_details['gkk_file']));
       
       return readfile($input_details['gkk_file']);
    }

    /**Elda Cancellation details */
    public function EldaCancellation($id)
    {
        $elda_detail = EldaDetail::where('contact_id', $id)->where('status', 'deregister')->with('contact_detail')
        ->orderBy('id', 'DESC')->first();

        /**S3 elda uploading base path */
        $base_path = config('constants.ELDA_S3_PATH').''.$elda_detail['contact_detail']['first_name'].'_'.strtotime(date('Y-m-d H:i:s'));

        $name = "EldaCancelDeRegistration_".$elda_detail['contact_detail']['first_name']."_".strtotime(date('Y-m-d H:i:s')).".gkk";
        $type = "CancelDeRegistration";
        $status = "deregister";
        /** */

        $firstline = $this->EldaRegistrationGenerateFirstLine();

        $secondline = $this->EldaRegistrationGenerateSecondLine($id);

        $thirdline = $this->EldaCancellationGenerateThirdine($id);

        $fourthline = $this->EldaRegistrationGenerateFourthLine();

        $alllinedata =  $firstline . $secondline . $thirdline . $fourthline;

        $this->generate_gkk_file($name, $alllinedata);

        /**Upload GKK file into s3 AWS storage */
        $url = $this->uploadEldaFile($base_path, $name, $name);
        $input_details['gkk_file'] = $url;

        /**Uplaod file on FTP threw queue job */
        //EldaFilesProcess::dispatch($name, $id, $type, $base_path, $url, $status);
        $this->eldaFilesProcess($name, $id, $type, $base_path, $url, $status);
        /**End */

        header("Content-Disposition: attachment; filename=" . basename($input_details['gkk_file']));
        
        return readfile($input_details['gkk_file']);
    }

    /**Elda ftp process list */
    public function ftpProcessList(Request $request)
    { 
        $v = validator($request->all(), [
            'page' => 'integer|min:1',
            'perPage' => 'integer|min:1',
            'contact_id' => 'nullable'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $ftp_details = EldaFTPProcoessDetails::query();

        if($request->contact_id){
            $ftp_details = $ftp_details->where('contact_id', $request->contact_id);
        }

        $count = $ftp_details->count();

        /**For pagination */
        if ($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $ftp_details->skip($perPage * ($page - 1))->take($perPage);
        }

        $ftp_details = $ftp_details->orderBy('id', 'DESC')->get();

        $data = [
            'ftp_details' => $ftp_details,
            'count' => $count
        ];
        return $this->sendResponse(true,__('strings.list_message', ['name' => 'Ftp process']),$data);
    }
}
