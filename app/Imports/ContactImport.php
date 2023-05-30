<?php
/**Instructors Import */
namespace App\Imports;

use App\Models\Contact;
use App\Models\Language;
use App\Models\ContactAddress;
use App\Models\ContactLanguage;
use App\Models\ContactBankDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ContactImport implements ToCollection, WithStartRow
{
    /**For skip heading row */
    public function startRow(): int
    {
        return 2;
    }

    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        foreach ($collection as $row)
        {
            /**For email already exist then skip the row */
            if($row[9]){
                $exist = Contact::where('email', $row[9])->first();
                if($exist){
                    continue;
                }
            }
            
            /**Contact details */
            $contact = Contact::create([
                'category_id' => '2',//Instructor
                'first_name' => ($row[0]?:null),
                'last_name' => ($row[1]?:null),
                'dob' => ($row[2]?date('Y-m-d',strtotime($row[2])):null),
                'gender' => ($row[3]=='M'?'M':($row[3]=='W'?'F':'O')),
                'mobile1' => trim(($row[8]?:null)),
                'mobile2' => trim(($row[13]?:null)),
                'email' => ($row[9]?:null),
                'insurance_number' => ($row[14]?:null),
                'nationality' => ($row[20]?:null),
                'created_by' => auth()->user()->id
            ]);

            /**Contact local address details */
            if($row[4]){
                $contact_local_address = array();
                $contact_local_address['contact_id'] = $contact->id;
                $contact_local_address['type'] = ($row[4] ?'L': 'L');
                $contact_local_address['street_address1']  = ($row[4] ?: null);
                $contact_local_address['city'] = ($row[6] ?: null);
                $contact_local_address['zip'] = ($row[5] ?: null);
                ContactAddress::create($contact_local_address);
            }

            /**Contact secondary address details */
            if($row[10]){
                $contact_secondary_address = array();
                $contact_secondary_address['contact_id']  = $contact->id;
                $contact_secondary_address['type'] = ($row[10] ?'P': 'P');
                $contact_secondary_address['street_address1']  = ($row[10] ?: null);
                $contact_secondary_address['city'] = ($row[12] ?: null);
                $contact_secondary_address['zip'] = ($row[11] ?: null);
                ContactAddress::create($contact_secondary_address);
            }

            /**Contact bank details */
            ContactBankDetail::create([
                'contact_id' => $contact->id,
                'account_no' => ($row[15] ?: null),
                'bank_name' => ($row[17] ?: null),
                'biz' => ($row[16] ?: null),
            ]);

            /**Contact language details */
            $contact_language_data = array();
            if($row[18]){
                $languages = explode(', ',$row[18]);
                if(count($languages)){
                    foreach($languages as $val){
                        $val = trim($val);
                        $l = Language::where('name', $val)->first();
                        if(!$l){
                            $display_order = Language::orderBy('display_order', 'DESC')->value('display_order');
                            $input['name'] = $val;
                            $input['display_order'] = $display_order + 1 ;
                            $input['created_by'] = auth()->user()->id;
                            $l = Language::create($input);
                        }
                        $contact_language_data['contact_id'] = $contact->id;
                        $contact_language_data['language_id'] = $l->id;
                        ContactLanguage::create($contact_language_data);
                    }
                }
            }
        }
    }
}
