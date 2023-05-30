<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\LeaveManagement\LeaveMst;

class LeaveManagementController extends Controller
{
    use Functions;

    /* create leave request */
    public function createLeaveRequest(Request $request)
    {
        $v = validator($request->all(), [
            'subject' => 'required|max:250',
            'description' => 'required',
            'date' => 'nullable|date_format:Y-m-d',
            'booking_id' => 'required|integer'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $input['user_id'] = auth()->user()->id;
        $leave = LeaveMst::create($input);

        return $this->sendResponse(true,__('strings.leave_request_created_success'),$leave);
    }

    /* List of leave request */
    public function listLeaveRequest(Request $request)
    {   
        $v = validator($request->all(), [
            'booking_id' => 'required|integer',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $booking_lists = LeaveMst::query();

        if($request->booking_id) {
            $booking_lists = $booking_lists->where('booking_id',$request->booking_id);
            //if($booking_lists->isEmpty()) return $this->sendResponse(false,'This Booking Id does not have any requests');
        }
        
        $booking_lists = $booking_lists->get();

        $page = $request->page ?? 1;
        $perPage = $request->perPage ?? 20;
        $leaveRequestList = LeaveMst::select('id','subject','description','status','booking_id','date','created_at')
        ->where('booking_id',$request->booking_id)
        ->where('user_id',auth()->user()->id)
        ->skip($perPage*($page-1))->take($perPage)
        ->orderBy('id','desc')
        ->get();

        return $this->sendResponse(true,__('strings.leave_request_list'),$leaveRequestList);
    }

    /* List of leave request CRM dashboard Running Course */
    public function LeaveRequestListDashboard(Request $request)
    {   
        
        //Get Ongoing Booking Ids
        $current_date = date('Y-m-d H:i:s');
        $booking_processes_ids = BookingProcessCourseDetails::where('StartDate_Time', '<=', $current_date)
        ->where('EndDate_Time', '>=', $current_date)
        ->pluck('booking_process_id');

        $booking_processes_ids = BookingProcesses::whereIn('id', $booking_processes_ids)->where('is_trash',0)->pluck('id');

        //Get Request List 
        $page = $request->page ?? 1;
        $perPage = $request->perPage ?? 20;
        $leaveRequestList = LeaveMst::select('id','user_id','subject','description','status','booking_id','date','created_at')
        ->whereIn('booking_id',$booking_processes_ids)
        ->with('user_detail')
        ->with('booking_detail.course_detail.course_data')
        ->orderBy('id','desc');
        
        //Get Counts
        $leave_request_counts = $leaveRequestList->count();

        $leaveRequestList = $leaveRequestList->skip($perPage*($page-1))->take($perPage)->get();

        $data = [
            'leave_requests' => $leaveRequestList,
            'count' => $leave_request_counts,
        ];

        return $this->sendResponse(true,__('strings.leave_request_list'),$data);
    }

    /* API for change leave status */
    public function changeRequestStatus(Request $request)
    {
        $v = validator($request->all(), [
            'status' => 'in:A,R',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        $leave_mst = LeaveMst::find($request->id);
        if(!$leave_mst)return $this->sendResponse(false,'This Leave Request is not available');
        $leave_mst->status = $request->status;
        $leave_mst->update();
        if($leave_mst->user_detail) {
            $user_detail = $leave_mst->user_detail;
            $type = $request->status === 'A' ? 6 : 7;
            $status_text = $request->status === 'A' ? 'Approved' : 'Rejected';
            $title = "Leave Request ".$status_text;
            $body = $request->status === 'A' ? "Congratulations! Your leave request is approved by our administrator" : "Your leave request is rejected by our administrator";
            $data = ['subject'=>$leave_mst->subject,'description'=>$leave_mst->description,'status'=>$leave_mst->status,'date'=>$leave_mst->date,'booking_id'=>$leave_mst->booking_id];
            $this->push_notification($user_detail['device_token'],$user_detail['device_type'],$title,$body,$type,$data);
        }
        return $this->sendResponse(true,__('strings.change_leave_status_success'));
    }
}
