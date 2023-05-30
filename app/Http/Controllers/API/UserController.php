<?php

namespace App\Http\Controllers\API;

use Auth;
use Hash;
use Excel;
use App\User;
use App\Models\Contact;
use App\Exports\UserExport;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordReset;
use App\Models\ContactAddress;
use App\Models\Permissions\Role;
use App\Models\CrmUserActionTrail;
use App\Notifications\EmailVerify;
use App\Http\Controllers\Functions;
use App\Notifications\SendPassword;
use App\Http\Controllers\Controller;
use App\Notifications\AccountConfirm;
use App\Models\ContactAdditionalPerson;
use App\Notifications\PasswordResetEmail;
use Illuminate\Support\Facades\Redirect;
use LaravelDuo\LaravelDuo;

class UserController extends Controller
{
    use Functions;

    function __construct(LaravelDuo $laravelDuo)
    {
        $this->_laravelDuo = $laravelDuo;
    }

    /** Login user with email and password */
    public function login(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return $this->sendResponse(false, __('strings.invalid_credential'));
        }
        if (!Hash::check($request->password, $user->password)) {
            return $this->sendResponse(false, __('strings.incorrect_password'));
        }
        if (!$user->is_active) {
            return $this->sendResponse(false, __('strings.account_disabled'));
        }

        $duoinfo = array(
            'HOST' => $this->_laravelDuo->get_host(),
            'POST' => url('duologin'),
            'USER' => $user->email,
            'SIG'  => $this->_laravelDuo->signRequest($this->_laravelDuo->get_ikey(), $this->_laravelDuo->get_skey(), $this->_laravelDuo->get_akey(), $user->email)
        );
        /**For logged in user contact available then get profile pic */
        $profile_pic = null;
        if($user->contact_id){
            $contact = Contact::find($user->contact_id);
            $profile_pic = $contact->profile_pic;
        }
        
        $accessTokenObject = $user->createToken('Login');
        $menus = $this->getMenus($user->role);
        $admin = User::whereHas('role_detail', function($q){
            $q->where('code','CHTADM');
        })->first();
        $adminJID = null;
        if($admin) {
            $internal_ip = config('constants.openfireInternalIp');
            $conference_ip = 'conference.'.config('constants.openfireInternalIp');
            $adminJID = $admin->jabber_id.'@'.$internal_ip;
        }
        $data = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
            'role' => $user->role,
            'token' => $accessTokenObject->accessToken,
            'contact_id' => $user->contact_id,
            'profile_pic' => $profile_pic,
            'menus' => $menus,
            'adminJID' => $adminJID,
            'duoinfo' => $duoinfo,
            'authduourl' => url('authduologin') 
        ];

        /**Update user duo logged in value */
        $user->is_duo_loggedin = false;
        $user->save();
        /** */

        return $this->sendResponse(true, 'success', $data);
    }

    /**
     * Stage Three - After Duo Auth Form
     */
    public function duologin(Request $request)
    {
        //dd(URL::to('/') . '/duologin');
        /**
         * Sent back from Duo
         */
        //$response = $_GET['sig_response'];
        $response = $request->sig_response;

        $U = $this->_laravelDuo->verifyResponse($this->_laravelDuo->get_ikey(), $this->_laravelDuo->get_skey(), $this->_laravelDuo->get_akey(), $response);

        /**
         * Duo response returns USER field from Stage Two
         */
        if($U){

            /**
             * Get the id of the authenticated user from their email address
             */
            // $id = User::getIdFromEmail($U);
            $user = User::where('email', $U)->first();
            
            /**Update user duo logged in value */
            $user->is_duo_loggedin = true;
            $user->save();
            /** */

            return Redirect::to(env('ADMINPANEL_URL').'/dashboard');
            //return $this->sendResponse(true, 'success');

            /**
             * Log the user in by their ID
             */
            //Auth::loginUsingId($user->id);

            /**
             * Check Auth worked, redirect to homepage if so
             */
            // if(Auth::check())
            // {
            //     return $this->sendResponse(true, 'success');
            // }
        }
    }

    /** Login user with email and password in APP side */
    public function app_login(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'device_token' => 'required|max:200',
            'device_type' => 'in:A,I'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::where('email', $request->email)->with('contact_detail')
        ->first();

        if (!$user) {
            return $this->sendResponse(false, __('strings.invalid_credential'));
        }

        if (!Hash::check($request->password, $user->password)) {
            return $this->sendResponse(false, __('strings.incorrect_password'));
        }

        $contact = Contact::find($user->contact_id);

        if (!$contact) {
            return $this->sendResponse(false, __('strings.invalid_user'));
        }

        if ($request->category_id) {
            if ($request->category_id!=$contact->category_id) {
                return $this->sendResponse(false, __('strings.invalid_credential'));
            }
        }

        if (!$user->is_active || !$contact->is_active) {
            return $this->sendResponse(false, __('strings.account_disabled'));
        }

        if ($user->is_app_user==0 || $user->is_app_user=='') {
            $user->is_app_user = 1;
        }
        $user->device_token = $request->device_token;
        $user->device_type = $request->device_type;
        $user->save();

        $contact_address = ContactAddress::where('contact_id', $user->contact_id)->get();
        //$contact_additional_person = ContactAdditionalPerson::where('contact_id',$user->contact_id)->get();

        $is_profile_fullfilled = 1;

        if (empty($contact->salutation) && empty($contact->mobile1) && empty($contact->nationality) && empty($contact->designation) && empty($contact->gender) && empty($contact->middle_name) && empty($contact_address->street_address1) && empty($contact_address->city) && empty($contact_address->state) && empty($contact_address->country)) {
            $is_profile_fullfilled = 0;
        }

        $userTokens = $user->tokens;
        foreach ($userTokens as $token) {
            $token->revoke();
        }
       /*  $accessTokens = $user->accessTokens()->get();
       foreach ($accessTokens as $key => $accessToken) {
           //dd($accessToken['id']);
            \DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);
            $accessToken->revoke();
         } */
        /*
        if($accessToken){
           \DB::table('oauth_refresh_tokens')
           ->where('access_token_id', $accessToken->id)
           ->update([
               'revoked' => true
           ]);
           $accessToken->revoke();
        } */

        $accessTokenObject = $user->createToken('Login');
        $data = [
            'data' => $user,
            'is_profile_fullfilled' => $is_profile_fullfilled,
            'token' => $accessTokenObject->accessToken,
        ];
        return $this->sendResponse(true, 'success', $data);
    }

    /** Send password link to user email */
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

    /** Reset password by user using email link */
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
        if (Str::lower($user->email) != Str::lower($password_reset->email)) {
            return $this->sendResponse(false, 'Your requested email & User account registered email is not the same. Please check your email carefully!');
        }
        if (Hash::check($request->password, $user->password)) {
            return $this->sendResponse(false, __('strings.same_old_new_password'));
        }

        $user->password = Hash::make($request->password);
        $user->save();
        PasswordReset::where('email', $request->email)->delete();

        return $this->sendResponse(true, __('strings.password_reset_success'));
    }

    /** Update profile of logged in user */
    public function updateProfile(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = auth()->user();
        if (!$user) {
            return $this->sendResponse(false, 'User not found.');
        }
        $user->name = $request->name;
        $user->save();
        return $this->sendResponse(true, 'success', $user);
    }

    /** Change password of logged in user */
    public function changePassword(Request $request)
    {
        $v = validator($request->all(), [
            'old_password' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = auth()->user();
        if (!$user) {
            return $this->sendResponse(false, 'User not found.');
        }
        if (!Hash::check($request->old_password, $user->password)) {
            return $this->sendResponse(false, __('strings.incorrect_old_password'));
        }
        if (Hash::check($request->password, $user->password)) {
            return $this->sendResponse(false, __('strings.same_old_new_password'));
        }

        $user->password = Hash::make($request->password);
        $user->save();

        return $this->sendResponse(true, 'success');
    }

    /** Logout user */
    public function logout()
    {
        $accessToken = Auth::user()->token();
        \DB::table('oauth_refresh_tokens')
            ->where('access_token_id', $accessToken->id)
            ->update([
                'revoked' => true
            ]);

        $accessToken->revoke();
        Auth::user()->update(['device_token'=>null]);

        /**Update user duo logged in value */
        Auth::user()->update(['is_duo_loggedin' => false]);
        /** */

        return $this->sendResponse(true, 'success');
    }

    /* Get employee list */
    public function getEmployees()
    {
        $contacts = Contact::where('category_id', 3)->get();
        return $this->sendResponse(true, 'success', $contacts);
    }

    /* Get user list except logged in user */
    public function getUsers()
    {
        $book2ski_email = config('constants.book2ski_email');
        $chat_admin_email = config('constants.chat_admin_email');
        $users = User::whereNotIn('email', [$book2ski_email,$chat_admin_email]);
        if (isset($_GET['is_app_user'])) {
            if ($_GET['is_app_user'] === 'Y') {
                $appUser = 1;
            } else {
                $appUser = null;
            }
            $users = $users->where('is_app_user', $appUser);
        }
        if (!isset($_GET['all_users']) && empty($_GET['is_export'])) {
            $users = $users->where('id', '!=', auth()->user()->id);
        }
        if (isset($_GET['expenditure'])) {
            $users = $users->whereHas('contact_detail', function ($q) {
                $q->where('category_id', '<>', '1');
            })->orWhereHas('role_detail', function ($q) {
                $q->whereIn('code', ['A','SA','FM','BM']);
            });
        }
        $users = $users->with(['contact_detail'=>function ($query) {
            $query->select('id', 'mobile1', 'mobile2', 'nationality', 'gender', 'designation', 'dob', 'profile_pic');
        }])->with(['role_detail'=>function ($query) {
            $query->select('id', 'name');
        }])
        ->orderBy('id', 'desc')
        ->get();

        if (!empty($_GET['is_export'])) {
            return Excel::download(new UserExport($users->toArray()), 'User.csv');
        }

        return $this->sendResponse(true, 'success', $users);
    }

    /* Add new user */
    public function addUser(Request $request)
    {
        $v = validator($request->all(), [
            'contact_id' => 'required|integer|min:1',
            'role' => 'required|integer|min:1',
           // 'password' => 'required|min:6'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $contact = Contact::find($request->contact_id);
        if (!$contact) {
            return $this->sendResponse(false, 'Contact not found');
        }
        $checkEmail = User::where('email', $contact->email)->count();
        if ($checkEmail) {
            return $this->sendResponse(false, __('strings.user_already_exist'));
        }

        $input = [];
        $input['email'] = $contact->email;
        $password = uniqid();
        $input['password'] = Hash::make($password);
        $input['role'] = $request->role;
        $input['contact_id'] = $request->contact_id;
        $input['name'] =  $contact->salutation.' '.$contact->first_name.' '.$contact->last_name;
        $user = User::create($input);
        // $user->notify(new SendPassword($password));
        $user->notify((new SendPassword($password))->locale($user->language_locale));

        /**Add crm user action trail */
            if ($user) {
                $action_id = $user->id; //user id
                $action_type = 'A'; //A = Add
                $module_id = 5; //module id base module table
                $module_name = "Users"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        $alert_details['name'] = $input['name'];
        $alert_details['email'] = $input['email'];
        $alert_details['created_admin_name'] = auth()->user()->name?auth()->user()->name:null;
        $alert_details['ip'] = $request->ip();
        $alert_details['created_at'] = $user->created_at;
        $alert_details['role_name'] = Role::find($request->role)->name;
        /**Function for send admin alerts when other admin is added */
        $this->sendAdminAlert($alert_details);

        return $this->sendResponse(true, __('strings.user_created_success'));
    }

    /*   API for Customer Registration in Application side */
    public function app_registration(Request $request)
    {
        $v = validator($request->all(), [
            'first_name' => 'required|max:191',
            'last_name' => 'required|max:191',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
            'device_token' => 'required|max:200',
            'device_type' => 'in:A,I'
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
        if (!$contact) {
            $input_contact['QR_number'] = mt_rand(100000000, 999999999);
            $contact = Contact::create($input_contact);
        } else {
            $contact->update($input_contact);
        }

        $contact = Contact::find($contact->id);
        $contact_address = ContactAddress::where('contact_id', $contact->id)->first();
        // $contact_additional_person = ContactAdditionalPerson::where('contact_id',$contact->id)->get();

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
        $input['device_token'] = $request->device_token;
        $input['device_type'] = $request->device_type;
        $input['name'] =  $contact->salutation.' '.$contact->first_name.' '.$contact->last_name;
        $user = User::create($input);
        $user = User::where('email', $user->email)->with('contact_detail')
        ->first();

        $contact_name = $contact->first_name;
        $data = [
            'data' => $user,
            'is_profile_fullfilled' => $is_profile_fullfilled
        ];

        // $user->notify(new EmailVerify($reset_token,$contact_name));
        $user->notify((new EmailVerify($reset_token,$contact_name))->locale($user->language_locale));
        
        return $this->sendResponse(true, __('strings.user_created_success_send_email_verification'), $data);
    }

    /* For User Email Verified after click verify link in email */
    public function emailVerify($token)
    {
        $token_reset = User::where('email_token', $token)->first();
        if (!$token_reset) {
            return view('customer/email_verified_failed');
        }
        $token_reset->email_token = '';
        $token_reset->is_verified = 1;
        $token_reset->save();
        // $token_reset->notify(new AccountConfirm($token_reset));
        $token_reset->notify((new AccountConfirm($token_reset))->locale($token_reset->language_locale));
        
        return view('customer/email_verified_success');
    }

    /* Send Temporary password to user's email */
    public function sendPassword(Request $request)
    {
        $v = validator($request->all(), [
             'user_id' => 'required|integer|min:1',
         ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return $this->sendResponse(false, 'User not found');
        }
        $password = uniqid();
        $user->password = Hash::make($password);
        $user->save();
        // $user->notify(new SendPassword($password));
        $user->notify((new SendPassword($password))->locale($user->language_locale));
        
        return $this->sendResponse(true, __('strings.password_sent_success'));
    }

    /* Update user's role */
    public function updateUserRole(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|integer|min:1',
            'role' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = User::find($request->user_id);
        if (!$user) {
            return $this->sendResponse(false, 'User not found');
        }
        $user->role = $request->role;
        $user->save();

        /**Add crm user action trail */
            if ($user) {
                $action_id = $user->id; //user id
                $action_type = 'U'; //U = Updated
                $module_id = 5; //module id base module table
                $module_name = "Users"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.role_updated_success'));
    }

    /* Delete user  */
    public function deleteUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->sendResponse(false, 'User not found');
        }
        $contact = Contact::find($user->contact_id);

        /**Add crm user action trail */
            if ($user) {
                $action_id = $user->id; //user id
                $action_type = 'D'; //D = Deleted
                $module_id = 5; //module id base module table
                $module_name = "Users"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        $user->delete();
        if ($contact) {
            $contact->delete();
        }
        return $this->sendResponse(true, __('strings.user_deleted_success'));
    }

    /* Change status of user */
    public function changeStatus(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|integer|min:1',
            'status' => 'boolean',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $user = User::find($request->user_id);
        if (!$user) {
            return $this->sendResponse(false, 'User not found');
        }
        $user->is_active = $request->status;
        $user->save();

        /**Add crm user action trail */
            if ($user) {
                $action_id = $user->id; //user id

                if($request->status)
                $action_type = 'ACS'; //ACS = Active Change Status
                else
                $action_type = 'DCS'; //DCS = Deactive Change Status

                $module_id = 5; //module id base module table
                $module_name = "Users"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
        /**End manage trail */

        if (!$request->status) {
            $user = User::where('id', $request->user_id)->first();
            if ($user) {
                $userTokens = $user->tokens;
                foreach ($userTokens as $token) {
                    $token->revoke();
                }
                $user->update(['device_token'=>null]);
            }
        }
        return $this->sendResponse(true, __('strings.status_change_success'));
    }

    /* Change Notification status of user */
    public function changeNotificationStatus(Request $request)
    {
        $v = validator($request->all(), [
            'user_id' => 'required|integer|min:1',
            'status' => 'boolean',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $user = User::where('contact_id', $request->user_id)->first();
        if (!$user) {
            return $this->sendResponse(false, 'User not found');
        }
        $user->is_notification = $request->status;
        $user->save();
        return $this->sendResponse(true, __('strings.status_change_success'));
    }

    /* Gwt user profile status */
    public function getUserProfileStatus($id)
    {
        $contact = Contact::find($id);
        $user_email_verify_status = User::where('contact_id', $id)->get('is_verified');

        $contact_address = ContactAddress::where('contact_id', $id)->get();
        $contact_additional_person = ContactAdditionalPerson::where('contact_id', $id)->get();

        $is_profile_fullfilled = 1;

        if (empty($contact->salutation) && empty($contact->mobile1) && empty($contact->nationality) && empty($contact->designation) && empty($contact->gender) && empty($contact->middle_name) && empty($contact_address->street_address1) && empty($contact_address->city) && empty($contact_address->state) && empty($contact_address->country) && empty($contact_additional_person->name)) {
            $is_profile_fullfilled = 0;
        }

        $data = [
            'user_email_verify_status' => $user_email_verify_status[0]->is_verified,
            'is_profile_fullfilled' => $is_profile_fullfilled
        ];
        return $this->sendResponse(true, __('success'), $data);
    }

    public function userNotificationList(Request $request)
    {
        $v = validator($request->all(), [
            'position' => 'required|integer|min:1'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user_id = auth()->user()->contact_id;

        $data = $this->notification_list($user_id, $request->position);
        if (count($data) > 0) {
            return $this->sendResponse(true, __('success'), $data);
        } else {
            return $this->sendResponse(false, __('You have an empty notification list'));
        }
    }

    /** Update device token of logged in user */
    public function updateDeviceToken(Request $request)
    {
        $v = validator($request->all(), [
            'device_token' => 'required',
         ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $user = auth()->user();
        if (!$user) {
            return $this->sendResponse(false, 'User not found.');
        }
        $user->device_token = $request->device_token;
        $user->save();
        return $this->sendResponse(true, 'device token updated successfully', $user);
    }

    /*   API for Instructor Registration via register code */
    public function instructorRegistration(Request $request)
    {
        $v = validator($request->all(), [
            'email' => 'required|email',
            'invitation_code' => 'required|max:191',
            'password' => 'required|confirmed|min:6',
            'device_token' => 'max:200',
            'device_type' => 'in:A,I'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invitation_code = $request->invitation_code;
        $current_datetime = date('Y-m-d H:i:s');

        $user = User::where('email', $request->email)->with('contact_detail')->first();

        if (!$user) {
            return $this->sendResponse(false, __('strings.invalid_email'));
        }
        if ($user->register_code != $invitation_code) {
            return $this->sendResponse(false, __('strings.invalid_invitation_code'));
        }
        if ($user->register_code_expire_at < $current_datetime) {
            return $this->sendResponse(false, __('strings.invitation_code_expire'));
        }

        $password = $request->password;
        $user->password = Hash::make($password);

        $user->device_token = $request->device_token;
        $user->device_type = $request->device_type;
        $user->register_code = null;
        $user->register_code_expire_at = null;
        $user->register_code_verified_at = date('Y-m-d H:i:s');
        $user->save();
        $userTokens = $user->tokens;
        foreach ($userTokens as $token) {
            $token->revoke();
        }

        $accessTokenObject = $user->createToken('Login');
        $data = [
            'data' => $user,
            'is_profile_fullfilled' => 1,
            'token' => $accessTokenObject->accessToken,
        ];
        return $this->sendResponse(true, __('strings.thankyou_for_register'), $data);
    }
    /*API for get crm admin panel action tarils list */
    public function getCrmTrails(Request $request)
    {
        // \DB::enableQueryLog();minmum
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;

        $trails = CrmUserActionTrail::query();

        /**Search module name base */
        if($request->search){
            $search = $request->search;
            $trails = $trails->where('module_name', 'like', "%$search%");
        }
        /**Search user base */
        if($request->user_id){
            $user_id = $request->user_id;
            $trails = $trails->where('created_by', $user_id);
        }
        /**Search action type base */
        if($request->action_type){
            $action_type = $request->action_type;
            $trails = $trails->where('action_type', $action_type);
        }
        /**Search from date range base */
        if($request->start_date){
            $start_date = $request->start_date;
            $trails = $trails->whereDate('created_at', '>=' , $start_date);
        }
        /**Search to date range base */
        if($request->end_date){
            $end_date = $request->end_date;
            $trails = $trails->whereDate('created_at', '<=' , $end_date);
        }
        $trails_count = $trails->count();
        
        $trails = $trails->with('module_detail')
        ->with('user_detail.contact_detail')
        ->skip($perPage*($page-1))->take($perPage)
        ->orderBy('id','desc')
        ->get();

        foreach ($trails as $key => $trail) {
            $trail->action_details;
        }

        $data = [
            'crm_trails' => $trails,
            'count' => $trails_count
        ];
        // dd(DB::getQueryLog());
        return $this->sendResponse(true, 'success', $data);
    }
    /*API for get crm users list */
    public function getCrmUsersList()
    {
        $roles = Role::pluck('id');
        if($roles){
            $users = User::with('role_detail')
            ->with('contact_detail')
            ->whereIn('role',$roles)
            ->get();
        }else{
            $users = new class{};
        }
        return $this->sendResponse(true, 'success', $users);
    }
}
