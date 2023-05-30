<?php

namespace App\Http\Controllers\API;

use Hash;
use App\User;
use App\Models\Contact;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\ContactAddress;
use App\Notifications\EmailVerify;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\PasswordReset;
use App\Notifications\PasswordResetEmail;

class Book2SkiCrmController extends Controller
{
    use Functions;

    /*Customer Registration */
    public function customerRegistration(Request $request)
    {
        $v = validator($request->all(), [
            'first_name' => 'required|max:191',
            'last_name' => 'required|max:191',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
            'mobile'=>'max:25',
            'dob'=>'date_format:Y-m-d',
            'gender' => 'in:M,F,O',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contact = Contact::where('email', $request->email)->first();
        $checkEmail = User::where('email', $request->email)->count();

        if ($checkEmail) {
            return $this->sendResponse(false, __('strings.user_already_exist'));
        }

        $input_contact = [];
        $input_contact['first_name'] = $request->first_name;
        $input_contact['last_name'] = $request->last_name;
        $input_contact['email'] = $request->email;
        $input_contact['category_id'] = 1;
        $input_contact['mobile1'] = $request->mobile;
        $input_contact['dob'] = $request->dob;
        $input_contact['gender'] = $request->gender;

        if (!$contact) {
            $input_contact['QR_number'] = mt_rand(100000000, 999999999);
            $contact = Contact::create($input_contact);
        } else {
            $contact->update($input_contact);
        }

        $contact = Contact::find($contact->id);
        $contact_address = ContactAddress::where('contact_id', $contact->id)->first();

        $is_profile_fullfilled = 1;

        if (empty($contact->salutation) && empty($contact->mobile1) && empty($contact->nationality) && empty($contact->designation) && empty($contact->gender) && empty($contact->middle_name) && empty($contact_address->street_address1) && empty($contact_address->city) && empty($contact_address->state) && empty($contact_address->country)) {
            $is_profile_fullfilled = 0;
        }

        $input = [];
        $reset_token = Str::random(50);
        $input['email'] = $request->email;
        $password = $request->password;
        $input['password'] = Hash::make($password);
        $input['contact_id'] = $contact->id;
        $input['is_app_user'] = 1;
        $input['email_token'] = $reset_token;
        $input['name'] =  $contact->salutation.' '.$contact->first_name.' '.$contact->last_name;
        $user = User::create($input);

        $user = User::where('email', $user->email)
        ->with(['contact_detail'=>function($query){
            $query->select('id','category_id','salutation','first_name', 'middle_name', 'last_name','email','mobile1',
            'mobile2','nationality','designation','dob','gender','profile_pic','QR_number','is_active','contact_person_email','contact_person_name');
        }])
        ->select('id','name', 'password', 'contact_id', 'is_active', 'is_notification')
        ->first();

        $contact_name = $contact->first_name;
        // $user->notify(new EmailVerify($reset_token,$contact_name));
        $user->notify((new EmailVerify($reset_token,$contact_name))->locale($user->language_locale));
        
        /**Create new login token */
        $accessTokenObject = $user->createToken('Login');

        $data = [
            'user_data' => $user,
            'is_profile_fullfilled' => $is_profile_fullfilled,
            'token' => $accessTokenObject->accessToken,
        ];
        return $this->sendResponse(true, __('strings.user_created_success_send_email_verification'), $data);
    }

    /**Send password link to user email */
    public function forgetPassword(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'required|email',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return $this->sendResponse(false, __('strings.invalid_email'));
        }

        $password_reset = PasswordReset::where('email', $request->email)->delete();
        $reset_token = Str::random(50);
        $update_at = date('H:i:s');
        PasswordReset::create(['email'=>$request->email,'token'=>$reset_token]);
        // $user->notify(new PasswordResetEmail($reset_token, $user, $user->is_app_user,$update_at));
        $user->notify((new PasswordResetEmail($reset_token, $user, $user->is_app_user,$update_at))->locale($user->language_locale));

        return $this->sendResponse(true, __('strings.password_reset_link_sent'));
    }

    /**Reset password by user using email link */
    public function resetPassword(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
            'token' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::where('email', $request->email)->first();
        $password_reset = PasswordReset::where('token', $request->token)->first();
        if (!$user || !$password_reset) {
            return $this->sendResponse(false, 'User / Password reset token not found.');
        }
        if ($user->email != $password_reset->email) {
            return $this->sendResponse(false, 'This password reset token is invalid.');
        }
        if (Hash::check($request->password, $user->password)) {
            return $this->sendResponse(false, __('strings.same_old_new_password'));
        }

        $user->password = Hash::make($request->password);
        $user->save();
        PasswordReset::where('email', $request->email)->delete();

        return $this->sendResponse(true, __('strings.password_reset_success'));
    }

    /**Get customers details */
    public function getCustomersList(Request $request)
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

        // if($request->email){
        //     $user_id = User::where('email', $request->email)->value('id');
        // }

        $customers  = Contact::with('category_detail')
        ->where('created_by', $user_id);

        if($request->email){
            $email = $request->email;
            $customers = $customers->where('email',$email);
        }

        $customers_count = $customers->count();

        if($request->page && $request->perPage){
            $page = $request->page;    
            $perPage = $request->perPage;    
            $customers->skip($perPage*($page-1))->take($perPage);
        }

        $customers = $customers->get();
        
        $data = [
            "customers_data" => $customers,
            "count" => $customers_count
        ];
        return $this->sendResponse(true, __('strings.list_message', ['Customers']), $data);
    }

    /**Get customer details with email/ logged in user ID */
    public function getCustomer(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'nullable|email|exists:contacts,email'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        if($request->email){
            $customer = Contact::where('email', $request->email)
            ->where('created_by', auth()->user()->id)->first();
            
            if(!$customer){
                $customer_id = null;
            }else{
                $customer_id = $customer->id;
            }
        }else{
            $customer_id = auth()->user()->contact_id;
        }
        
        $customer  = Contact::with(['category_detail','address.country_detail'=>function($q){
            $q->select('id', 'name', 'code');
        }])
        ->with('subcategory_detail')
        ->with(['allergies' => function ($q) {
            $q->with('allergy:id,name');
        }])
        ->with('languages.language')
        ->with('bank_detail')
        ->with('addition_persons')
        ->select('id','category_id','salutation','first_name', 'middle_name', 'last_name','email','mobile1',
        'mobile2','nationality','designation','dob','gender','profile_pic','QR_number','is_active','contact_person_email','contact_person_name','skiing_level')
        ->find($customer_id);
        
        return $this->sendResponse(true, __('strings.get_message', ['name' => 'Customer']), $customer);
    }
}
