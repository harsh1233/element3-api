<?php
/**Customer Import */
namespace App\Imports; 

use App\Models\Country;
use App\Models\Contact;
use App\Models\Language;
use App\Models\SubCustomer;
use App\Models\ContactAddress;
use App\Models\ContactLanguage;
use App\Models\ContactBankDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class CustomerImport implements ToCollection, WithStartRow
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
            /**Contact local address details */
            $val = trim($row[11]);
            $c = Country::where('name', $val)->first();
            if(!$c){
                $input['name'] = $val;
                $input['created_by'] = auth()->user()->id;
                $input['created_at'] = date('Y-m-d H:i:s');
                $c = Country::create($input);
            }
            
            /**For email already exist then skip the row */
            if($row[10]){
                $exist = Contact::where('email', $row[10])->first();
                if($exist){
                    $exit_data['first_name'] = $row[1];
                    $exit_data['last_name'] = $row[0];
                    $exit_data['email'] = $row[10];
                    $exit_data['mobile1'] = $row[7];
                    $exit_data['mobile2'] = $row[6];
                    $exit_data['address'] = $row[3];
                    $exit_data['zip'] = $row[4];
                    $exit_data['dob'] = ($row[2]?date('Y-m-d',strtotime($row[2])):null);
                    $exit_data['city'] = $row[5];
                    $exit_data['country'] = $c->id;
                    $exit_data['accomodation'] = $row[9];
                    $exit_data['created_by'] = auth()->user()->id;
                    $exit_data['created_at'] = date('Y-m-d H:i:s');
                    SubCustomer::create($exit_data);
                    continue;
                }
                /**Contact details */
                $contact = Contact::create([
                    'category_id' => '1',//Customer
                    'first_name' => ($row[1]?:null),
                    'last_name' => ($row[0]?:null),
                    'dob' => ($row[2]?date('Y-m-d',strtotime($row[2])):null),
                    // 'gender' => ($row[3]=='M'?'M':($row[3]=='W'?'F':'O')),
                    'mobile1' => trim(($row[7]?:null)),
                    'mobile2' => trim(($row[6]?:null)),
                    'email' => ($row[10]?:null),
                    'accomodation' => ($row[9]?:null),
                    'created_by' => auth()->user()->id
                ]);

                if($row[3]){
                    $contact_local_address = array();
                    $contact_local_address['contact_id'] = $contact->id;
                    $contact_local_address['type'] = 'L';
                    $contact_local_address['street_address1']  = ($row[3] ?: null);
                    $contact_local_address['city'] = ($row[5] ?: null);
                    $contact_local_address['zip'] = ($row[4] ?: null);
                    $contact_local_address['country'] = $c->id;
                    ContactAddress::create($contact_local_address);
                }

                /**Contact secondary address details */
                if($row[3]){
                    $contact_secondary_address = array();
                    $contact_secondary_address['contact_id']  = $contact->id;
                    $contact_secondary_address['type'] = 'P';
                    $contact_secondary_address['street_address1']  = ($row[3] ?: null);
                    $contact_secondary_address['city'] = ($row[5] ?: null);
                    $contact_secondary_address['zip'] = ($row[4] ?: null);
                    $contact_secondary_address['country'] = $c->id;
                    ContactAddress::create($contact_secondary_address);
                }
            }else{
                continue;
            }
        }
    }
}
