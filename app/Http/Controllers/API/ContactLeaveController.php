<?php

namespace App\Http\Controllers\API;
use Excel;
use App\User;
use App\Models\Leave;
use App\Models\Payroll;
use App\Models\Payslip;
use App\Models\Contact;
use App\Models\ContactLeave;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Exports\ContactLeaveExport;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class ContactLeaveController extends Controller
{
    use Functions;

    /* API for Creating Contact Leave information */
    public function createContactLeave(Request $request)
    {
        $v = $this->checkValidation($request);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $input = $request->all();
        if(auth()->user()->type() === 'Instructor') {
            $input['contact_id'] = auth()->user()->contact_id;
            $input['leave_status'] = 'P';
        }
        $input['created_by'] = auth()->user()->id;

        
        $contactleave = ContactLeave::create($input);

        /**Add crm user action trail */
        if($contactleave){
            $action_id = $contactleave->id; //contactleave id
            $action_type = 'A'; //A = Add
            $module_id = 10; //module id base module table
            $module_name = "All Leaves"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        /**Sent notification to admin */
        if(auth()->user()->type() === 'Instructor') {
            $receiver_id = 0;//For admin
            $sender_id = auth()->user()->contact_id;
            $start_date = $request->start_date; 
            $end_date = $request->end_date;
            $body = "Request leave from ".$start_date." to ".$end_date;
            Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>32,'message'=>$body]);
        }
        /**End */

        $data = ContactLeave::with('leave_detail')->find($contactleave['id']);
        return $this->sendResponse(true,__('strings.contact_leave_created_success'),$data);
    }

    /* Check booking validation for given instructor */
    public function checkInstructorBookingValidation(Request $request)
    {
        $v = validator($request->all(), [
         //   'contact_id' => 'required|integer|min:1',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        if(auth()->user()->type() === 'Instructor') {
            $contact_id = auth()->user()->contact_id;
        } else {
            $contact_id = $request->contact_id;
        }

        $start_date= $request->start_date;
        $end_date= $request->end_date;

        $booking_processes_ids = BookingProcessCustomerDetails::
        Where(function($query) use($start_date){
            $query->where('start_date', '<=', $start_date);
            $query->where('end_date', '>=', $start_date);
        })
        ->orWhere(function($query) use($end_date){
            $query->where('start_date', '<=', $end_date);
            $query->where('end_date', '>=', $end_date);
        })
        ->pluck('booking_process_id')->toArray();
        
        $booking_processes_ids_main = BookingProcessInstructorDetails::whereIn('booking_process_id',$booking_processes_ids)->where('contact_id',$contact_id)->pluck('booking_process_id')->toArray();

        $booking_processes_ids_main = array_unique($booking_processes_ids_main);

        $booking_numbers = BookingProcesses::whereIn('id', $booking_processes_ids_main)->pluck('booking_number');

        return $this->sendResponse(true,'Booking availability',$booking_numbers);

    }

    /* API for Updating Contact Leave information */
    public function updateContactLeave(Request $request,$id)
    {
        $v = $this->checkValidation($request);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $contact_leave = ContactLeave::find($id);
        if (!$contact_leave) return $this->sendResponse(false,'Leave not found');

        $contact_id = $contact_leave['contact_id'];
        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        $input['is_paid'] = $request->leave_status === 'R' ? 'N' : 'Y';
        $contact_leave->update($input);

        if($request->leave_status) {
            $user = User::where('contact_id',$contact_id)->first();

            if($user && $user['is_notification']) {
                $type = $request->leave_status === 'A' ? 6 : 7;
                $status_text = $request->leave_status === 'A' ? 'Approved' : 'Rejected';
                $title = "Leave Request ".$status_text;
                $body = $request->leave_status === 'A' ? __('strings.leave_approved') : __('strings.leave_rejected');
                $data = [];
                Notification::create([
                    'sender_id' => auth()->user()->id,
                    'receiver_id' => $user->contact_id,
                    'type' => $type,
                    'status' => $request->leave_status,
                    'message' => $body,
                    'reject_reason' => $request->reject_reason
                ]);
                $this->push_notification($user['device_token'],$user['device_type'],$title,$body,$type,$data);
            }
        }

        /**Add crm user action trail */
        if ($contact_leave) {
            $action_id = $contact_leave->id; //contact_leave id
            $action_type = 'U'; //U = Updated
            $module_id = 10; //module id base module table
            $module_name = "All Leaves"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true,__('strings.contact_leave_updated_success'),$contact_leave);
    }

    /* API for Updating  Leave Paid status */
    public function updatePaidStatus(Request $request,$id)
    {
        $v = validator($request->all(), [
              'is_paid' => 'required|in:Y,N',
            ]);
        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $contact_leave = ContactLeave::find($id);
        if (!$contact_leave) return $this->sendResponse(false,'Leave not found');
        $contact_leave->is_paid = $request->is_paid;
        $contact_leave->save();
        
        /**Add crm user action trail */
        if ($contact_leave) {
            $action_id = $contact_leave->id; //contact_leave id
            if($request->is_paid=='Y'){
                $action_type = 'P'; //P = Paid
            }else{
                $action_type = 'NP'; //NP = Not Paid
            }
            $module_id = 10; //module id base module table
            $module_name = "All Leaves"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true,__('strings.contact_leave_updated_success'),$contact_leave);
    }

    /* API for Updating Contact Leave information */
    public function viewContactLeave($id)
    {
        $contact_leave = ContactLeave::with(['contact_detail'=>function($query){
            $query->select('id','salutation','first_name','middle_name','last_name','category_id');
        }])->with('leave_detail')->find($id);

        if (!$contact_leave) return $this->sendResponse(false,'Leave not found');
        
        return $this->sendResponse(true,'success',$contact_leave);
    }

    /* API for Deleting Contact Leave information */
    public function deleteContactLeave($id)
    {
        $contact_leave = ContactLeave::find($id);
        if (!$contact_leave) return $this->sendResponse(false,'Leave not found');
        
        /**Add crm user action trail */
        if ($contact_leave) {
            $action_id = $contact_leave->id; //contact_leave id
            $action_type = 'D'; //D = Deleted
            $module_id = 10; //module id base module table
            $module_name = "All Leaves"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        $contact_leave->delete();

        return $this->sendResponse(true,__('strings.contact_leave_deleted_success'));
    }

    /* API for Contact Leave List */
    public function getContactLeavesList()
    {
        $contact_leave = ContactLeave::with(['leave_detail']);

        if(auth()->user() && auth()->user()->type() === 'Instructor') {
            $contact_leave = $contact_leave->where('contact_id',auth()->user()->contact_id);
        } else {
            $contact_leave = $contact_leave->with(['contact_detail'=>function($query){
                $query->select('id','salutation','first_name','middle_name','last_name');
            }]);
        }

        if(!empty($_GET['perPage']) && !empty($_GET['page'])){
            $perPage = $_GET['perPage'];
            $page = $_GET['page'];
            $contact_leave = $contact_leave->skip($perPage*($page-1))->take($perPage);
        }

        $contact_leave = $contact_leave->latest()->get();

        if(!empty($_GET['is_export'])){
            return Excel::download(new ContactLeaveExport($contact_leave->toArray()), 'ContactLeave.csv');  
        }
        return $this->sendResponse(true,'List of all leaves',$contact_leave);
    }

    public function getLeaves()
    {
        $Leaves = Leave::where('is_active',true)->get();
        return $this->sendResponse(true,'success',$Leaves);
    }

    /* Check validation for adding/updating contact leave */
    public function checkValidation($request)
    {
        $v = validator($request->all(), [
          //  'contact_id' => 'required|integer|min:1',
            'leave_id' => 'required|integer|min:1',
            'start_date'=>'date_format:Y-m-d',
            'end_date'=>'date_format:Y-m-d',
            'no_of_days'=> 'required',
            'reason'=>'required|max:255',
            'leave_status' => 'nullable|in:A,R',
            'is_paid'=>'nullable|in:Y,N',
            'reject_reason'=>'nullable|max:250',
            'description'=>'nullable|max:500',
          ]);
        return $v;
    }

    /**Cancel contact leave */
    public function cancelContactLeave($id)
    {
        $contact_id = auth()->user()->contact_id;
        $user = Contact::find($contact_id);
        $is_instructor = false;
        
        $contact_leave = ContactLeave::query();
        
        if($user->category_id){
            if ($user->isType('Instructor')) {
                $is_instructor = true;
                $contact_leave = $contact_leave->where('contact_id', $contact_id);
            }
        }

        $contact_leave = $contact_leave->where('leave_status', 'A')->where('id',$id)->first();

        if (!$contact_leave) return $this->sendResponse(false,'Leave not found');
        
        if(!$is_instructor){
            $contact_id = $contact_leave->contact_id;
        }

        $month[] = date('m',strtotime($contact_leave->start_date));
        $month[] = date('m',strtotime($contact_leave->end_date));
        $month = array_unique($month);

        $year[] = date('Y',strtotime($contact_leave->start_date));
        $year[] = date('Y',strtotime($contact_leave->end_date));
        $year = array_unique($year);

        foreach($year as $y){
            foreach($month as $m){
                $payroll = Payroll::where('month', $m)->where('year', $y)->first();
                if($payroll){
                    $payslip_exist = Payslip::where('payroll_id',$payroll->id)->where('contact_id',$contact_id)->count();
                    if($payslip_exist){
                        return $this->sendResponse(false,__('strings.contact_leave_is_counted_payroll'));
                    }
                }
            }
        }

        $contact_leave->leave_status = 'C';//Cancel leave
        $contact_leave->save();

        /**Sent notification to admin */
        if(auth()->user()->type() === 'Instructor') {
            $receiver_id = 0;
            $sender_id = auth()->user()->contact_id;
            $body = "Canceled leave from ".$contact_leave->start_date." to ".$contact_leave->end_date;
            Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>32,'message'=>$body]);
        }
        /**End */

        return $this->sendResponse(true,__('strings.contact_leave_cancel'));
    }
}
