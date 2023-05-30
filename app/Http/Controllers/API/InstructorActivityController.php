<?php

namespace App\Http\Controllers\API;

use App\User;
use Carbon\Carbon;
use App\Models\Contact;
use Illuminate\Http\Request;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\BookingProcess\BookingPayment;
use App\Models\InstructorActivity\InstructorActivity;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\InstructorActivity\InstructorActivityComment;
use App\Models\BookingProcess\BookingProcessInstructorDetails;
use App\Models\InstructorActivity\InstructorActivityTimesheet;
use App\Models\BookingProcess\BookingParticipantsAttendance;
use App\Models\BookingProcess\BookingProcessCustomerDetails;

class InstructorActivityController extends Controller
{
    use Functions;
    
    /** Activity start and stop with break */
    public function create(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|integer',
            'activity_type' => 'required|in:AS,AE,BS,BE',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $input = [];
        $activity_type = $request->activity_type;
        $booking_id = $request->booking_id;
        $input['booking_id'] = $booking_id;
        $input['activity_type'] = $activity_type;
        if (auth()->user()->type() === 'Instructor'){
            $input['activity_date'] = date('Y-m-d');
            $input['activity_time'] = date('H:i:s');
            $input['instructor_id'] = auth()->user()->id;
        }else{
            $input['activity_date'] = $request->activity_date;
            $input['activity_time'] = $request->activity_time;
            $user = User::where('contact_id',$request->instructor_id)->first();
            $input['instructor_id'] = $user['id'];
        }
        
        $input['created_by'] = auth()->user()->id;

        $activity = InstructorActivity::create($input);
        
        //Add/Update instructor timesheet 
        if (auth()->user()->type() === 'Instructor'){
            $this->updateTimesheet($input);
        }
        else
        {
            $this->updateTimesheet1($input);
        }

        $message = '';
        switch ($activity_type) {
            case 'AS':
                $message = __('strings.activity_started');
                break;
            case 'AE':
                $message = __('strings.activity_stoped');
                break;    
            case 'BS':
                $message = __('strings.break_started');
                break;
            case 'BE':
                $message = __('strings.break_stoped');
                break;
            default:
                break;
        }

        return $this->sendResponse(true,$message,$activity);
    }

    /** Add/Update instructor timesheet */    
    public function updateTimesheet($data)
    {
        $current_time = date('H:i:s');
        $timesheet = InstructorActivityTimesheet::where('booking_id',$data['booking_id'])->where('instructor_id',$data['instructor_id'])->where('activity_date',$data['activity_date'])->first();
        if(!$timesheet) {
            $start_time = date('H:i:s');
            $insert_data = array_merge($data,['start_time'=>$start_time,'current_time'=>$current_time]);
            InstructorActivityTimesheet::create($insert_data);
            return;
        }

        $total_duration = Carbon::parse($current_time)->diffInSeconds(Carbon::parse($timesheet->current_time));
        $total_duration = gmdate('H:i:s', $total_duration);
        
        switch ($data['activity_type']) {
            case 'AS':
                $timesheet->start_time = $current_time;
                break;
            case 'BS':
                $timesheet->total_activity_hours = $this->addTime($total_duration,$timesheet->total_activity_hours);
                break;
            
            case 'BE':
                $timesheet->total_break_hours = $this->addTime($total_duration,$timesheet->total_break_hours);
                break;
            
            case 'AE':
                $timesheet->total_activity_hours = $this->addTime($total_duration,$timesheet->total_activity_hours);
                $timesheet->end_time = $current_time;
                break;
            
            default:
                break;
        }
        $timesheet->current_time = $current_time;
        $timesheet->save();
        Log::info($data['activity_type']);
        Log::info($timesheet);
    }

    /** New Add/Update instructor timesheet */    
    public function updateTimesheet1($data)
    {
        $current_time = $data['activity_time'];
        $timesheet = InstructorActivityTimesheet::where('booking_id',$data['booking_id'])->where('instructor_id',$data['instructor_id'])->where('activity_date',$data['activity_date'])->first();
        if(!$timesheet) {
            $start_time = date('H:i:s');
            $insert_data = array_merge($data,['start_time'=>$start_time,'current_time'=>$current_time]);
            InstructorActivityTimesheet::create($insert_data);
            return;
        }
        $total_duration = Carbon::parse($current_time)->diffInSeconds(Carbon::parse($timesheet->current_time));
        $total_duration = gmdate('H:i:s', $total_duration);
        $timesheet->current_time = $data['activity_time'];
        // dd($total_duration);
        switch ($data['activity_type']) {
            case 'AS':
            $timesheet->start_time = $current_time;
            if($timesheet['end_time']!='00:00:00' && $timesheet->total_break_hours!='00:00:00')
            {
                $timesheet->total_activity_hours = date("H:i:s",(strtotime($timesheet['end_time']) - strtotime($current_time))- strtotime($timesheet->total_break_hours));
            }
            break;

            case 'BS':
            $activity = InstructorActivity::where('booking_id',$data['booking_id'])
                ->where('instructor_id',$data['instructor_id'])
                ->where('activity_date',$data['activity_date'])
                ->where('activity_type','BE')->first();

            $temp_total_break_hours= date("H:i:s",strtotime($activity['activity_time']) - strtotime($data['activity_time']));
            if($timesheet['end_time']!='00:00:00' && $activity['activity_time']!='00:00:00' && $timesheet['start_time']!='00:00:00')
            {
                $timesheet->total_break_hours=$temp_total_break_hours;
                $timesheet->total_activity_hours = date("H:i:s",(strtotime($timesheet['end_time']) - strtotime($timesheet['start_time'])-strtotime($temp_total_break_hours)));
            }
            break;

            case 'BE':
                $activity = InstructorActivity::where('booking_id',$data['booking_id'])
                ->where('instructor_id',$data['instructor_id'])
                ->where('activity_date',$data['activity_date'])
                ->where('activity_type','BS')->first();
                $temp_total_break_hours = date("H:i:s",strtotime($data['activity_time']) - strtotime($activity['activity_time']));
                if(($timesheet['end_time']!='00:00:00' || $activity['activity_time']!='00:00:00') && $timesheet['start_time']!='00:00:00')
                {
                    $timesheet->total_break_hours=$temp_total_break_hours;
                    if($timesheet['end_time']!='00:00:00' && $timesheet['start_time']!='00:00:00')
                    {
                    $timesheet->total_activity_hours = date("H:i:s",(strtotime($timesheet['end_time']) - strtotime($timesheet['start_time'])-strtotime($temp_total_break_hours)));
                    }
                }
            break;

            case 'AE':
                $timesheet->end_time = $current_time;
                if($timesheet->total_break_hours!='00:00:00' || $timesheet['start_time']!='00:00:00')
                {
                $timesheet->total_activity_hours = date("H:i:s",(strtotime($current_time) - strtotime(
                    $timesheet['start_time']))-strtotime($timesheet->total_break_hours));
                }
                else if($timesheet['start_time']!='00:00:00')
                {
                    $timesheet->total_activity_hours = date("H:i:s",(strtotime($current_time) - strtotime(
                        $timesheet['start_time'])));
                }
            break;
            
            default:
            break;
        }
        $timesheet->save();
        Log::info($data['activity_type']);
        Log::info($timesheet);
    }

    /* Add time in previous stored time */
    public function addTime($newTime, $previousTime)
    {
        $secs = strtotime($newTime)-strtotime("00:00:00");
        $result = date("H:i:s",strtotime($previousTime)+$secs);
        return $result;
    }

    /* Get date wise activities for particular booking */
    public function getActivity(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|integer',
            'activity_date' => 'date_format:Y-m-d',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $booking_id = $request->booking_id;
        $activity_date = $request->activity_date;
        $activities = InstructorActivity::select('id','instructor_id','booking_id','activity_type','activity_date','activity_time')->where('booking_id',$booking_id);
        
        if(auth()->user()->type() === 'Instructor'){
            $activities = $activities->where('instructor_id',auth()->user()->id);
        }

        if($request->activity_date) {
            $activities = $activities->where('activity_date',$activity_date);
        }
        $activities = $activities->get();
        $comments = [];
        if(auth()->user()->type() === 'Customer') {
           $instructor_comment =  InstructorActivityComment::select('id','comment_by','description','comment_user_id','created_at')
           ->with(['comment_user_detail'=>function($query){
            $query->select('id','name');
            }])->where('comment_date',$activity_date)->where('booking_id',$booking_id)->where('comment_by','I')->latest()->first();
           if($instructor_comment)  $comments[] = $instructor_comment;

            $customer_comment = InstructorActivityComment::select('id','comment_by','description','comment_user_id','created_at')
           ->with(['comment_user_detail'=>function($query){
                $query->select('id','name');
            }])
           ->where('comment_date',$activity_date)->where('booking_id',$booking_id)->where('comment_user_id',auth()->user()->id)->latest()->first();
           if($customer_comment) $comments[]= $customer_comment;
          $comments =  collect($comments)->sortBy('created_at')->values()->all();
        }
        $data = [
            'activities' => $activities,
            'comments' => $comments
        ];
        return $this->sendResponse(true,__('Activity list'),$data);
    }

    /* Add comment by customer or instructor  */
    public function addComment(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|integer',
            'description' => 'required|max:250',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $input = [];
        $booking_id = $request->booking_id;
        $description = $request->description;
        $input['booking_id'] = $booking_id;
        $input['description'] = $description;
        $input['comment_user_id'] = auth()->user()->id;
        $input['comment_date'] = date('Y-m-d');
        $comment_by = null;
        switch (auth()->user()->type()) {
            case 'Instructor':
                $comment_by = 'I';
                break;

            case 'Customer':
                $comment_by = 'C';
                break;    
            
            default:
                break;
        }
        $input['comment_by'] = $comment_by;
        $input['created_by'] = auth()->user()->id;

        $comment = InstructorActivityComment::create($input);
        return $this->sendResponse(true,__('strings.comment_added'),$comment);
    }

    /* Get all comments / date wise comment for particular booking */
    public function getComments(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|integer',
            'comment_date' => 'date_format:Y-m-d',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $booking_id = $request->booking_id;
        $page = $request->page ?? 1;
        $perPage = $request->perPage ?? 20;
        $comments = InstructorActivityComment::select('id','booking_id','comment_by','comment_user_id','description','comment_date')->where('booking_id',$booking_id);

        if($request->comment_date) {
            $comments = $comments->where('comment_date',$request->comment_date);
        }

        $comments = $comments->with(['comment_user_detail'=>function($query){
            $query->select('id','name');
        }])
        ->skip($perPage*($page-1))->take($perPage)
        ->orderBy('id','desc')
        ->get();

        return $this->sendResponse(true,__('Comment list'),$comments);
    }

    /* Get instructor's total timesheet graph filtered by current week, current month */
    public function getTotalTimesheetGraph(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'integer',
            'filtered_by' => 'in:week,month,custom',
            'month_number' => 'required_if:filtered_by,==,month|numeric|min:1|max:12',
            'start_date' => 'required_if:filtered_by,==,custom|date_format:Y-m-d',
            'end_date' => 'required_if:filtered_by,==,custom|date_format:Y-m-d',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $booking_id = $request->booking_id;

        $timesheet = InstructorActivityTimesheet::where('instructor_id',auth()->user()->id);
        
        if($booking_id) {
            $timesheet = $timesheet->where('booking_id',$booking_id);
        }
        
        if($request->filtered_by) {
            $filtered_by = $request->filtered_by;
            switch ($filtered_by) {
                case 'week':
                    $timesheet = $timesheet->whereBetween('created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()]);
                    break;

                case 'month':
                    $timesheet = $timesheet->whereMonth('created_at', $request->month_number);
                    break;    
                
                case 'custom':
                    $timesheet = $timesheet->whereBetween('created_at', [$request->start_date.' 00:00:00', $request->end_date.' 23:23:59']);
                    break;
            }
        }

        $total_activity_hours = $timesheet->sum(\DB::raw("TIME_TO_SEC(total_activity_hours)"));
        $total_break_hours = $timesheet->sum(\DB::raw("TIME_TO_SEC(total_break_hours)"));
        $data = [
            'total_activity_hours' => gmdate('H:i:s', $total_activity_hours),
            'total_break_hours' => gmdate('H:i:s', $total_break_hours),
        ];
        return $this->sendResponse(true,__('Total activity and break hours'),$data);

    }

    /* Send activity confirmation request to admin by instructor for given day */
    public function activityConfirmationRequest(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|integer',
            'activity_dates' => 'required|array',
            'activity_dates.*' => 'date_format:Y-m-d',
           // 'signature' => 'url',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $booking_id = $request->booking_id;
        $activity_dates = $request->activity_dates;
        $authuser_id = auth()->user()->id;
        $activities = InstructorActivityTimesheet::where('booking_id',$booking_id)->where('instructor_id',$authuser_id)->whereIn('activity_date',$activity_dates);

        if(!$activities->count()) return $this->sendResponse(false,__('Activity not found'));
        $activities->update(['status'=>'IP','signature'=>$request->signature]);
        
        $data = ['activitiY_dates'=>$activity_dates];
        return $this->sendResponse(true,__('strings.activity_confirmation_request_sent'),$data);
    }

    /* Approve/Reject activity confirmation request by admin for given day */
    public function updateConfirmationRequest(Request $request)
    {
        $v = validator($request->all(), [
            'timesheet_id' => 'required|integer',
            'status' => 'required|in:A,R',
            'reject_reason'=>'required_if:status,==,R|max:500'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $status = $request->status;
        $timesheet_id = $request->timesheet_id;
        $activity = InstructorActivityTimesheet::find($timesheet_id);

        if(!$activity) return $this->sendResponse(false,__('Activity not found'));
        $activity->status = $status;
        if($request->reject_reason) {
            $activity->reject_reason = $request->reject_reason;
        }
        $activity->save();

        $course_detail = BookingProcessCourseDetails::with('course_data')->where('booking_process_id',$activity->booking_id)->first();
        $start_date = $end_date = $start_time = $end_time = '';
        if($course_detail) {
            $start_date = $course_detail['start_date'];
            $end_date = $course_detail['end_date'];
            $start_time = $course_detail['start$start_time'];
            $end_time = $course_detail['end_time'];
            $course_name = $course_detail->course_data ? $course_detail->course_data->name : '';
        }
        $data = [
            'status'=>$status,
            'date'=>$activity->activity_date,
            'booking_id'=>$activity->booking_id,
            'start_date'=>$start_date,
            'end_date'=>$end_date,
            'start_time'=>$start_time,
            'end_time'=>$end_time,
            'course_name'=>$course_name,
            
        ];
        
        $status_text = $status === 'A' ? 'Approved' : 'Rejected';
        $formatted_date = date('dS F Y',strtotime($activity->activity_date));
        $title = "Activity Request ".$status_text." for ".$formatted_date;
        
        if($activity->instructor_detail) {
            $user_detail = $activity->instructor_detail;
            $type = $request->status === 'A' ? 9 : 10;
            $body = $status === 'A' ? "Congratulations! Your activity request for ".$formatted_date ." is approved by our administrator" : "Your activity request for".$formatted_date ." is rejected by our administrator";
            $this->push_notification($user_detail['device_token'],$user_detail['device_type'],$title,$body,$type,$data);
        }
        
        return $this->sendResponse(true,$title,$data);
    }

    /* Instructor activity timesheet list */
    public function activityTimesheetList(Request $request)
    {

        if(auth()->user()->type() === 'Instructor') {
            $instructor_id = auth()->user()->id;
        } else {
            $contact_id = $request->instructor_id;
            $user = User::select('id','contact_id')->where('contact_id',$contact_id)->first();
            if(!$user) return $this->sendResponse(false,__('User not found'));
            $instructor_id = $user->id;
        }
        $data = [];
        $activities = InstructorActivityTimesheet::select('id','instructor_id','booking_id','activity_date','start_time','end_time','status','total_activity_hours','total_break_hours','actual_start_time','actual_end_time','actual_hours','reject_reason')
        ->where('instructor_id',$instructor_id);

        if($request->booking_id) {
            $activities = $activities->where('booking_id',$request->booking_id);
        }

        if($request->month) {
            $month = $request->month;
            $activities = $activities->whereMonth('activity_date',$month);
        }
        if($request->year) {
            $year = $request->year;
            $activities = $activities->whereYear('activity_date',$year);
        }

        if($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $activities = $activities->skip($perPage*($page-1))->take($perPage);
        }

        $activities = $activities
        ->with(['booking_detail'=>function($query){
            $query->select('id','booking_number');
            $query->with(['course_detail'=>function($query2){
                $query2->select('id','booking_process_id','course_type','course_id');
                $query2->with(['course_data'=>function($query3){
                    $query3->select('id','name');
                }]);
            }]);
        }])
        ->get();

       //$activities = $activities->groupBy('booking_id')->toArray();
        return $this->sendResponse(true,__('Timesheet list'),$activities);
    }

    /* Instructor booking list */
    public function bookingList(Request $request)
    {
        $v = validator($request->all(), [
            'month' => 'required|numeric|min:1|max:12',
            'year' => 'required|numeric',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        if(auth()->user()->type() === 'Instructor') {
            $instructor_id = auth()->user()->contact_id;
        } else {
            $instructor_id = $request->instructor_id;
        }
        $data = [];
        $booking_ids = BookingProcessInstructorDetails::where('contact_id',$instructor_id)->pluck('booking_process_id');
        $course_detail = BookingProcessCourseDetails::select('booking_process_id','course_id','StartDate_Time','EndDate_Time','course_type')->whereIn('booking_process_id',$booking_ids);
        
        if($request->month) {
            $month = $request->month;
            $course_detail = $course_detail->whereMonth('StartDate_Time',$month);
        }
        if($request->year) {
            $year = $request->year;
            $course_detail = $course_detail->whereYear('StartDate_Time',$year);
        }

        if($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $course_detail = $course_detail->skip($perPage*($page-1))->take($perPage);
        }

        $course_detail = $course_detail->with(['course_data'=>function($q){
            $q->select('id','name');
        }])->get();
        $data = [];
        foreach($course_detail as $cd) {
            if($cd['StartDate_Time'] && $cd['EndDate_Time'] && $cd['course_data'] && $cd['booking_process_id']) {
                $startDateArray = explode(" ",$cd['StartDate_Time']);
                $endDateArray = explode(" ",$cd['EndDate_Time']);
                $start_date = $startDateArray[0];
                $end_date = $endDateArray[0];
                $start_time = $startDateArray[1];
                $end_time = $endDateArray[1];
                $course_name = $cd['course_data']['name'];
                $total_duration = Carbon::parse($end_time)->diffInSeconds(Carbon::parse($start_time));
               // $total_time = gmdate('H:i:s', $total_duration);
                $diff = strtotime($end_date) - strtotime($start_date);
                $days = Carbon::parse($end_date)->diffInDays(Carbon::parse($start_date));
                $total_seconds = ($days+1)*$total_duration;
                $hours = floor($total_seconds / 3600);
                $minutes = floor(($total_seconds / 60) % 60);
                $seconds = $total_seconds % 60;
                $total_time = $hours.":".$minutes.":".$seconds;
                $data[] = [
                    'booking_id' => $cd['booking_process_id'],
                    'course_name' => $course_name,
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'start_time' => $start_time,
                    'end_time' => $end_time,
                    'total_time' => $total_time,
                ];
            }
        }
        return $this->sendResponse(true,__('Booking list'),$data);
    }

    /* Instructor booking list */
    public function loggedTime(Request $request)
    {
        $v = validator($request->all(), [
            'type' => 'required|in:daily,weekly',
            'activity_date' => 'required_if:type,==,daily|date_format:Y-m-d',
            'activity_start_date' => 'required_if:type,==,weekly|date_format:Y-m-d',
            'activity_end_date' => 'required_if:type,==,weekly|date_format:Y-m-d',
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());

        if(auth()->user()->type() === 'Instructor') {
            $instructor_id = auth()->user()->id;
        } else {
            $contact_id = $request->instructor_id;
            $user = User::select('id','contact_id')->where('contact_id',$contact_id)->first();
            if(!$user) return $this->sendResponse(false,__('User not found'));
            $instructor_id = $user->id;
        }

        $activities = InstructorActivityTimesheet::select('id','instructor_id','booking_id','activity_date','total_activity_hours','total_break_hours')
        ->where('instructor_id',$instructor_id);

        if($request->type === 'daily') {
            $activities = $activities->where('activity_date',$request->activity_date);
        } else {
            $activities = $activities->whereBetween('activity_date',[$request->activity_start_date,$request->activity_end_date]);
        }

        if($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $activities = $activities->skip($perPage*($page-1))->take($perPage);
        }

        $activities = $activities
        ->with(['booking_detail'=>function($query){
            $query->select('id','booking_number');
            $query->with(['course_detail'=>function($query2){
                $query2->select('id','booking_process_id','course_type','course_id');
                $query2->with(['course_data'=>function($query3){
                    $query3->select('id','name');
                }]);
            }]);
        }])
        ->get();
        return $this->sendResponse(true,__('Instructor Logged'),$activities);
    }

    /* API for View Activity Timesheet Details */
    public function viewActivityTimesheet($id)
    {
        $instructor_activity_timesheet = InstructorActivityTimesheet::
        /* with(['booking_course_detail'=>function($query){
            $query->select('id','booking_process_id','course_type');
        }]) */
        with(['booking_detail'=>function($query){
            $query->select('id','booking_number');
        }])
        ->with('booking_course_detail.course_data')
        ->find($id);
        $user = User::where('id',$instructor_activity_timesheet->instructor_id)->first();
        $contact = Contact::where('id',$user->contact_id)->select('id','salutation','first_name','middle_name','last_name')->first();

        if (!$instructor_activity_timesheet) {
            return $this->sendResponse(false, 'Instructor Activity Timesheet not found');
        }

        $instructor_activity = InstructorActivity::where('instructor_id',$instructor_activity_timesheet->instructor_id)
        ->where('booking_id',$instructor_activity_timesheet->booking_id)
        ->where('activity_date',$instructor_activity_timesheet->activity_date)->get();

        $instructor_activity_timesheet['instructor'] = $contact;
        $instructor_activity_timesheet['instructor_activity'] = $instructor_activity;
        
        return $this->sendResponse(true, 'success', $instructor_activity_timesheet);
    }

    /* API for Update Activity Timesheet Details */
    public function updateActivityTimesheet(Request $request)
    {
        $v = validator($request->all(), [
            'activitie_id' => 'required|integer',
            'booking_id' => 'required|integer',
            'instructor_id' => 'required|integer',
            'activity_type' => 'required|in:AS,AE,BS,BE',
            'activity_time' => 'required|date_format:H:i:s'
        ]);

        if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
        
        $input = [];
        $activity_type = $request->activity_type;
        $booking_id = $request->booking_id;
        $instructor_id = $request->instructor_id;
        
        $input['activity_time'] = $request->activity_time;
        
        $input['updated_by'] = auth()->user()->id;
        
        $activity = InstructorActivity::find($request->activitie_id);
        
        if (!$activity) {
            return $this->sendResponse(false, 'Instructor Activity not found');
        }

        $activity_update = $activity->update($input);
        
        $input['activity_date'] = $activity->activity_date;
        $input['booking_id'] = $booking_id;
        $input['activity_type'] = $activity_type;
        $input['instructor_id'] = $instructor_id;

        if($activity_update){
            //Add/Update instructor timesheet 
            $update = $this->updateTimesheet1($input);
        }

        $message = '';
        switch ($activity_type) {
            case 'AS':
                $message = __('strings.activity_started');
                break;
            case 'AE':
                $message = __('strings.activity_stoped');
                break;    
            case 'BS':
                $message = __('strings.break_started');
                break;
            case 'BE':
                $message = __('strings.break_stoped');
                break;
            default:
                break;
        }
        return $this->sendResponse(true,$message,$activity);
    }


     /** Activity start and stop with break (offline flow) */
     public function offlineCreate(Request $request)
     {
         $v = validator($request->all(), [
             'booking_id' => 'required|integer',
             'activity_type' => 'required|in:AS,AE,BS,BE',
             'activity_datetime' => 'required|date_format:Y-m-d H:i:s',
         ]);
 
         if ($v->fails()) return $this->sendResponse(false,$v->errors()->first());
         
         $input = [];
         $activity_datetime = explode(" ",$request->activity_datetime);
         $activity_type = $request->activity_type;
         $booking_id = $request->booking_id;
         $input['booking_id'] = $booking_id;
         $input['activity_type'] = $activity_type;
         $input['activity_date'] = $activity_datetime[0];
         $input['activity_time'] = $activity_datetime[1];
         $input['instructor_id'] = auth()->user()->id;
         $input['created_by'] = auth()->user()->id;
 
         $activity = InstructorActivity::create($input);
         
         //Add/Update instructor timesheet 
         $this->updateTimesheetOffline($input);
         
         $message = '';
         switch ($activity_type) {
             case 'AS':
                 $message = __('strings.activity_started');
                 break;
             case 'AE':
                 $message = __('strings.activity_stoped');
                 break;    
             case 'BS':
                 $message = __('strings.break_started');
                 break;
             case 'BE':
                 $message = __('strings.break_stoped');
                 break;
             default:
                 break;
         }
 
         return $this->sendResponse(true,$message,$activity);
     }
 
     /** Add/Update instructor timesheet offline */    
     public function updateTimesheetOffline($data)
     {
         //$current_time = date('H:i:s');
         $current_time = $data['activity_time'];
         $timesheet = InstructorActivityTimesheet::where('booking_id',$data['booking_id'])->where('instructor_id',$data['instructor_id'])->where('activity_date',$data['activity_date'])->first();
         if(!$timesheet) {
             $start_time = $data['activity_time'];
             $insert_data = array_merge($data,['start_time'=>$start_time,'current_time'=>$current_time]);
             InstructorActivityTimesheet::create($insert_data);
             return;
         }
 
         $total_duration = Carbon::parse($current_time)->diffInSeconds(Carbon::parse($timesheet->current_time));
         $total_duration = gmdate('H:i:s', $total_duration);
         
         switch ($data['activity_type']) {
             case 'AS':
                 $timesheet->start_time = $current_time;
                 break;
             case 'BS':
                 $timesheet->total_activity_hours = $this->addTime($total_duration,$timesheet->total_activity_hours);
                 break;
             
             case 'BE':
                 $timesheet->total_break_hours = $this->addTime($total_duration,$timesheet->total_break_hours);
                 break;
             
             case 'AE':
                 $timesheet->total_activity_hours = $this->addTime($total_duration,$timesheet->total_activity_hours);
                 $timesheet->end_time = $current_time;
                 break;
             
             default:
                 break;
         }
         $timesheet->current_time = $current_time;
         $timesheet->save();
         Log::info($data['activity_type']);
         Log::info($timesheet);
     }

     /** Add/Update participants attendances */    
     public function saveParticipantsAttendances(Request $request)
     {
         /**Validation rules */
        $v = validator($request->all(), [
            'booking_process_id' => 'required|exists:booking_processes,id,deleted_at,NULL',
            'instructor_id' => 'required|exists:contacts,id,category_id,2,deleted_at,NULL',
            'attendance_date' => 'required|date|date_format:Y-m-d',
            'participants_attendances' => 'required|array',
            'participants_attendances.*.customer_id' => 'required|exists:contacts,id,category_id,1,deleted_at,NULL',
            'participants_attendances.*.is_attend' => 'required|boolean',
            'participants_attendances.*.comment' => 'nullable', 
        ],[
            'participants_attendances.*.customer_id.exists' => __('strings.exist_validation',['name' => 'customer']),
            'participants_attendances.*.is_attend.required' => __('strings.required_validation',['name' => 'is_attend']),
            'participants_attendances.*.is_attend.boolean' => __('strings.boolean_validation',['name' => 'is_attend']),
        ]);

        /**Return error response if validation rule not satisfy */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        /**Get neccessary details */
        $participants_attendances = $request->only('booking_process_id','instructor_id','attendance_date','participants_attendances');

        /**Every paricipant base add or update participants attendances */
        foreach($participants_attendances['participants_attendances'] as $participants_details){
            $input_details['booking_process_id'] = $participants_attendances['booking_process_id'];
            $input_details['instructor_id'] = $participants_attendances['instructor_id'];
            $input_details['attendance_date'] = $participants_attendances['attendance_date'];
            $input_details['customer_id'] = $participants_details['customer_id'];
            $input_details['is_attend'] = $participants_details['is_attend'];

            if($participants_details['comment'])
                $input_details['comment'] = $participants_details['comment'];

            /**Check instructor valid or not, if not valid then return error response */
            $check_instructor_valid = BookingProcessInstructorDetails::where('booking_process_id',$input_details['booking_process_id'])->where('contact_id',$input_details['instructor_id'])->first();

            if(!$check_instructor_valid)
                return $this->sendResponse(false, __('strings.invalid_booking_detail', ['name' => 'Instructor']));
            /** */

            /**Check customer valid or not, if not valid then return error response */
            $check_customer_valid = BookingProcessCustomerDetails::where('booking_process_id',$input_details['booking_process_id'])->where('customer_id',$input_details['customer_id'])->first();

            if(!$check_customer_valid)
                return $this->sendResponse(false, __('strings.invalid_booking_detail', ['name' => 'Customer']));
            /** */

            /**Create or update
             * If booking id, attendance_date and customerid this all data are exist then update details other  
             * wise create
             */
            $save_data = BookingParticipantsAttendance::updateOrCreate([
                'booking_process_id' => $input_details['booking_process_id'],
                'attendance_date' => $input_details['attendance_date'],
                'customer_id' => $input_details['customer_id']
            ], $input_details);
        }

        return $this->sendResponse(true, __('strings.participants_attendance_save_sucess'));
     }

     /**List participants attendances */    
     public function listParticipantsAttendances(Request $request)
     {
         /**Validation rules */
         $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
            'booking_process_id' => 'exists:booking_processes,id,deleted_at,NULL',
            'date' => 'nullable|date|date_format:Y-m-d',
            'is_attend' => 'nullable|boolean',
        ]);

        /**Return error response if validation rule not satisfy */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        $page = $request->page;
        $perPage = $request->perPage;
        $booking_participants = BookingParticipantsAttendance::query();

        if($request->booking_process_id){
            $booking_participants = $booking_participants->where('booking_process_id', $request->booking_process_id);
        }

        $booking_participants = $booking_participants->with('customer_detail','instructor_detail');

        /**If attend pass then filter */
        if( isset($request->is_attend) ){
            $booking_participants = $booking_participants->where('is_attend', $request->is_attend);
        }

        /**If date pass then attendance base filter */
        if($request->date){
            $booking_participants = $booking_participants->whereDate('attendance_date', $request->date);
        }

        /**If search pass then search on customer and instructor name base search */
        if($request->search){
            $search = $request->search;
            $contact_ids = Contact::where(function($q) use($search){
                $q->where('salutation','like',"%$search%");
                $q->orWhere('first_name','like',"%$search%");
                $q->orWhere('middle_name','like',"%$search%");
                $q->orWhere('last_name','like',"%$search%");
            })
            ->where('category_id',1)
            ->pluck('id');

            if(count($contact_ids) == 0){
                $contact_ids = Contact::where(function($q) use($search){
                    $q->where('salutation','like',"%$search%");
                    $q->orWhere('first_name','like',"%$search%");
                    $q->orWhere('middle_name','like',"%$search%");
                    $q->orWhere('last_name','like',"%$search%");
                })
                ->where('category_id',2)
                ->pluck('id');
            }
            $booking_participants = $booking_participants
            ->whereIn('customer_id',$contact_ids)
            ->orWhereIn('instructor_id',$contact_ids);
        }

        $booking_participants_count = $booking_participants->count();

        $booking_participants->skip($perPage*($page-1))->take($perPage);

        $booking_participants = $booking_participants->get();

        $data = [
            'participants_attendances' => $booking_participants,
            'count' => $booking_participants_count
        ];

        return $this->sendResponse(true, __('strings.list_message',['name' => 'Booking participants attendances']),$data);
     }

     /**Get participants attendances */    
     public function getParticipantsAttendances($id)
     {
        /**Check id not valid then return error response */
        $booking_participant = BookingParticipantsAttendance::find($id);
        
        if (!$booking_participant) {
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Id']));
        }

        /**Get details */
        $booking_participant = $booking_participant->with('customer_detail','instructor_detail')->first();

        return $this->sendResponse(true, __('strings.get_message',['name' => 'Booking participants attendances']),$booking_participant);
     }
}
