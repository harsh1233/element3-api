<?php

namespace App\Http\Controllers\API\Masters;

use Carbon\Carbon;
use App\Models\Contact;
use App\Models\ContactLeave;
use Illuminate\Http\Request;
use App\Models\Courses\Course;
use App\Models\ContactLanguage;
use App\Models\InstructorLevel;
use App\Models\Feedback\Feedback;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\InstructorActivity\InstructorActivity;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessLanguageDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class InstructorLevelController extends Controller
{
    use Functions;
    
    /** Get all Instructor Levels */
    public function getLevels()
    {
        $levels = InstructorLevel::latest()->get();
        return $this->sendResponse(true, 'success', $levels);
    }

    /** Create new Instructor Level */
    public function createLevel(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        $level = InstructorLevel::create($input);
        return $this->sendResponse(true, 'success', $level);
    }

    /** Update Instructor Level */
    public function updateLevel(Request $request, $id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $level = InstructorLevel::find($id);
        if (!$level) {
            return $this->sendResponse(false, 'Instructor Level not found');
        }
        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        $level->update($input);
        return $this->sendResponse(true, 'success', $level);
    }

    /** delete Instructor Level */
    public function deleteLevel($id)
    {
        $level = InstructorLevel::find($id);
        if (!$level) {
            return $this->sendResponse(false, 'Level not found');
        }
        $level->delete();
        return $this->sendResponse(true, 'success', $level);
    }

    /** Get Available Instructors between two date */
    public function getAvailableInstructor(Request $request)
    {
        // $v = validator($request->all(), [
        //     'StartDate_Time' => 'date',
        //     'EndDate_Time' => 'date',
        //     'language_detail' => 'array'
        // ]);

        $v = validator($request->all(), [
                    'dates' => 'array',
                    'language_detail' => 'array'
             ]);
            
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
            
        $contacts = Contact::where('is_active', true)->where('category_id', 2);
        $instructor_ids = ContactLanguage::whereIn('language_id', $request->language_detail)
            ->pluck('contact_id');

        $contacts = $contacts->whereIn('id', $instructor_ids);
        if ($request->dates) {
            $i=0;
            $tempStartDate = '';
            $tempEndDate = '';
            foreach ($request->dates as $key => $dates) {
                $date_inputs = $dates;

                if ($tempStartDate=='') {
                    $tempStartDate = $date_inputs['StartDate_Time'];
                } elseif ($date_inputs['StartDate_Time']<$tempStartDate) {
                    $tempStartDate = $date_inputs['StartDate_Time'];
                }
                if ($tempEndDate=='') {
                    $tempEndDate = $date_inputs['EndDate_Time'];
                } elseif ($date_inputs['EndDate_Time']>$tempEndDate) {
                    $tempEndDate = $date_inputs['EndDate_Time'];
                }

                $i++;
            }
        }
        
        $start_date = $tempStartDate;
        $end_date = $tempEndDate;
          
        $start_date1 = explode(" ", $start_date);
        $end_date1 = explode(" ", $end_date);
           
        $strt_date = $start_date1[0];
        $ed_date = $end_date1[0];
        $strt_time = $start_date1[1];
        $ed_time = $end_date1[1];

        $leave_contact_ids = ContactLeave::
                where(function ($query) use ($strt_date) {
                    $query->where('start_date', '<=', $strt_date);
                    $query->where('end_date', '>=', $strt_date);
                })
                ->orWhere(function ($query) use ($ed_date) {
                    $query->where('start_date', '<=', $ed_date);
                    $query->where('end_date', '>=', $ed_date);
                })
                ->pluck('contact_id');
           

        $contacts = $contacts->whereNotIn('id', $leave_contact_ids);
            
        if ($request->booking_process_id) {
            $booking_processId=$request->booking_process_id;
            $booking_processes_ids = $this->getAvailableInstructorList($strt_date, $ed_date, $strt_time, $ed_time, $booking_processId);
        } else {
            $booking_processes_ids = $this->getAvailableInstructorList($strt_date, $ed_date, $strt_time, $ed_time);
        }
            
           
        /* ->
        where(function($query) use($start_date,$end_date){
            $query->where('StartDate_Time', '>=', $start_date);
            $query->where('StartDate_Time', '<=', $end_date);
        })->
        orWhere(function($query) use($start_date,$end_date){
            $query->where('EndDate_Time', '<=', $start_date);
            $query->where('EndDate_Time', '>=', $end_date);
        })->
        orWhere(function($query) use($start_date,$end_date){
            $query->where('StartDate_Time', '>=', $start_date);
            $query->where('EndDate_Time', '<=', $end_date);
        })->
        orWhere(function($query) use($start_date,$end_date){
            $query->where('StartDate_Time', '<=', $start_date);
            $query->where('EndDate_Time', '>=', $start_date);
        })->
        orWhere(function($query) use($start_date,$end_date){
            $query->where('StartDate_Time', '<=', $end_date);
            $query->where('EndDate_Time', '>=', $end_date);
        })->
        orWhere(function($query) use($start_date,$end_date){
            $query->where('StartDate_Time', '>=', $start_date);
            $query->where('StartDate_Time', '<=', $end_date);
        }) */
            
        if (count($booking_processes_ids) > 0) {
            $assigned_instructor_ids = BookingProcessInstructorDetails::whereIn('booking_process_id', $booking_processes_ids)->pluck('contact_id');
                
            if (count($assigned_instructor_ids) > 0) {
                $contacts = $contacts->whereNotIn('id', $assigned_instructor_ids);
            }
        }
             
        $contacts = $contacts->get();
        if ($contacts->isEmpty()) {
            return $this->sendResponse(false, __('strings.instructor_not_found_criteria'));
        }

        return $this->sendResponse(true, 'success', $contacts);
    }

    /** Get Available Instructors between two date New API*/
    public function getAvailableInstructorNew(Request $request)
    {
        $v = validator($request->all(), [
                    'dates' => 'array',
                    'language_detail' => 'array',
                    "instructor_activity" => 'nullable|in:ski,snowboard'
             ]);
            
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
            
        $contacts = Contact::where('is_active', true)->where('category_id', 2);
        /**not languages base filter in check instructor availablity [18-12-2019] */
        /* $instructor_ids = ContactLanguage::whereIn('language_id', $request->language_detail)
            ->pluck('contact_id');
            
        $contacts = $contacts->whereIn('id', $instructor_ids); */

        if($request->instructor_activity){
            if($request->instructor_activity === 'ski'){
                $contacts = $contacts->where('is_ski', true);
            }else{
                $contacts = $contacts->where('is_snowboard', true);
            }
        }

        if ($request->dates) {
            $i=0;
            $tempStartDate = '';
            $tempEndDate = '';

            if ($request->booking_process_id) {
                $data = $this->getAvailableInstructorListNew($request->dates, $request->booking_process_id);
            } else {
                $data = $this->getAvailableInstructorListNew($request->dates);
            }


            if ($request->booking_process_id) {
                $lunch_data = $this->getAvailableInstructorInLunchHour($request->dates, $request->booking_process_id);
            } else {
                $lunch_data = $this->getAvailableInstructorInLunchHour($request->dates);
            }
            $booking_processes_lunch_ids = array_unique($lunch_data['booking_processes_lunch_ids']);

            $contact_lunch_ids = BookingProcessInstructorDetails::whereIn('booking_process_id', $booking_processes_lunch_ids)->pluck('contact_id');
            $contacts = $contacts->whereIn('id', $contact_lunch_ids);


            $booking_processes_ids_main = array_unique($data['booking_processes_ids_main']);
            $leave_contact_ids_main = array_unique($data['leave_contact_ids_main']);    
            if(count($leave_contact_ids_main)){
                $contacts = $contacts->whereNotIn('id', $leave_contact_ids_main);
            }   
            if(count($booking_processes_ids_main)){
                $contact_ids = BookingProcessInstructorDetails::whereIn('booking_process_id', $booking_processes_ids_main)->pluck('contact_id');
            }
            //$contacts = $contacts->whereNotIn('id', $contact_ids);
            $main_contact_ids = $contacts->pluck('id');
            /**Check instructor available with season schedular */
            $main_contact_ids = $this->getSeasonSchedularBaseIds($main_contact_ids, $request->dates);
            if(count($main_contact_ids)){
                $contacts = $contacts->whereIn('id', $main_contact_ids);
            }
        }
        $contacts = $contacts->get();
        
        //Contact Languages Conflict or Not 
        if($request->language_detail)
        {
            $instructor_ids = ContactLanguage::whereIn('language_id', $request->language_detail)
            ->pluck('contact_id')->toArray();

            $contacts = $contacts->map(function ($contact) use ($instructor_ids) {
                if (in_array($contact['id'], $instructor_ids))
                {
                    $contact['language_conflict']=0; 
                }
                else
                {
                    $contact['language_conflict']=1; 
                }
                return $contact;
            });

        }
        
        if ($contacts->isEmpty()) {
            return $this->sendResponse(false, __('strings.instructor_not_found_criteria'));
        }

        return $this->sendResponse(true, 'success', $contacts);
    }

    /**Check assigned instructor language base conflict or not */
    public function checkInstructorLanguageConflict(Request $request){
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
            'contact_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_process = BookingProcesses::find($request->booking_process_id);
        $contact = Contact::where('id',$request->contact_id)->where('is_active',1)->where('category_id',2)->first();

        if(!$booking_process)return $this->sendResponse(false, __('strings.booking_not_found'));
        elseif(!$contact)return $this->sendResponse(false, __('strings.contact_not_found'));

        /**Get assigned booking languages */
        $booking_languages_ids = BookingProcessLanguageDetails::where('booking_process_id',$request->booking_process_id)->pluck('language_id')->toArray();

        /**Get instructor languages */
        $instructor_languages_ids = ContactLanguage::where('contact_id',$request->contact_id)->pluck('language_id')->toArray(); 

        /**Check how many languages are common in booking languages and instructor languages */
        $common_languages_ids = array_intersect($booking_languages_ids, $instructor_languages_ids);

        $data = '';        
        if(count($common_languages_ids)===0){
            $data = __('strings.assigned_instructor_language_conflict');
        }
        return $this->sendResponse(true,'success',$data);
    }

    /* List all Instructor courses API */
    public function getInstuctorCourse(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        $page = $request->page;
        $perPage = $request->perPage;

        $instructor_id = auth()->user()->contact_id;
        $instructor = Contact::where('id', $instructor_id)->where('category_id', '2')->first();
        
        if (!$instructor) {
            return $this->sendResponse(false, 'Logged in Instructor not found');
        }

        $booking_processes_ids = BookingProcessInstructorDetails::where('contact_id', $instructor_id)->pluck('booking_process_id');

        if ($booking_processes_ids->isEmpty()) {
            return $this->sendResponse(false, 'Instructor have not a any Course');
        }
       
        $current_date = date('Y-m-d H:i:s');
        $ongoing_booking_processes_ids = array();
        if ($request->date_type == 'Upcoming') {
            $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids);

            if(date('H:i:s') >= '18:00:00'){
                $ongoing_booking_processes_ids = $ongoing_booking_processes_ids
                ->where('StartDate_Time', '>', date('Y-m-d H:i:s', strtotime('+1 day')));
            }else{
                $ongoing_booking_processes_ids = $ongoing_booking_processes_ids
                ->where('StartDate_Time', '>', $current_date);
            }
            // ->where('StartDate_Time', '>', $current_date)
            /**CHANGE DATE : 2020-11-02
             *DESCRIPTION : NOW ONGOING COURSE SHOULD BE VALID UP TO ONE DAY AGO SO THIS UPCOMING COURSE ARE AFFECT 
                */
            // ->where('StartDate_Time', '>', date('Y-m-d H:i:s', strtotime('+1 day')))
            $ongoing_booking_processes_ids = $ongoing_booking_processes_ids
            ->orderBy('StartDate_Time','ASC')
            ->pluck('booking_process_id');
        }
        if ($request->date_type == 'Past') {
            $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                    ->where('EndDate_Time', '<', $current_date)
                    ->pluck('booking_process_id');
        }
        if ($request->date_type == 'Ongoing') {
            $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids);
            /**CHANGE DATE : 2020-11-02
             *DESCRIPTION : NOW ONGOING COURSE SHOULD BE VALID UP TO ONE DAY AGO SO THIS ONE IS AFFECT 
            */
            if(date('H:i:s') >= '18:00:00'){
                $ongoing_booking_processes_ids = $ongoing_booking_processes_ids
                ->where(function($q) use($current_date){
                    $q->where('StartDate_Time', '<=', $current_date);
                    $q->orWhere('StartDate_Time', '<=', date('Y-m-d H:i:s', strtotime('+1 day')));
                });
            }else{
                $ongoing_booking_processes_ids = $ongoing_booking_processes_ids
                ->where('StartDate_Time', '<=', $current_date);
            }
            // ->where('StartDate_Time', '<=', $current_date)
            $ongoing_booking_processes_ids = $ongoing_booking_processes_ids
            ->where('EndDate_Time', '>=', $current_date)
            ->pluck('booking_process_id');
        }
        if (!$ongoing_booking_processes_ids) {
            return $this->sendResponse(false, 'Please Enter Valid Search Cousre Content');
        }

        // My Courses Arrange Order By Start Date ASC Wise
        //if ($request->date_type == 'Upcoming') {
            $my_courses = BookingProcesses::join('booking_process_course_details', 'booking_process_course_details.booking_process_id', '=', 'booking_processes.id')
            ->whereIn('booking_processes.id', $ongoing_booking_processes_ids);

            /**If past booking then end date base DESC other wise start date base ASC */
            if($request->date_type == 'Past'){
                $my_courses = $my_courses->orderBy('booking_process_course_details.EndDate_Time','DESC');
            }else{
                $my_courses = $my_courses->orderBy('booking_process_course_details.StartDate_Time','ASC');
            }
            $my_courses = $my_courses->select('booking_processes.*')
            ->with(['course_detail.course_data'])
            ->skip($perPage*($page-1))->take($perPage)
            ->get();
        //}else{
        //     $my_courses = BookingProcesses::whereIn('id', $ongoing_booking_processes_ids)
        //     ->with(['course_detail.course_data'])
        //     ->skip($perPage*($page-1))->take($perPage)
        //     ->get();
        // }

        //Last activity
        $my_courses->map(function ($my_course) use ($instructor_id) {
            $check_last_activity = InstructorActivity::where('instructor_id', auth()->user()->id)->where(
                'booking_id',
                $my_course['id']
            )->where('activity_date', date('Y-m-d'))->latest()->first();
            $my_course['last_activity'] = $check_last_activity ?  $check_last_activity['activity_type'] : '';
            $instructorDetail = BookingProcessInstructorDetails::select('id', 'contact_id', 'booking_process_id', 'is_course_confirmed')->where('contact_id', $instructor_id)->where(
                'booking_process_id',
                $my_course['id']
            )->latest()->first();
            $my_course['is_course_confirmed'] = $instructorDetail ? $instructorDetail['is_course_confirmed']  : 0;
            $feedback = Feedback::select('id', 'instructor_id', 'booking_id', 'average_rating')->where('instructor_id', auth()->user()->id)->where('booking_id', $my_course['id'])->first();
            if ($feedback) {
                $my_course['feedback_id'] = $feedback->id;
                $my_course['average_rating'] = $feedback->average_rating;
            } else {
                $my_course['feedback_id'] = null;
                $my_course['average_rating'] = null;
            }
            return $my_course;
        });

        $upcoming_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                    ->where('StartDate_Time', '>', $current_date)
                    /* ->orWhere(function($query)use($current_date){
                        $query->where('StartDate_Time', '<=', $current_date);
                        $query->where('EndDate_Time', '>=', $current_date);
                    }) */
                    ->pluck('booking_process_id');

        $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                    // ->where('StartDate_Time', '>', $current_date)
                    ->where(function ($query) use ($current_date) {
                        $query->where('StartDate_Time', '<=', $current_date);
                        $query->where('EndDate_Time', '>=', $current_date);
                    })
                    ->pluck('booking_process_id');
                    
        $upcoming_course_status = BookingProcessInstructorDetails::where('contact_id', $instructor_id)
        ->whereIn('booking_process_id', $upcoming_booking_processes_ids)
        ->where('is_course_confirmed',0)->get();

        $ongoing_course_status = BookingProcessInstructorDetails::where('contact_id', $instructor_id)
        ->whereIn('booking_process_id', $ongoing_booking_processes_ids)
        ->where('is_course_confirmed', 0)->get();

        if (count($upcoming_course_status)>0?$upcoming_course_status = "0":$upcoming_course_status="1");
        if (count($ongoing_course_status)>0?$ongoing_course_status = "0":$ongoing_course_status="1");

        return $this->sendResponse(true, 'success', $my_courses, $upcoming_course_status,$ongoing_course_status);
    }

     /* List all Customer courses API */
     public function getCustomerCourse(Request $request)
     {
         $v = validator($request->all(), [
             'page' => 'required|integer|min:1',
             'perPage' => 'required|integer|min:1',
         ]);
 
         if ($v->fails()) {
             return $this->sendResponse(false, $v->errors()->first());
         }
         
         $page = $request->page;
         $perPage = $request->perPage;
 
         $customer_id = auth()->user()->contact_id;
         $customer = Contact::where('id', $customer_id)->where('category_id', '1')->first();
         
         if (!$customer) {
             return $this->sendResponse(false, 'Logged in user not found');
         }
 
         $booking_processes_ids = BookingProcessCustomerDetails::where('customer_id', $customer_id)->pluck('booking_process_id');
 
         if ($booking_processes_ids->isEmpty()) {
             return $this->sendResponse(false, 'course not found');
         }
        
         $current_date = date('Y-m-d H:i:s');
         $ongoing_booking_processes_ids = array();
         if ($request->date_type == 'Upcoming') {
             $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                 /**CHANGE DATE : 2020-12-29
                 *DESCRIPTION : NOW ONGOING COURSE SHOULD BE VALID UP TO ONE DAY AGO SO THIS UPCOMING COURSE ARE AFFECT 
                 */
                ->where('StartDate_Time', '>', date('Y-m-d H:i:s', strtotime('+1 day')))
                ->orderBy('StartDate_Time','ASC')
                 ->pluck('booking_process_id');
         }
         if ($request->date_type == 'Past') {
             $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                     ->where('EndDate_Time', '<', $current_date)
                     ->pluck('booking_process_id');
         }
         if ($request->date_type == 'Ongoing') {
             $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                      /**CHANGE DATE : 2020-12-29
                     *DESCRIPTION : NOW ONGOING COURSE SHOULD BE VALID UP TO ONE DAY AGO SO THIS ONE IS AFFECT 
                    */
                    ->where(function($q) use($current_date){
                        $q->where('StartDate_Time', '<=', $current_date);
                        $q->orWhere('StartDate_Time', '<=', date('Y-m-d H:i:s', strtotime('+1 day')));
                    })
                     ->where('EndDate_Time', '>=', $current_date)
                     ->pluck('booking_process_id');
         }
         if (!$ongoing_booking_processes_ids) {
             return $this->sendResponse(false, 'Please Enter Valid Search Cousre Content');
         }
    
        //  $my_courses = BookingProcesses::whereIn('id', $ongoing_booking_processes_ids)
        //              ->with(['course_detail.course_data'])
        //              ->skip($perPage*($page-1))->take($perPage)
        //              ->get();

        //My Courses Arrange Order By Start Date ASC 
        $my_courses = BookingProcesses::join('booking_process_course_details', 'booking_process_course_details.booking_process_id', '=', 'booking_processes.id')
        ->whereIn('booking_processes.id', $ongoing_booking_processes_ids);

        if($request->date_type == 'Past'){
            $my_courses = $my_courses->orderBy('booking_process_course_details.EndDate_Time','DESC');
        }else{
            $my_courses = $my_courses->orderBy('booking_process_course_details.StartDate_Time','ASC');
        }
        
        $my_courses = $my_courses->select('booking_processes.*')
        ->with(['course_detail.course_data'])
        ->skip($perPage*($page-1))->take($perPage)
        ->get();

         //Last activity
         $my_courses->map(function ($my_course) {
             $feedback = Feedback::select('id', 'customer_id', 'booking_id', 'average_rating')->where('customer_id', auth()->user()->id)->where('booking_id', $my_course['id'])->first();
             if ($feedback) {
                 $my_course['feedback_id'] = $feedback->id;
                 $my_course['average_rating'] = $feedback->average_rating;
             } else {
                 $my_course['feedback_id'] = null;
                 $my_course['average_rating'] = null;
             }
             return $my_course;
         });
 
         $upcoming_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                     ->where('StartDate_Time', '>', $current_date)
                     /* ->orWhere(function($query)use($current_date){
                         $query->where('StartDate_Time', '<=', $current_date);
                         $query->where('EndDate_Time', '>=', $current_date);
                     }) */
                     ->pluck('booking_process_id');
 
         $ongoing_booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
                     // ->where('StartDate_Time', '>', $current_date)
                     ->where(function ($query) use ($current_date) {
                         $query->where('StartDate_Time', '<=', $current_date);
                         $query->where('EndDate_Time', '>=', $current_date);
                     })
                     ->pluck('booking_process_id');
                     
 
         return $this->sendResponse(true, 'success', $my_courses);
     }

    /*Get Ongoing participate listing*/
    public function getInstructorOngoingCourseParticipateListing()
    {
        $instructor_id = auth()->user()->contact_id;
        //$instructor_id = 7;
        $current_date = date('Y-m-d H:i:s');

        if (!$instructor_id) {
            return $this->sendResponse(false, 'Logged in Instructor not found');
        }

        $booking_processes_ids = BookingProcessInstructorDetails::where('contact_id', $instructor_id)->pluck('booking_process_id');

        $ongoing_booking_processes_ids = BookingProcessCustomerDetails::whereIn('booking_process_id', $booking_processes_ids)
                    ->where('StartDate_Time', '<=', $current_date)
                    ->where('EndDate_Time', '>=', $current_date)
                    ->pluck('booking_process_id');
        
        $ongoing_booking_customer_ids = BookingProcessCustomerDetails::whereIn('booking_process_id', $booking_processes_ids)
                    ->where('StartDate_Time', '<=', $current_date)
                    ->where('EndDate_Time', '>=', $current_date)
                    ->pluck('customer_id');
       
        if (!$ongoing_booking_processes_ids || !$ongoing_booking_customer_ids) {
            return $this->sendResponse(false, 'You have not any Ongoing Course');
        }

        $booking_processes_customer_details = BookingProcessCustomerDetails::with('customer.allergies.allergy', 'customer.languages.language', 'customer.difficulty_level_detail')
        ->with('bookingProcessCourseDetails')
        ->with('bookingPaymentDetails')
        ->whereIn('customer_id', $ongoing_booking_customer_ids)
        ->whereIn('booking_process_id', $ongoing_booking_processes_ids)->get()->toArray();
        Log::info($booking_processes_customer_details);
        
        return $this->sendResponse(true, 'success', $booking_processes_customer_details);
    }

    public function getInstructorLeaves(Request $request)
    {
        $leave_data = array();

        if ($request->is_second_calender=='true') {
            if ($request->type=='day') {
                if ($request->date) {
                    $request_date=$request->date;
                    //$temp_contact_ids = ContactLeave::where('start_date', $request->date)->pluck('contact_id');
                    $leave_data = ContactLeave::where(function ($query) use ($request_date) {
                        $query->where('start_date', '<=', $request_date);
                        $query->where('end_date', '>=', $request_date);
                    });
                }
            } elseif ($request->type=='week') {
                if ($request->date) {
                    $date = strtotime("+6 day", strtotime($request->date));
                    $start_date = $request->date;
                    $end_date = date('Y-m-d', $date);
                    
                    $leave_data = ContactLeave::where(function($query) use($start_date,$end_date){
                        $query->whereBetween('start_date', [$start_date,$end_date]);
                        $query->orWhereBetween('end_date', [$start_date,$end_date]);
                    });                    
                }
            }
        }else{
            $leave_data = ContactLeave::query();  
        }

        if ($request->contact_id) {
            $leave_data = $leave_data->where('contact_id', $request->contact_id);
        }
        if($leave_data){
            $leave_data = $leave_data->where('leave_status', 'A')->with('contact_detail')->get();
        }else{
            $leave_data = array();
        }
        $data['leave_data'] = $leave_data;
        return $this->sendResponse(true, 'success', $data);
    }


    /* For User Email Verified after click confirm course link in instructor email */
    public function courseConfirm()
    {
        $instructor_detail = BookingProcessInstructorDetails::where('booking_token', $_GET['reset_token'])
        ->where('booking_process_id', $_GET['booking_process_id'])
        ->where('contact_id', $_GET['instructor_id'])
        ->first();
        

        if (!$instructor_detail) {
            //$res = 'Course Confirm token not found.';
            $res=0;
        } else {
            if ($instructor_detail->is_course_confirmed) {
                //$res = 'Course is Already Confirmed.';
                $res=1;
            } else {
                $input['booking_token'] = null;
                $input['is_course_confirmed'] = 1;
                $input['confirmed_at'] = date('Y-m-d H:i:s');
                $update = $instructor_detail->update($input);
                //$res = 'Successfully Course Confirmed';
                $res=2;
            }
        }
        
        return view('instructor/course_confirmed_success', ["data"=>$res]);
    }

    /* For Course Confirm status */
    public function changeCourseConfirmStatus(Request $request)
    {
        $v = validator($request->all(), [
            'is_course_confirmed' => 'required|integer|in:1,0',
            'booking_process_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $update['updated_by'] = auth()->user()->id;
        if ($request->instructor_id) {
            $instructor_id = $request->instructor_id;
            $instructor_detail = BookingProcessInstructorDetails::where('booking_process_id', $request->booking_process_id)
            ->where('contact_id', $instructor_id)
            ->first();
        } else {
            $instructor_id = auth()->user()->contact_id;
            $instructor_detail = BookingProcessInstructorDetails::where('booking_process_id', $request->booking_process_id)
            ->where('contact_id', $instructor_id)
            ->first();
        }
        
       
        if (!$instructor_detail) {
            return $this->sendResponse(false, 'Instructor Course not found.');
        }

        $update['is_course_confirmed'] = $request->is_course_confirmed;
        $update['booking_token'] = null;
        $update['confirmed_at'] = date('Y-m-d H:i:s');

        $update = $instructor_detail->update($update);

        $instructorDetail = BookingProcessInstructorDetails::select('id', 'contact_id', 'booking_process_id', 'is_course_confirmed')->where('contact_id', $instructor_id)->where(
            'booking_process_id',
            $request->booking_process_id
        )->latest()->first();

        $is_course_confirmed = $instructorDetail ? $instructorDetail['is_course_confirmed']  : 0;
        
        $current_date = date('Y-m-d H:i:s');

        $booking_processes_ids = BookingProcessCourseDetails::
        // where('StartDate_Time', '>', $current_date)
        where('EndDate_Time', '>=', $current_date)
        ->pluck('booking_process_id');

        $general_course_status = BookingProcessInstructorDetails::where('contact_id', $instructor_id)
        ->whereIn('booking_process_id', $booking_processes_ids)
        ->where('is_course_confirmed', 0)->get();

        if (count($general_course_status)>0?$general_status = 0:$general_status=1);

        $booking_details = BookingProcesses::where('id', $request->booking_process_id)
                    ->with(['course_detail.course_data'])
                    ->first();

        $data = $booking_details;
        $data['is_course_confirmed'] = $is_course_confirmed;
        return $this->sendResponse(true, __('strings.course_confirm_status_change_success'), $data,null,null,$general_status);
    }

    public function quickCheckInstructor(Request $request)
    {
        $v = validator($request->all(), [
            'instructor_id' => 'required|integer',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d|after_or_equal:start_date',
           // 'start_time' => 'required|date_format:H:i:s',
           // 'end_time' => 'required|date_format:H:i:s|after:start_time',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $instructor_id = $request->instructor_id;
        // $start_time = $request->start_time;
        // $end_time = $request->end_time;
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        
        $contact = Contact::where('id', $instructor_id)->where('category_id', 2)->count();
        if (!$contact) {
            return $this->sendResponse(true, __('Instructor not exist'));
        }
        $data = [];
        while (strtotime($start_date) <= strtotime($end_date)) {
            //$bookingIds = BookingProcessCustomerDetails::where('start_date','<=',$start_date)->where('end_date','>=',$start_date)
            // ->where(function($q) use($start_time,$end_time){
            //    $q->where('start_time','<=',$start_time);
            //    $q->OrWhere('start_time','<=',$end_time);
            // })
            // ->where(function($q) use($start_time,$end_time){
            //     $q->where('end_time','>=',$start_time);
            //     $q->OrWhere('end_time','>=',$end_time);
            //  })
            //->pluck('booking_process_id');
            //print_r($bookingIds);
            $instructorBookings = BookingProcessInstructorDetails::where('contact_id', $instructor_id)->pluck('booking_process_id')->toArray();
            
            if (count($instructorBookings) > 0) {
                $instructorBookings = BookingProcesses::whereIn('id', $instructorBookings)->where('is_trash', false)->where('is_cancel', false)->pluck('id')->toArray();

                $bookingDates = BookingProcessCourseDetails::whereIn('booking_process_id', $instructorBookings)->where('start_date', '<=', $start_date)->where('end_date', '>=', $start_date)->get();
                if (count($bookingDates) > 0) {
                    $total_hours = 0;
                    foreach ($bookingDates as $bd) {
                        $total_duration = Carbon::parse($bd['end_time'])->diffInSeconds(Carbon::parse($bd['start_time']));
                        $total_duration = $total_duration/3600;
                        $total_hours += $total_duration;
                    }
                    // Log::info($bookingDates);
                    // Log::info($total_hours);
                    if ($total_hours < config('constants.instructor_daily_office_hour')) {
                        $status = "partially_booked";
                    } else {
                        $status = "booked";
                    }
                } else {
                    if ($this->checkInstructorLeaveStatus($instructor_id, $start_date)) {
                        $status = 'onleave';
                    } else {
                        $status = 'available';
                    }
                }
            } else {
                if ($this->checkInstructorLeaveStatus($instructor_id, $start_date)) {
                    $status = 'onleave';
                } else {
                    $status = 'available';
                }
            }

            /**Check instructor available with season schedular */
            if($status === 'available'){
                $contact_ids[] = $instructor_id;
                $dates[0]['StartDate_Time'] = $start_date.' '.'00:00:00';
                $dates[0]['EndDate_Time'] = $start_date.' '.'23:59:59';
                $main_data = $this->getSeasonSchedularBaseIds($contact_ids, $dates);
                if(count($main_data)){
                    $status = 'available';
                }else{
                    $status = 'not_available';
                }
            }
            /**End */

            $data[] = [
                'date' => $start_date,
                'status' => $status
            ];
            $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
        }

        return $this->sendResponse(true, __('Instructor availibility status'), $data);
    }

    public function checkInstructorLeaveStatus($instructor_id, $date)
    {
        $checkLeave = ContactLeave::where('contact_id', $instructor_id)->where('leave_status', 'A')->where('start_date', '<=', $date)->where('end_date', '>=', $date)->count();
        if ($checkLeave) {
            return true;
        }
        return false;
    }
}
