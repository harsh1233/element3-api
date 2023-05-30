<?php

namespace App\Http\Controllers\API\Masters;

use App\User;
use App\Models\Contact;
use App\Models\SalaryGroup;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\ContactBankDetail;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class SalaryGroupController extends Controller
{
    use Functions;

    /** Get all Salary Groups */
    public function getSalaryGroups()
    {
        $salaryGroups = SalaryGroup::latest()->get();
        return $this->sendResponse(true, 'success', $salaryGroups);
    }

    /** Create new Salary Group */
    public function createSalaryGroup(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'description' => 'required|max:250',
            'salary_type' => 'required|in:FM,FD,H',
            'amount' => 'required|numeric|min:0',
            'sum_per_extra_hour' => 'required_if:salary_type,H|nullable|numeric|min:0',
            'paid_sick_leave' => 'required|bool',
            'paid_vacation_leave' => 'required|bool',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $salaryGroup = SalaryGroup::create($input);

        /**Add crm user action trail */
        if ($salaryGroup) {
            $action_id = $salaryGroup->id; //salary group id
            $action_type = 'A'; //A = Add
            $module_id = 13; //module id base module table
            $module_name = "Salary Groups"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, 'success', $salaryGroup);
    }

    /** Update Salary Group */
    public function updateSalaryGroup(Request $request, $id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'description' => 'required|max:250',
            'salary_type' => 'required|in:FM,FD,H',
            'amount' => 'required|numeric|min:0',
            'sum_per_extra_hour' => 'required_if:salary_type,H|nullable|numeric|min:0',
            'paid_sick_leave' => 'required|bool',
            'paid_vacation_leave' => 'required|bool',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $salaryGroup = SalaryGroup::find($id);
        if (!$salaryGroup) {
            return $this->sendResponse(false, 'Salary Group not found');
        }
        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        $salaryGroup->update($input);
        
        /**Add crm user action trail */
        if ($salaryGroup) {
            $action_id = $salaryGroup->id; //salary group id
            $action_type = 'U'; //U = Updated
            $module_id = 13; //module id base module table
            $module_name = "Salary Groups"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, 'success', $salaryGroup);
    }

     /* View Salary Group with contact list */
     public function viewSalaryGroup($id)
     {
         $salary_group = SalaryGroup::find($id);
 
         if (!$salary_group) return $this->sendResponse(false,__('strings.salary_group_not_foud'));
         
         return $this->sendResponse(true,'success',$salary_group);
     }

    /** delete Salary Group */
    public function deleteSalaryGroup($id)
    {
        $SalaryGroup = SalaryGroup::find($id);
        if (!$SalaryGroup) {
            return $this->sendResponse(false, 'Salary Group not found');
        }
        
        /**Add crm user action trail */
        if ($SalaryGroup) {
            $action_id = $SalaryGroup->id; //salary group id
            $action_type = 'D'; //D = Deleted
            $module_id = 13; //module id base module table
            $module_name = "Salary Groups"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        $SalaryGroup->delete();
        return $this->sendResponse(true, 'success', $SalaryGroup);
    }

    /** Salary Group Wise Contacts List */
    public function getSalaryGroupWiseContacts($id)
    {   
        $salaryGroup=SalaryGroup::find($id);
        if (!$salaryGroup) {
            return $this->sendResponse(false, 'Salary Group not found');
        }

        $contacts_ids=ContactBankDetail::where('salary_group',$id)->pluck('contact_id');

        $contacts=Contact::whereIn('id',$contacts_ids)->with('category_detail')
        ->with('difficulty_level_detail.difficulty_level_detail')
        // ->with(['languages'=>function($q){
        //     $q->with('language:id,name');
        // }])
        //->with('bank_detail.salary_group_detail:id,name')
        // ->with(['user_detail'=>function($q){
        //         $q->select('id','contact_id','register_code_verified_at');
        // }])
        ->orderBy('id', 'desc')->get();

        //Contact Wise Confirm Booking Count
        foreach($contacts as $contact)
        {
            $bookingList = BookingProcessInstructorDetails::where('contact_id', '=', $contact->id)->where('is_course_confirmed','1')->get();
            $bookingcount = $bookingList->count();

            $contact['confirm_booking_count'] = $bookingcount; 
        }

        return $this->sendResponse(true,'success',$contacts);

    } 

    /* Change Instructor Salary Group */
    public function changeInstructorSalaryGroup(Request $request)
    {
        //Validation For Requests
        $v= validator($request->all(),[
            'contact_id' => 'required|integer',
            'salary_group' =>'required|integer',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        //Check For Salary Group Exits or not
        $salaryGroup=SalaryGroup::where('id',$request->salary_group);
        if (!$salaryGroup) {
            return $this->sendResponse(false, 'Salary Group not found');
        }

        $salary_group=$request->salary_group;
        $contact_id=$request->contact_id;
        $contact=Contact::where('id', $contact_id)->first();
        
        //Check For Contact Exits or not
        if ($contact) {
           
            //Upadate Salary Group In Contact Bank Detail
            ContactBankDetail::where('contact_id', $contact_id)->update([
                'salary_group'=>$salary_group
            ]); 

            //Send push notification for instructor
            if($contact->user_detail->type() === 'Instructor'){
                $user = $contact->user_detail()->first();

                if ($user) {
                    $sender_id = auth()->user()->id;
                    $receiver_id = $user['contact_id'];
                
                    $type = 26;
                    $title = __('notifications.salarygroup_updated_title');
                    $body = __('notifications.salarygroup_updated');
                    $data = ['salary_group_id'=>$salary_group];

                    $notification = Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>$type,'message'=>$body]);
        
                        if ($user['is_notification'] == 1) {
                            if (!empty($user['device_token'])) {
                                SendPushNotification::dispatch($user['device_token'], $user['device_type'], $title, $body, $type, $data);

                                \Log::info('Salary group : sender_id'.$sender_id.' device_token: '.$user['device_token'].' title '.$title.' body '.$body.' salary group id '.$data['salary_group_id']);
                            }
                        }
                }
            }
            //end push notification   
       }else {
           return $this->sendResponse(false, 'Contact not found');
       }

        return $this->sendResponse(true,__('strings.salary_group_update_success'));

    }

}
