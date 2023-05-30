<?php

namespace App\Http\Controllers\API;

use Hash;
use Excel;
use App\User;
use App\Models\Contact;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PaymentMethod;
use App\Exports\ContactExport;
use App\Models\ContactAddress;
use App\Models\ContactAllergy;
use App\Models\SequenceMaster;
use App\Models\ContactLanguage;
use App\Models\CustomerUpdates;
use App\Models\ContactBankDetail;
use App\Http\Controllers\Functions;
use App\Notifications\SendPassword;
use App\Http\Controllers\Controller;
use App\Notifications\InvitationCode;
use App\Models\ContactDifficultyLevel;
use App\Models\ContactAdditionalPerson;
use Illuminate\Support\Facades\Notification;
use App\Models\BookingProcess\BookingProcesses;
use App\Notifications\TrainingMaterialNotifacation;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;
use App\Imports\ContactImport;
use App\Imports\CustomerImport;
use App\Jobs\SendPushNotification;
use http\Exception\InvalidArgumentException;
use Exception;

class ContactController extends Controller
{
    use Functions;

    /* API for Creating Contact information */
    public function createContact(Request $request)
    {
        $v = $this->checkValidation($request);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $checkEmail = Contact::where('email',$request->email)->count();
        if ($checkEmail) return $this->sendResponse(false,__('strings.email_already_taken'));

        if(($request->category_id===1 || $request->category_id===2 || $request->category_id===3) && (!$request->nationality)) {
            return $this->sendResponse(false,__('strings.addition_persons_validation'));    
        }

        /* if($request->category_id === 2 || $request->category_id === 3) {
            $bank_validation = validator($request->all(), [    
                'bank_details.iban_no' => 'required|max:50',
                'bank_details.account_no' => 'max:50',
                'bank_details.bank_name' => 'required|max:50',
                'bank_details.salary_group' => 'required|integer|min:1',
                'bank_details.joining_date' => 'date_format:Y-m-d',
                'bank_details.last_booking_date' => 'date_format:Y-m-d',
            ]);
        }
        if ($bank_validation->fails()) return $this->sendResponse(false,$bank_validation->errors()->first()); */

        $input = $request->all();
        $input['created_by'] = auth()->user()->id;

        if($request->profile_pic) {
            $url = $this->uploadImage('contacts',$request->first_name,$request->profile_pic);
            $input['profile_pic'] = $url;
        }

        if($request->category_id === 2) {
            $instructor_number = SequenceMaster::where('code', 'IN')->first();
            
            if($instructor_number){
                $input['instructor_number'] = $instructor_number->sequence;
                $update_data['sequence'] = $input['instructor_number']+1;
                $instructor_number->update(['sequence'=>$instructor_number->sequence+1]);
            }
        }
        
        $contact = Contact::create($input);

        if($request->category_id === 1) {
            $nine_digit_random_number = mt_rand(100000000, 999999999);
            if($contact){
                $update_data['QR_nine_digit_random_number'] = $nine_digit_random_number;
                $contact->update(['QR_number'=>$update_data['QR_nine_digit_random_number']]);
            }
        }
        
        if($request->allergies) {
            foreach ($request->allergies as $allergy) {
                ContactAllergy::create([
                        'contact_id' => $contact->id,
                        'allergy_id' => $allergy
                    ]);
            }
        }

        if($request->languages) {
            foreach ($request->languages as $language) {
                ContactLanguage::create([
                        'contact_id' => $contact->id,
                        'language_id' => $language
                    ]);
            }
        }

        if($request->address) {
            foreach ($request->address as $address) {
                ContactAddress::create([
                        'contact_id' => $contact->id,
                    ] + $address);
            }
        }

        if($request->category_id === 2 || $request->category_id === 3) {
            $bank_details = $request->bank_details;
            ContactBankDetail::create(['contact_id'=>$contact->id] + $bank_details);
        }

        if($request->addition_persons) {
            foreach ($request->addition_persons as $person) {
                ContactAdditionalPerson::create([
                        'contact_id' => $contact->id,
                    ] + $person);
            }
        }

        //Insert instructor difficulty level
        if($request->category_id === 2 && $request->difficulty_level_id) {
            $difficulty_level_ids = $request->difficulty_level_id;
            foreach ($difficulty_level_ids as $difficulty_level_id) {
                ContactDifficultyLevel::create([
                        'contact_id' => $contact->id,
                        'difficulty_level_id' => $difficulty_level_id,
                    ]);
            }
        }

        if( ($request->category_id === 2) || ($request->category_id === 1)) {
            $input = [];
            $input['email'] = $request->email;
            $password = Str::random(6);
            $input['password'] = Hash::make($password);
            $input['contact_id'] = $contact->id;
            $input['is_app_user'] = 1;
            $input['is_verified'] = 1;
            $input['email_token'] = '';
            $input['device_token'] = '';
            $input['device_type'] = '';  
            $input['name'] =  $contact->salutation.' '.$contact->first_name.' '.$contact->last_name;
            if($request->category_id === 1 && $request->display_in_app) {
                //Create new customer
                $user = User::create($input);
                // $user->notify(new SendPassword($password));
                $user->notify((new SendPassword($password))->locale($user->language_locale));
            }
            if($request->category_id === 2) {
                //Send invitation code to instructor
                $code = Str::random(10);
                $code_expire_at = date("Y-m-d H:i:s",strtotime("+2 days"));
                $input['register_code'] = $code;
                $input['register_code_expire_at'] = $code_expire_at;
                $user = User::create($input);
                // $user->notify(new InvitationCode($code));
                $user->notify((new InvitationCode($code))->locale($user->language_locale));
            }
        }

        /**Add crm user action trail */    
        if($contact){
            $action_id = $contact->id; //contact created id
            $action_type = 'A'; //A = Added
            $module_id = 9; //module id base module table 
            $module_name = "Contacts"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id,$action_type,$module_id,$module_name);
        }
        /**End manage trail */
        return $this->sendResponse(true,__('strings.contact_created_success'));
    }

    /* API for Updating Contact information */
    public function updateContact(Request $request,$id)
    {
        $v = $this->checkValidation($request);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $checkEmail = Contact::where('id','!=',$id)->where('email',$request->email)->count();
        if ($checkEmail) return $this->sendResponse(false,__('strings.email_already_taken'));

        if(($request->category_id===1 || $request->category_id===2 || $request->category_id===3) && (!$request->nationality)) {
            return $this->sendResponse(false,__('strings.addition_persons_validation'));    
        }

        /* if($request->category_id === 2 || $request->category_id === 3) {
            $bank_validation = validator($request->all(), [    
                'bank_details.iban_no' => 'required|max:50',
                'bank_details.account_no' => 'max:50',
                'bank_details.bank_name' => 'required|max:50',
                'bank_details.salary_group' => 'required|integer|min:1',
                'bank_details.joining_date' => 'date_format:Y-m-d',
                'bank_details.last_booking_date' => 'date_format:Y-m-d',
            ]);
        }
        if ($bank_validation->fails()) return $this->sendResponse(false,$bank_validation->errors()->first()); */

        $contact = Contact::find($id);
        if (!$contact) return $this->sendResponse(false,__('strings.contact_not_found'));

        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;

        if($request->profile_pic && $request->imageUpdate && $request->is_app_user) {
            $input['profile_pic'] = $request->profile_pic;
        }elseif($request->profile_pic && $request->imageUpdate){
            $url = $this->uploadImage('contacts',$request->first_name,$request->profile_pic);
            $input['profile_pic'] = $url;
        }

        if($request->category_id === 1 && $contact->QR_number=='') {
            $nine_digit_random_number = mt_rand(100000000, 999999999);
            $update_data['QR_nine_digit_random_number'] = $nine_digit_random_number;
            $input['QR_number'] = $update_data['QR_nine_digit_random_number'];
        }

        $contact->update($input);

        if( ($request->category_id === 2 || $request->category_id === 1) && $request->display_in_app && (empty($contact->user_detail))) {
        //create instructor as user with email send to notification
        $input = [];
        $input['email'] = $request->email;
        $password = Str::random(6);
        $input['password'] = Hash::make($password);
        $input['contact_id'] = $contact->id;
        $input['is_app_user'] = 1;
        $input['is_verified'] = 1;
        $input['email_token'] = '';
        $input['device_token'] = '';
        $input['device_type'] = '';  
        $input['name'] =  $contact->salutation.' '.$contact->first_name.' '.$contact->last_name;
        $user = User::create($input);
        // $user->notify(new SendPassword($password));
        $user->notify((new SendPassword($password))->locale($user->language_locale));
        }

        if($request->allergies) {
            ContactAllergy::where('contact_id',$id)->delete();
            foreach ($request->allergies as $allergy) {
                ContactAllergy::create([
                        'contact_id' => $contact->id,
                        'allergy_id' => $allergy
                    ]);
            }
        }

        if($request->languages) {
            ContactLanguage::where('contact_id',$id)->delete();
            foreach ($request->languages as $language) {
                ContactLanguage::create([
                        'contact_id' => $contact->id,
                        'language_id' => $language
                    ]);
            }
        }

        if($request->address) {
            ContactAddress::where('contact_id',$id)->delete();
            foreach ($request->address as $address) {
                ContactAddress::create([
                        'contact_id' => $contact->id,
                    ] + $address);
            }
        }

        if($request->category_id === 2 || $request->category_id === 3) {
            $bank_details = $request->bank_details;
            $checkBank = ContactBankDetail::where('contact_id',$id);
            if($checkBank->count() === 0) {
                ContactBankDetail::create(['contact_id'=>$contact->id] + $bank_details);
            } else {
                $update_data = [];
                $update_data['account_no'] = $bank_details['account_no'];
                $update_data['iban_no'] = $bank_details['iban_no'];
                $update_data['bank_name'] = $bank_details['bank_name'];
                $update_data['salary_group'] = $bank_details['salary_group'];

                if(!empty($bank_details['biz'])) {
                    $update_data['biz'] = $bank_details['biz'];
                }
                $checkBank->update($update_data);
            }
        }

        if($request->addition_persons) {
            ContactAdditionalPerson::where('contact_id',$id)->delete();
            foreach ($request->addition_persons as $person) {
                ContactAdditionalPerson::create([
                        'contact_id' => $contact->id,
                    ] + $person);
            }
        }

        //Update instructor difficulty level
        if($request->category_id === 2 && $request->difficulty_level_id) {
            $difficulty_level_ids = $request->difficulty_level_id;
            ContactDifficultyLevel::where('contact_id',$id)->delete();
            foreach ($difficulty_level_ids as $difficulty_level_id) {
                ContactDifficultyLevel::create([
                        'contact_id' => $contact->id,
                        'difficulty_level_id' => $difficulty_level_id,
                    ]);
            }
        }

        $user = User::where('contact_id',$id);
        if($user){
            $update['email'] = $request->email;
            $user->update($update);
        }
        // if (!$contact) return $this->sendResponse(false,__('strings.contact_not_found'));

        /**Add crm user action trail */
        if ($contact) {
            $action_id = $contact->id; //contact id
            $action_type = 'U'; //U = Updated
            $module_id = 9; //module id base module table
            $module_name = "Contacts"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true,__('strings.contact_updated_success'));
    }

    /* API for Deleting Contact information */
    public function deleteContact($id)
    {
        $contact = Contact::find($id);
        if (!$contact) return $this->sendResponse(false,'Contact not found');
        $checkUser = User::where('contact_id',$id)->count();
        if($checkUser) return $this->sendResponse(false,__('strings.delete_contact_user_exists'));
        
        /**Add crm user action trail */
        if ($contact) {
            $action_id = $contact->id; //contact id
            $action_type = 'D'; //D = Deleted
            $module_id = 9; //module id base module table
            $module_name = "Contacts"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        $contact->delete();

        return $this->sendResponse(true,__('strings.contact_deleted_success'));
    }

    /* API for View Contact information */
    public function viewContact($id)
    {
        $contact = Contact::with('category_detail')->with('subcategory_detail')->with(['allergies'=>function($q){
            $q->with('allergy:id,name');
        }])
        ->with('office_detail')
        ->with('difficulty_level_detail.difficulty_level_detail')
        ->with(['languages'=>function($q){
            $q->with('language:id,name');
        }])
        ->with(['user_detail'=>function($q){
            $q->select('id','contact_id');
        }])
        ->with('address.country_detail:id,name,code')
        ->with('addition_persons')
        ->with('accommodation_data')
        ->with('bank_detail.salary_group_detail:id,name')
        ->find($id);

        if (!$contact) return $this->sendResponse(false,__('strings.contact_not_found'));

        /* $contact_booking_details = Contact::with(['booking_detail.booking_process_detail.payment_detail'=>function($query) use($id){
                     */
                    
        if($contact->category_id == 1){
            $contact_booking_details = Contact::with(['booking_detail.booking_process_detail.payment_detail'=>function($query) use($id){
                    $query->where('customer_id',$id);
                }])
            ->with('booking_detail.booking_process_detail.course_detail.course_data','booking_detail.booking_process_detail.course_detail.course_detail_data')
            ->find($id);
            $contact->booking_detail = $contact_booking_details;
            
            $current_date = date('Y-m-d H:i:s');
    
            //get contact upcoming booking details 
            $upcoming_booking = BookingProcessCustomerDetails::with('booking_process_detail')
            ->with(['booking_process_detail.payment_detail'=>function($query) use($id){
                $query->where('customer_id',$id);
            }])
            ->with('booking_process_detail.course_detail.course_data')
            ->with('course_detail_data')
            ->where('StartDate_Time', '>', $current_date)
            ->where('customer_id',$id)->get();

            $contact->upcoming_booking = $upcoming_booking;

        }else if($contact->category_id == 2){
            $contact->booking_detail = array();
            $contact->upcoming_booking = array();
            //get instructor booking details 
            
            $booking_ids = BookingProcessInstructorDetails::where('contact_id',$id)->pluck('booking_process_id');

            $instructor_booking_details = BookingProcesses::whereIn('id',$booking_ids)
            ->where('is_trash',0)
            ->with(['course_detail.course_data'])
            ->with(['customer_detail.customer'])
            ->with(['extra_participant_detail'])
            ->orderBy('id', 'desc')
            ->get();
            
            $contact->instructor_booking_details = $instructor_booking_details;
            
        }
        $user = User::where('contact_id',$id)->first();
        
        if($user){
            $contact->user_detail = $user;
        }
        
        if($contact->QR_number){
            $url = url('/').'/customer_QR_code/'.$contact->QR_number;
            $qr_code = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=".$url."&choe=UTF-8";
            $contact->qr_code = $qr_code;
        }
        
        return $this->sendResponse(true,'success',$contact);
    }

     /* API for Active/Inactive Contact information */
     public function changeStatus(Request $request)
     {
        $v = validator($request->all(), [
            'contact_id' => 'required|integer|min:1',
            'status' => 'boolean',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $contact = Contact::find($request->contact_id);
        if (!$contact) return $this->sendResponse(false,__('strings.contact_not_found'));
        
        $contact->is_active = $request->status;
        $contact->save();
            
        if(!$request->status) {
            $user = User::where('contact_id',$request->contact_id)->first();
            if($user) {
                $userTokens = $user->tokens;
                foreach($userTokens as $token) {
                    $token->revoke();
                }
                $user->update(['device_token'=>null]);
            }
        }

        /**Add crm user action trail */
        if ($contact) {
            $action_id = $contact->id; //contact id
            if($request->status){
                $action_type = 'ACS'; //ACS = Active Change Status
            }else{
                $action_type = 'DCS'; //DCS = Deactive Change Status
            }
            $module_id = 9; //module id base module table
            $module_name = "Contacts"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */
        
        return $this->sendResponse(true,__('strings.status_change_success'));
     }

     /* API for Contact list */
     public function contactList(Request $request)
     {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $page = $request->page;    
        $perPage = $request->perPage;    
        $contacts = Contact::query();
        if($request->is_employee) {
            $contacts = $contacts->where(function($q){
                $q->where('category_id',2)->orWhere('category_id',3);
            });
        }
        //If Role is Booking Manager Display Only Customer Contact in List
        if(auth()->user()->role==15)
        {
            $contacts = $contacts->where('category_id',1);
        }
        $contactCount = $contacts->count();
        if($request->search) {
            $search = $request->search;
            $contacts = $contacts->where(function($query) use($search){
                $query->where('email','like',"%$search%");
                $query->orWhere('mobile1','like',"%$search%");
                $query->orWhere('salutation','like',"%$search%");
                $query->orWhere('first_name','like',"%$search%");
                $query->orWhere('middle_name','like',"%$search%");
                $query->orWhere('last_name','like',"%$search%");
            });
            $contactCount = $contacts->count();
        }

        if($request->category) {
           $contacts = $contacts->where('category_id',$request->category);
           $contactCount = $contacts->count();
        }

        $contacts = $contacts->skip($perPage*($page-1))->take($perPage);

        $contacts = $contacts->with('category_detail')
        ->with(['user_detail'=>function($q){
                $q->select('id','contact_id','register_code_verified_at');
        }])
        ->with('accommodation_data')
        ->orderBy('id', 'desc')
        ->get();

        $data = [
            'contacts' => $contacts,
            'count' => $contactCount
        ];
        return $this->sendResponse(true,'success',$data);
     }

     /* API for Active Contact list */
    public function ActiveContactList()
     {
        $contacts = Contact::query();
        
        $contacts = $contacts->where('is_active', '1');

        if(isset($_GET['category_id'])){
            $contacts = $contacts->where('category_id',$_GET['category_id']);
        }

        if(isset($_GET['display_in_app'])) {
           $contacts = $contacts->where('display_in_app',$_GET['display_in_app']);
        }

        if(isset($_GET['subcategory_id'])) {
            $contacts = $contacts->where('subcategory_id',$_GET['subcategory_id']);
         }

        if(isset($_GET['search'])) {
            $search = $_GET['search'];
            $contacts = $contacts->where(function($query) use($search){
                $query->where('email','like',"%$search%");
                $query->orWhere('mobile1','like',"%$search%");
                $query->orWhere('salutation','like',"%$search%");
                $query->orWhere('first_name','like',"%$search%");
                $query->orWhere('middle_name','like',"%$search%");
                $query->orWhere('last_name','like',"%$search%");
            });
        }

        if(isset($_GET['last_name'])) {
            $last_name = $_GET['last_name'];
            $contacts = $contacts->where(function($query) use($last_name){
                $query->where('last_name','like',"%$last_name%");
            });
        }

        $contacts = $contacts->with(['payment_method'])
        ->with('subcategory_detail')
        ->with('languages')
        ->with(['allergies'=>function($q){
            $q->with('allergy:id,name');
        }])
        ->with('accommodation_data')
        ->get();
        return $this->sendResponse(true,'success',$contacts);
     }

     /* API for Get instructor for booking calender */
     public function getInstructorsCalender(Request $request)
     {
        $contacts = Contact::query();
        if($request->language_detail)
        {
            //dd($request->language_detail);
            $instructor_ids = ContactLanguage::whereIn('language_id',$request->language_detail)
            ->pluck('contact_id');
            $contacts = $contacts->whereIn('id',$instructor_ids);
        }
        if($request->difficulty_level_id)
        {
            $contactIds = ContactDifficultyLevel::where('difficulty_level_id',$request->difficulty_level_id)->pluck('contact_id');
            $contacts = $contacts->whereIn('id',$contactIds);
        }

        if($request->instructor_ids)
        {
            $instructor_ids = $request->instructor_ids;
            if(count($instructor_ids) > 0) {
                $contacts = $contacts->whereIn('id',$instructor_ids);
            }
        }
        $contacts = $contacts->with('languages.language')->with('difficulty_level_detail.difficulty_level_detail')->where('is_active',true)->where('category_id',2)->get();
        return $this->sendResponse(true,'success',$contacts);
     }

     /* API for Export Contact list */
     public function contactExport()
     {
        return Excel::download(new ContactExport, 'contacts.csv');
     }

     /* API for Generate Customer QR code */
     public function generate_qr($QR_nine_digit_number)
     {
            $contact = Contact::where('QR_number',$QR_nine_digit_number)->first();
            //dd($contact);
            return view('customer/customer_details_from_QR',$contact);
            /* $nine_digit_random_number = mt_rand(100000000, 999999999);
            $url = url('/').'/customer_QR_code/'.$nine_digit_random_number;
            $qr_code = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=".$url."&choe=UTF-8";
            return $qr_code; */
            //echo $QR_nine_digit_number;
     }
     
      /* API for Edit prefer payment method id for customer  */
     public function editPreferPaymentMethod(Request $request)
     {
        $v = validator($request->all(), [
            'contact_id' => 'required|integer|min:1',
            'prefer_payment_method_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $contact = Contact::find($request->contact_id);
        if (!$contact) return $this->sendResponse(false,__('strings.contact_not_found'));

        $payment_method = PaymentMethod::where('id',$request->prefer_payment_method_id)->where('is_active',1)->first();
        if (!$payment_method) return $this->sendResponse(false,__('strings.payment_method_not_found'));
       
        $contact->prefer_payment_method_id = $request->prefer_payment_method_id;
        $contact->save();
        
        return $this->sendResponse(true,__('strings.contact_prefer_payment_method_updated_success'));
     }

     /* API for updtaes assign teaching material for customer */
     public function assignCustomerTeachingMaterial(Request $request)
     {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
            'contact_ids' => 'required|array',
            'description' => 'max:500',
            'material_links' => 'array',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $instructor_id = auth()->user()->contact_id;


         if($request->contact_ids) {
            
                $contact_detail = array();
                $i = 0;
                foreach($request->contact_ids as $contact) {
                    $contact_detail_inputs[$i]['booking_process_id'] = $request->booking_process_id;
                    $contact_detail_inputs[$i]['instructor_id'] = $instructor_id;
                    $contact_detail_inputs[$i]['customer_id'] = $contact;
                    $contact_detail_inputs[$i]['description'] = $request->description;
                    $contact_detail_inputs[$i]['created_at'] = date("Y-m-d H:i:s");                    
                    if($request->material_links){
                        $urls = implode(', ', $request->material_links); 
                        $contact_detail_inputs[$i]['urls'] = $urls;
                    }
                    $i++;

                    $course_details = BookingProcessCourseDetails::where('booking_process_id',$request->booking_process_id)
                    ->first();
                    
                    $contact = Contact::where('id',$contact)->first();
                    $instructor = Contact::where('id',$instructor_id)->first();

                    $data['customer_name'] = $contact->first_name;
                    $data['instuctor_name'] = $instructor->first_name;
                    $data['deep_link'] =  url('/d/customer/training-material?booking_process_id='.$request->booking_process_id.'&course_id='.$course_details->course_id);
                    $update_at = date('H:i:s');
                    $instructor_name = $instructor->first_name;
                    // $contact->notify(new TrainingMaterialNotifacation($data,$instructor_name,$update_at)); 
                    $contact->notify((new TrainingMaterialNotifacation($data,$instructor_name,$update_at))->locale($contact->language_locale)); 
                }
                $add_customer_detail = CustomerUpdates::insert($contact_detail_inputs);
       
                $contact = Contact::find($instructor_id);

                if($add_customer_detail){
                    $data['instructor_name'] =  $contact->salutation." ".$contact->first_name." ".$contact->last_name;
                    $data['booking_processes_id'] = $request->booking_process_id; 
                    $title = "You have Receive new Update";
                    // $body = "You have Receive new Update form Instructor : ".$data['instructor_name'];
                    $body = "New course update from your instructor ".$data['instructor_name'];
                    $this->setPushNotificationData($request->contact_ids,5,$data,$title,$body,$request->booking_process_id);
                }
        }
        return $this->sendResponse(true,__('strings.material_send_sucess'));
    }

    /* API for updtaes assign teaching material for customer */
     public function getCustomerAndInstructorUpdates(Request $request)
     {
        $v = validator($request->all(), [
            'contact_id' => 'required|integer|min:1'
        ]);

        $page = $request->page ?? 1;
        $perPage = $request->perPage ?? 20;
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

            $updates = CustomerUpdates::where('customer_id',$request->contact_id)
            ->with(['conatct_detail'=>function($query){
                $query->select('id','salutation','email','first_name','middle_name','last_name','profile_pic');
            }])
            ->with(['instructor_detail'=>function($query){
                $query->select('id','salutation','email','first_name','middle_name','last_name','profile_pic');
            }])
            ->skip($perPage*($page-1))->take($perPage)
            ->orderBy('id','desc')
            ->get()->toArray();
        
        if($request->is_instructor){
            $booking_updates = CustomerUpdates::where('booking_process_id',$request->booking_process_id)->get();
            if($booking_updates->isEmpty()) return $this->sendResponse(false,'No updates found for selected booking course');
            $updates = CustomerUpdates::where('instructor_id',$request->contact_id)
            ->where('booking_process_id',$request->booking_process_id)
            ->with(['conatct_detail'=>function($query){
                $query->select('id','salutation','email','first_name','middle_name','last_name','profile_pic');
            }])
            ->with(['instructor_detail'=>function($query){
                $query->select('id','salutation','email','first_name','middle_name','last_name','profile_pic');
            }])
            ->skip($perPage*($page-1))->take($perPage)
            ->orderBy('id','desc')
            ->get()->toArray();
        }
        return $this->sendResponse(true,'success',$updates);
     }

     /* API for Get Instructor Not Mapped Salary Group */
     public function getInstructorNotMappedSalaryGroup(Request $request)
     {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $page = $request->page;    
        $perPage = $request->perPage;    
        $contacts = Contact::query();
        $contacts = $contacts->where('category_id',2)->where('category_id',3);

        $salaryContactIds = ContactBankDetail::where('salary_group','!=',null)->pluck('contact_id');
        $contacts = $contacts->whereNotIn('id',$salaryContactIds);
        $contactCount = $contacts->count();
        
        $contacts = $contacts->with('category_detail','office_detail','difficulty_level_detail.difficulty_level_detail','payment_method')
        ->orderBy('id', 'desc')
        ->skip($perPage*($page-1))->take($perPage)
        ->get();

        $data = [
            'contacts' => $contacts,
            'count' => $contactCount
        ];
        return $this->sendResponse(true,'success',$data);
    }

    public function sendInvitationCode(Request $request)
    {
        $v = validator($request->all(), [
            'instructor_id' => 'required|integer',
            'email' => 'required|email',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $instructor_id = $request->instructor_id;
        $user = User::where('contact_id',$instructor_id)->first();
        if(!$user) return $this->sendResponse(false,__('Invalid instructor id'));

         //Send invitation code to instructor
         $code = Str::random(10);
         $code_expire_at = date("Y-m-d H:i:s",strtotime("+2 days"));
         $user->register_code = $code;
         $user->register_code_expire_at = $code_expire_at;
         $user->save();
         //$user->notify(new InvitationCode($code));
         Notification::route('mail', $request->email)
            ->notify(new InvitationCode($code));
         return $this->sendResponse(true,__('strings.invitation_code_sent_success'),$user);
    }

    /* This API for crm side view customer and instructor booklings */
    public function contactBookings(Request $request)
     {
         $v = validator($request->all(), [
             'page' => 'required|integer|min:1',
             'perPage' => 'required|integer|min:1',
             'id' => 'required|integer|min:1'
         ]);
 
         if ($v->fails()) {
             return $this->sendResponse(false, $v->errors()->first());
         }
         
         $page = $request->page;
         $perPage = $request->perPage;
         $booking_processes_ids = [];
 
         $id = $request->id;
         $contact = Contact::where('id', $id)->first();
         
        if ($contact) {
            if ($contact->category_id==1) {
                $booking_processes_ids = BookingProcessCustomerDetails::where('customer_id', $id)->pluck('booking_process_id');
            } elseif ($contact->category_id==2) {
                $booking_processes_ids = BookingProcessInstructorDetails::where('contact_id', $id)->pluck('booking_process_id');
            }
        }
        
         $current_date = date('Y-m-d H:i:s');
         $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
         ->pluck('booking_process_id');

         if ($request->date_type == 'Upcoming') {
             $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                 ->where('StartDate_Time', '>', $current_date)
                 ->pluck('booking_process_id');
         }
         if ($request->date_type == 'Past') {
             $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                     ->where('EndDate_Time', '<', $current_date)
                     ->pluck('booking_process_id');
         }
         if ($request->date_type == 'Ongoing') {
             $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                     ->where('StartDate_Time', '<=', $current_date)
                     ->where('EndDate_Time', '>=', $current_date)
                     ->pluck('booking_process_id');
         }
                  
         $booking_detail = BookingProcesses::whereIn('id', $ongoing_booking_processes_ids)
            ->where('is_trash',0)
            ->with(['course_detail.course_data','course_detail.course_detail_data'])
            ->with(['customer_detail'=>function($query) use($id){
                    $query->where('customer_id',$id);
                },'customer_detail.customer'])
            ->with(['payment_detail'=>function($query) use($id){
                    $query->where('customer_id',$id);
                },'payment_detail.customer'])
            ->with(['instructor_detail'=>function($query) use($id){
                    $query->where('contact_id',$id);
                },'instructor_detail.contact'])
            ->with(['extra_participant_detail'])
            ->orderBy('id', 'desc');

        $booking_processes_count = $booking_detail->count();
         
        $booking_detail = $booking_detail->skip($perPage*($page-1))->take($perPage)
        ->get();

        $data = [
            'booking_detail' => $booking_detail,
            'count' => $booking_processes_count
        ];

         return $this->sendResponse(true, 'success', $data);
     }

    /* Check validation for adding/updating contact */
    public function checkValidation($request)
    {
        $v = validator($request->all(), [
            'category_id' => 'required|integer|min:1',
            'office_id' => 'nullable|integer|min:1',
            'difficulty_level_id' => 'nullable|array',
            'salutation' => 'nullable|in:Mr.,Mrs.,Ms.,Dr.,Jr.',
            'first_name'=>'max:250',
            'middle_name'=>'nullable|max:250',
            'last_name'=>'max:250',
            'email' => 'required|email',
            'mobile1'=>'max:25',
            'mobile2'=>'max:25',
            'nationality'=>'max:50',
            'designation'=>'max:50',
            'dob'=>'date_format:Y-m-d',
            'gender' => 'in:M,F,O',
            //'profile_pic' => '',
            'display_in_app' => 'boolean',
            'skiing_level' => 'nullable|in:Kinderland,Unknown,Green,Blue,Red,Black',
            // '' => 'required_if:category_id,==,3',
            'allergies' => 'required_if:category_id,1',
            'languages' => 'required_if:category_id,1|required_if:category_id,2',
            
            'address' => 'array',
            'address.0.type' => 'in:L,P',
            'address.*.street_address1' => 'max:250',
            'address.*.city' => 'max:50',
            'address.*.state' => 'max:50',
            'address.*.country' => 'nullable|integer|min:1',
            'address.*.zip' => 'nullable|max:30',

            'addition_persons'=>'array',
            'addition_persons.*.salutation'=>'in:Mr.,Mrs.,Ms.,Dr.,Jr.',
            'addition_persons.*.name'=>'max:50',
            'addition_persons.*.relationaship'=>'nullable|in:Spouse,Father,Mother,Brother,Sister,Relative',
            'addition_persons.*.mobile1'=>'max:25',
            'addition_persons.*.mobile2'=>'max:25',
            'addition_persons.*.comments'=>'max:500',

            'insurance_number' => 'nullable',
            'is_ski' => 'nullable|boolean',
            'is_snowboard' => 'nullable|boolean'
        ],[
            'addition_persons.*.relationaship.in' => "Additional contact relationship is not valid",
            'allergies.required_if' => 'Allergies field is required',
            // 'languages.required_if' => 'Languages field is required'
            'languages.required_if' => 'Some fields are required in Additional Details Tab',
            'insurance_number.required_if' => 'Insurance number is required for instructor'
        ]);
        return $v;
    }

    /* API for Creating Contact from add booking page */
    public function addCustomer(Request $request)
    {
        $v = validator($request->all(), [
            'customer_name' => 'nullable',
            'mobile1'=>'max:25',
            'mobile2'=>'max:25',
            'dob'=>'nullable|date_format:Y-m-d',
            'email' => 'required|email',
            'skiing_level' => 'nullable|in:Kinderland,Unknown,Green,Blue,Red,Black',
            'gender' => 'in:M,F,O',
            'accomodation' => 'nullable',
            'allergies' => 'nullable|array',
            'allergies.*' => 'exists:allergies,id',
            'languages' => 'required|array',
            'languages.*' => 'exists:languages,id',
            'accommodation_id' => 'nullable|integer'
        ],[
            'dob.date_format' => __('validation.dob_invalid_formate'),
            'allergies.*.exists' =>  __('strings.exist_validation', ['name' => 'allergies']),
            'languages.*.exists' =>  __('strings.exist_validation', ['name' => 'languages'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /**Check if email exist then return error response */
        $checkEmail = Contact::where('email',$request->email)->count();
        if ($checkEmail) return $this->sendResponse(false,__('strings.email_already_taken'));

        /**Get neccessary details */
        $input_details = $request->only('customer_name','mobile1','mobile2','dob','email','skiing_level','gender','accomodation','accommodation_id');

        if($input_details['customer_name']){
            $customer_name = explode(" ",$input_details['customer_name']);
            if(count($customer_name) > 2)
            {
                $input_details['first_name'] = $customer_name[0];
                $input_details['middle_name'] = $customer_name[1];
                $input_details['last_name'] = $customer_name[2];
            }
            else
            {
              $input_details['first_name'] = $customer_name[0];
              $input_details['last_name'] = $customer_name[1];
            }
            
            unset($input_details['customer_name']);
        }

        $input_details['category_id']=1;

        /**Create new contact */
        $contact = Contact::create($input_details);

        if($request->allergies) {
            foreach ($request->allergies as $allergy) {
                ContactAllergy::create(
                [
                    'contact_id' => $contact->id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        if($request->languages) {
            foreach ($request->languages as $language) {
                ContactLanguage::create(
                [
                    'contact_id' => $contact->id,
                    'language_id' => $language
                ]);
            }
        }

        /**Add crm user action trail */    
        if($contact){
            $action_id = $contact->id; //contact created id
            $action_type = 'A'; //A = Added
            $module_id = 9; //module id base module table 
            $module_name = "Contacts"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id,$action_type,$module_id,$module_name);
        }
        /**End manage trail */

        $data = [
            "customer_id" => $contact->id,
            "first_name" => $contact->first_name,
            "last_name" => $contact->last_name,
        ];

        /**Return success resposne with details */
        return $this->sendResponse(true,__('strings.contact_created_success'), $data);
    }

    /**Date: 20-10-2020
     * Description : Import contact data from xls
     */
    public function importContactData(Request $request)
    {
        $v = validator($request->all(), [
            //'select_file'  => 'required|mimes:xls,xlsx'
            'import_type' => 'required|in:1,2'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        ini_set('post_max_size', '2000M');
        ini_set('upload_max_filesize', '2000M');
        ini_set('max_execution_time', '300000');
        ini_set('client_max_body_size', '200M');
        ini_set('memory_limit', '5000M');
        ini_set('max_input_time', '30000');
        
        try{
            if($request->import_type == 1){/**Import customers */
                Excel::import(new CustomerImport,request()->file('select_file'));
            }else{/**Import instructors */
                Excel::import(new ContactImport,request()->file('select_file'));
            }
        }
        catch(\InvalidArgumentException $ex)
        {
            return $this->sendResponse(false,$ex->getMessage());
        }
        catch(\Exception $ex)
        {
            return $this->sendResponse(false,$ex->getMessage());
        }

        return $this->sendResponse(true,__('strings.contact_import_success'));
    }

    /**Get customers details */
    public function getRelevantCustomersList(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'nullable|email',
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $user_id = auth()->user()->id;

        if($request->email){
            $user_id = User::where('email', $request->email)->value('id');
        }

        $customers  = Contact::query();

        $customers = $customers->where('created_by', $user_id);

        if($request->category_id){
            $customers = $customers->where('category_id',$request->category_id);
        }

        if($request->display_in_app) {
           $customers = $customers->where('display_in_app',$request->display_in_app);
        }

        if($request->subcategory_id) {
            $customers = $customers->where('subcategory_id',$request->subcategory_id);
        }

        $customers_count = $customers->count();
        
        if($request->page && $request->perPage){
            $page = $request->page;    
            $perPage = $request->perPage;    
            $customers->skip($perPage*($page-1))->take($perPage);
        }

        $customers = $customers->with(['payment_method'])
        ->with('category_detail')
        ->with('subcategory_detail')
        ->with(['allergies'=>function($q){
            $q->with('allergy:id,name');
        }]);

        $customers = $customers->get();
        
        $data = [
            "customers_data" => $customers,
            "count" => $customers_count
        ];
        return $this->sendResponse(true, 'success', $data);
    }

    /**Customer Arrival Send Notification Update to instructor  */
    public function customerArrivalNotifyInstructor(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_process = BookingProcesses::find($request->booking_process_id);
        if(!$booking_process)return $this->sendResponse(false, __('strings.booking_not_found'));

        //get all instructors using booking
        $instructor_data = BookingProcessInstructorDetails::where('booking_process_id',$request->booking_process_id)->pluck('contact_id');
        
        $booking_course = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();

        $sender_id=null;
        $type=27;
        $booking_process_id=$request->booking_process_id;
        $title = "Customer Arrival Update";
        $body = "Your customer has arrived - Please make yourself available";
        $data['course_id'] = $booking_course->course_id; 
        $data['booking_process_id'] = $request->booking_process_id;

        //send notification to instructor
        $user_tokens = User::whereIn('contact_id', $instructor_data)->select('id', 'is_notification', 'device_token', 'device_type', 'contact_id')->get()->toArray();

        if ($user_tokens) {
            foreach ($user_tokens as $key => $user_token) {
                $receiver_id = $user_token['contact_id'];

                $notification = \App\Models\Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>$type,'message'=>$body,'booking_process_id'=>$booking_process_id,'email_scheduler_type'=>null]);
                // Log::info("data",$user_token);
                if ($user_token) {
                    if ($user_token['is_notification'] == 1) {
                        if (!empty($user_token['device_token'])) {
                            SendPushNotification::dispatch($user_token['device_token'], $user_token['device_type'], $title, $body, $type, $data);
                            /* $this->push_notification($user_token['device_token'], $user_token['device_type'], $title, $body, $type, $data); */
                        }
                    }
                }
            }
        }
        return $this->sendResponse(true,__('strings.notification_success'));
    }

    /**Instructor arrival notification update to customer  */
    public function instructorArrivalNotifyCustomer(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $booking_process = BookingProcesses::find($request->booking_process_id);
        if(!$booking_process)return $this->sendResponse(false, __('strings.booking_not_found'));

        //get all customers using booking
        $customer_data = BookingProcessCustomerDetails::where('booking_process_id',$request->booking_process_id)->pluck('customer_id')->toArray();
        $customer_data = array_merge($customer_data);

        $booking_course = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();
        
        $sender_id=auth()->user()->contact_id;
        $type=31;
        $booking_process_id = $request->booking_process_id;
        
        $title = "Your instructor is at the meeting point, click here to see what color and number (s)he is holding up with their phone!";
        $body = "Your instructor is at the meeting point, click here to see what color and number (s)he is holding up with their phone!";

        $data['course_id'] = $booking_course->course_id; 
        $data['booking_process_id'] = $request->booking_process_id;
        $data['sender_id'] = $sender_id;

        //send notification to customer
        $user_tokens = User::whereIn('contact_id', $customer_data)->select('id', 'is_notification', 'device_token', 'device_type', 'contact_id')->get()->toArray();
        if ($user_tokens) {
            foreach ($user_tokens as $key => $user_token) {
                $receiver_id = $user_token['contact_id'];

                $notification = \App\Models\Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>$type,'message'=>$body,'booking_process_id'=>$booking_process_id,'email_scheduler_type'=>null]);
                if ($user_token) {
                    if ($user_token['is_notification'] == 1) {
                        if (!empty($user_token['device_token'])) {
                            SendPushNotification::dispatch($user_token['device_token'], $user_token['device_type'], $title, $body, $type, $data);
                        }
                    }
                }
            }
        }
        return $this->sendResponse(true,__('strings.notification_success'));
    }

    /**Check customer email exist */
    public function checkCustomerExist(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'required|email',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contact =  Contact::select('id', 'email', 'salutation', 'first_name', 'middle_name', 'last_name')->where('email', $request->email)->first();

        $is_customer_exist = false;
        if ($contact) {
            $is_customer_exist = true;;
        }
        $data = [
            "is_customer_exist" => $is_customer_exist
        ];
        return $this->sendResponse(true, 'Success', $data);
    }
}
