<?php

namespace App\Http\Controllers\API\Courses;

use Excel;
use DateTime;
use Illuminate\Http\Request;
use App\Exports\CourseExport;
use App\Models\PaymentMethod;
use App\Models\Courses\Course;
use App\Models\CreditCardMaster;
use App\Models\Feedback\Feedback;
use App\Http\Controllers\Functions;
use App\Models\SeasonDaytimeMaster;
use App\Http\Controllers\Controller;
use App\Models\Courses\CourseDetail;
use App\Exports\CourseBaseBookingExport;
use App\Models\BookingProcess\BookingPayment;
use App\Models\Courses\CourseDifficultyLevel;
use App\Models\BookingProcess\BookingEstimate;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\TeachingMaterial\TeachingMaterial;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\TeachingMaterial\CourseTeachingMaterialDetail;

class CourseController extends Controller
{
    use Functions;

    /* List all courses */
    public function listCourse(Request $request)
    {
        $courses = Course::with(['category_detail'=>function ($query) {
            $query->select('id', 'name', 'name_en');
        }])
            ->with(['difficulty_level_detail'=>function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['meeting_point_detail'=>function ($query) {
                $query->select('id', 'name', 'address');
            }]);
        if (isset($_GET['type'])) /* Remove Code For  && isset($_GET['session']) && isset($_GET['time']) && isset($_GET['no_of_days']) search get course */ {
            //$course_detail_ids = CourseDetail::where('session',$_GET['session'])->where('time',$_GET['time'])->where('no_of_days',$_GET['no_of_days'])->pluck('course_id');

            /* $course_detail_ids = CourseDetail::where('session', $_GET['session'])->where('time', $_GET['time'])->pluck('course_id');
            
            if (isset($_GET['hours_per_day'])) {
                $course_detail_ids1 = CourseDetail::where('session', $_GET['session'])->where('time', $_GET['time'])->where('hours_per_day', $_GET['hours_per_day'])->get();
                $course_detail_ids1 = $course_detail_ids1->whereIn('course_id', $course_detail_ids)->pluck('course_id');
                $course_detail_ids=$course_detail_ids1;
            } */
            
            $courses = $courses->where('type', $_GET['type'])
            // ->whereIn('id', $course_detail_ids)
            ->where('is_active', 1);
        }
        /**If is_archived passed then course list filter */
        if(isset($_GET['is_archived'])){
            $courses = $courses->where('is_archived',$_GET['is_archived']);
        }
        
        $courses = $courses->orderBy('name','ASC')
        ->get();

        if (!empty($_GET['is_export'])) {
            return Excel::download(new CourseExport($courses->toArray()), 'Courses.csv');
        }

        return $this->sendResponse(true, 'success', $courses);
    }

    /* List all courses Pagination */
    public function listCoursePagination(Request $request)
    {
        $courses = Course::with(['category_detail'=>function ($query) {
            $query->select('id', 'name', 'name_en');
        }])
            ->with(['difficulty_level_detail'=>function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['meeting_point_detail'=>function ($query) {
                $query->select('id', 'name', 'address');
            }]);
        if($request->perPage)
        {
            $perPage=$request->perPage;
        }
        else
        {
            $perPage=20;
        }
        if($request->page)
        {
            $page=$request->page;
        }
        else
        {
            $page=1;
        }
        
        if (isset($_GET['type']) && isset($_GET['session']) && isset($_GET['time'])) /* Remove Code For && isset($_GET['no_of_days']) search get course */ {
            //$course_detail_ids = CourseDetail::where('session',$_GET['session'])->where('time',$_GET['time'])->where('no_of_days',$_GET['no_of_days'])->pluck('course_id');
            $course_detail_ids = CourseDetail::where('session', $_GET['session'])->where('time', $_GET['time'])->pluck('course_id');
            
            if (isset($_GET['hours_per_day'])) {
                $course_detail_ids1 = CourseDetail::where('session', $_GET['session'])->where('time', $_GET['time'])->where('hours_per_day', $_GET['hours_per_day'])->get();
                $course_detail_ids1 = $course_detail_ids1->whereIn('course_id', $course_detail_ids)->pluck('course_id');
                $course_detail_ids=$course_detail_ids1;
            }
            
            $courses = $courses->where('type', $_GET['type'])->whereIn('id', $course_detail_ids)->where('is_active', 1);
            $courseCount = $courses->count();
        }
        
        /**If is_archived passed then course list filter */
        if(isset($_GET['is_archived'])){
            $courses = $courses->where('is_archived',$_GET['is_archived']);
        }

        if($request->page && $request->perPage)
        {   
          $courseCount = $courses->count();
          $courses->skip($perPage*($page-1))->take($perPage);  
        }

        $courses = $courses->orderBy('id', 'desc')->get();
        $data=array();
        if($request->page && $request->perPage)
        {
            $data = [
                'courses' => $courses,
                'count' => $courseCount
            ];
        }
        else
        {
            $data=$courses;
        }
        
        if (!empty($_GET['is_export'])) {
            return Excel::download(new CourseExport($courses->toArray()), 'Courses.csv');
        }
        return $this->sendResponse(true, 'success', $data);
    }

    /* View course detail */
    public function viewCourse($id)
    {
        $course = Course::with(['category_detail'=>function ($query) {
            $query->select('id', 'name', 'name_en');
        }])
       ->with(['difficulty_level_detail'=>function ($query) {
           $query->select('id', 'name');
       }])
       ->with('course_detail')
       ->with('teaching_material_detail.teaching_material_data.teaching_material_category_detail')
       ->find($id);
        if (!$course) {
            return $this->sendResponse(false, 'Course not found');
        }
        return $this->sendResponse(true, 'success', $course);
    }

    /* Create new course */
    public function createCourse(Request $request)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50|unique:courses',
            'name_en' => 'nullable|max:50',
            'type' => 'required|in:Group,Private,Other',
            'category_id' => 'required|integer|min:1',
            //'difficulty_level' => 'required|integer|min:1',
            'maximum_instructor' => 'nullable|integer|min:1',
            'maximum_participant' => 'nullable|integer|min:1',
            'is_display_on_website' => 'boolean',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'meeting_point_id' => 'nullable|exists:meeting_points,id,deleted_at,NULL',
            'restricted_start_date' => 'nullable|date_format:Y-m-d',
            'restricted_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:restricted_start_date',
            'restricted_no_of_days' => 'nullable|numeric',
            'restricted_start_time' => 'nullable|date_format:H:i:s',
            'restricted_end_time' => 'nullable|date_format:H:i:s',
            'restricted_no_of_hours' => 'nullable|numeric',
            'price_per_item' => 'nullable|numeric',
            'course_detail' => 'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|array',
            'course_detail.*.session'=>'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|in:High Season,Low Season,Day',
            'course_detail.*.time'=>'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|in:Morning,Afternoon,Whole Day',
            'course_detail.*.price_per_day'=>'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|numeric',
            'course_detail.*.hours_per_day'=>'nullable|numeric',
            'course_detail.*.no_of_days'=>'nullable|integer|min:1',
            'course_detail.*.extra_person_charge'=>'nullable|numeric',
            'course_detail.*.material_detail' => 'array',
            'course_detail.*.cal_payment_type' => 'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|in:PH,PD',
            'course_detail.*.is_include_lunch' => 'nullable|in:1,0',
            'course_detail.*.include_lunch_price' => 'nullable|numeric',
            'cal_payment_type'=>'required|in:PH,PD,PIS',
            'course_detail.*.total_price'=>'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|numeric',
            'material_detail' => 'array',
            'is_include_lunch_hour' => 'nullable|boolean'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        $input = $request->all();
        $input['created_by'] = auth()->user()->id;
        if ($request->course_banner) {
            $url = $this->uploadImage('courses', $request->name, $request->course_banner);
            $input['course_banner'] = $url;
        }
        $course = Course::create($input);
        
        if ($request->course_detail) {
            $courseDetail = array_map(function ($course_data) use ($course) {
                return $course_data + ['course_id' => $course->id] + ['created_at' => date("Y-m-d H:i:s")];
            }, $request['course_detail']);
            CourseDetail::insert($courseDetail);
        }

        if ($request->material_detail) {
            $material = array();
            $courseDetail = array_map(function ($material_data) use ($course, $material) {
                $material['teaching_material_id'] = $material_data;
                $material['course_id'] = $course->id;
                $material['created_at'] = date("Y-m-d H:i:s");
                return $material;
            }, $request['material_detail']);
            CourseTeachingMaterialDetail::insert($courseDetail);
        }

        /**Add crm user action trail */
        if ($course) {
            $action_id = $course->id; //course id
            $action_type = 'A'; //A = Add
            $module_id = 14; //module id base module table
            $module_name = "Course Catalog"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.course_create_success'));
    }

    /* Update existing course */
    public function updateCourse(Request $request, $id)
    {
        $v = validator($request->all(), [
            'name' => 'required|max:50',
            'name_en' => 'nullable|max:50',
            'type' => 'required|in:Group,Private,Other',
            'category_id' => 'required|integer|min:1',
            //'difficulty_level' => 'required|integer|min:1',
            'maximum_instructor' => 'nullable|integer|min:1',
            'maximum_participant' => 'nullable|integer|min:1',
            'is_display_on_website' => 'boolean',
            'start_time' => 'nullable|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s',
            'meeting_point_id' => 'nullable|exists:meeting_points,id,deleted_at,NULL',
            'restricted_start_date' => 'nullable|date_format:Y-m-d',
            'restricted_end_date' => 'nullable|date_format:Y-m-d|after_or_equal:restricted_start_date',
            'restricted_no_of_days' => 'nullable|numeric',
            'restricted_start_time' => 'nullable|date_format:H:i:s',
            'restricted_end_time' => 'nullable|date_format:H:i:s',
            'restricted_no_of_hours' => 'nullable|numeric',
            'price_per_item' => 'nullable|numeric',
            'course_detail' => 'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|array',
            'course_detail.*.session'=>'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|in:High Season,Low Season,Day',
            'course_detail.*.time'=>'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|in:Morning,Afternoon,Whole Day',
            'course_detail.*.price_per_day'=>'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|numeric',
            'course_detail.*.hours_per_day'=>'nullable|numeric',
            'course_detail.*.no_of_days'=>'nullable|integer|min:1',
            'course_detail.*.extra_person_charge'=>'nullable|numeric',
            'material_detail' => 'array',
            'course_detail.*.cal_payment_type' => 'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|in:PH,PD',
            'course_detail.*.is_include_lunch' => 'nullable|in:1,0',
            'course_detail.*.include_lunch_price' => 'nullable|numeric',
            'cal_payment_type'=>'required|in:PH,PD,PIS',
            'course_detail.*.total_price'=>'required_if:cal_payment_type,PH,required_if:cal_payment_type,PD|numeric',
            'is_include_lunch_hour' => 'nullable|boolean'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        $course = Course::find($id);
        if (!$course) {
            return $this->sendResponse(false, 'Course not found');
        }

        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;
        if ($request->course_banner && $request->imageUpdate) {
            $url = $this->uploadImage('courses', $request->name, $request->course_banner);
            $input['course_banner'] = $url;
        }
        $course->update($input);

        if ($request->is_course_detail_update==1) {
            if ($request->delete_course_detail) {
                foreach ($request->delete_course_detail as $key => $delete_course_detail) {
                    if ($delete_course_detail['is_new'] == 0) {
                        $course_detail = CourseDetail::where("course_id", $course->id)
                        ->where('session', $delete_course_detail['session'])
                        ->where('time', $delete_course_detail['time'])
                        ->where('price_per_day', $delete_course_detail['price_per_day'])
                        ->first();
                        if (!$course_detail) {
                            return $this->sendResponse(false, __('strings.course_detail_not_found'));
                        }
                        $check_course_detail = BookingProcessCourseDetails::where('course_detail_id', $course_detail->id)->count();
                        if ($check_course_detail) {
                            return $this->sendResponse(false, 'Course Detail is already exist in Booking', $course_detail->id);
                        }
                        $course_detail->delete();
                    }
                }
            }
            $i=0;
            if ($request->course_detail) {
                //CourseDetail::where('course_id',$id)->delete();
                $customer_detail1 = array();
                $course_detail_data = $request['course_detail'];
                foreach ($course_detail_data as $key => $customer_detail) {
                    /**For new add course details price details */
                    if ($course_detail_data[$i]['is_new']==1) {
                        $customer_detail1[$i]['session'] = $customer_detail['session'];
                        $customer_detail1[$i]['time'] = $customer_detail['time'];
                        $customer_detail1[$i]['price_per_day'] = $customer_detail['price_per_day'];
                        $customer_detail1[$i]['hours_per_day'] = $customer_detail['hours_per_day'];
                        $customer_detail1[$i]['no_of_days'] = $customer_detail['no_of_days'];
                        $customer_detail1[$i]['extra_person_charge'] = $customer_detail['extra_person_charge'];
                        $customer_detail1[$i]['cal_payment_type'] = $customer_detail['cal_payment_type'];
                        $customer_detail1[$i]['is_include_lunch'] = $customer_detail['is_include_lunch'];
                        $customer_detail1[$i]['include_lunch_price'] = $customer_detail['include_lunch_price'];
                        $customer_detail1[$i]['total_price'] = $customer_detail['total_price'];
                        $customer_detail1[$i]['course_id'] = $course->id;
                        $customer_detail1[$i]['created_at'] = date("Y-m-d H:i:s");
                        $customer_detail1[$i]['updated_at'] = date("Y-m-d H:i:s");
                    }
                    /**For update course details price details */
                    if ($course_detail_data[$i]['is_update']==1) {

                        /**Update course details */
                        $course_detail = CourseDetail::where("course_id", $course->id)
                        ->where('id',$customer_detail['id'])->first();

                        /**If not find course details from the passed course id and course 
                         * details id base then return error
                         */
                        if (!$course_detail) {
                            return $this->sendResponse(false, __('strings.course_detail_not_found'));
                        }
                        /**Manage update course detail array for update */
                        $customer_update['session'] = $customer_detail['session'];
                        $customer_update['time'] = $customer_detail['time'];
                        $customer_update['price_per_day'] = $customer_detail['price_per_day'];
                        $customer_update['hours_per_day'] = $customer_detail['hours_per_day'];
                        $customer_update['no_of_days'] = $customer_detail['no_of_days'];
                        $customer_update['extra_person_charge'] = $customer_detail['extra_person_charge'];
                        $customer_update['cal_payment_type'] = $customer_detail['cal_payment_type'];
                        $customer_update['is_include_lunch'] = $customer_detail['is_include_lunch'];
                        $customer_update['include_lunch_price'] = $customer_detail['include_lunch_price'];
                        $customer_update['total_price'] = $customer_detail['total_price'];
                        $customer_update['course_id'] = $course->id;
                        $customer_update['updated_at'] = date("Y-m-d H:i:s");

                        /**Update course details */
                        CourseDetail::where("course_id", $course->id)
                        ->where('id',$customer_detail['id'])->update($customer_update);
                    }
                    $i=$i+1;
                }
                /* $courseDetail = array_map(function($course_data) use ($course,$course_detail_data,$j) {
                    if($course_detail_data[$j]['is_new']==1){
                        $j=$j+1;
                            return $course_data + ['course_id' => $course->id] + ['created_at' => date("Y-m-d H:i:s")] + ['updated_at' => date("Y-m-d H:i:s")];
                    }
                },$customer_detail1); */
                //dd($customer_detail1);
                CourseDetail::insert($customer_detail1);
            }
        }

        if ($request->material_detail) {
            CourseTeachingMaterialDetail::where('course_id', $id)->delete();
            $material = array();
            $courseDetail = array_map(function ($material_data) use ($course, $material) {
                $material['teaching_material_id'] = $material_data;
                $material['course_id'] = $course->id;
                $material['created_at'] = date("Y-m-d H:i:s");
                $material['updated_at'] = date("Y-m-d H:i:s");
                return $material;
            }, $request['material_detail']);
            CourseTeachingMaterialDetail::insert($courseDetail);
        }

        /**Add crm user action trail */
        if ($course) {
            $action_id = $course->id; //course id
            $action_type = 'U'; //U = Updated
            $module_id = 14; //module id base module table
            $module_name = "Course Catalog"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.course_update_success'));
    }

    /* Change status of course */
    public function changeStatus(Request $request, $id)
    {
        $v = validator($request->all(), [
            'status' => 'boolean',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        $course = Course::find($id);
        if (!$course) {
            return $this->sendResponse(false, 'Course not found');
        }

        $course->is_active = $request->status;
        $course->save();

        /**Add crm user action trail */
        if ($course) {
            $action_id = $course->id; //course id
            
            if($request->status)
            $action_type = 'ACS'; //ACS = Active Change Status
            else
            $action_type = 'DCS'; //DCS = Deactive Change Status

            $module_id = 14; //module id base module table
            $module_name = "Course Catalog"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.status_change_success'));
    }

    /* List all customer courses */
    /* This API is old concept api so do not use */
    public function listCustomerCourse(Request $request)
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
        $userId = auth()->user()->contact_id;
        $my_courses = array();
        $feature_course = array();
        $booking_processes_ids = BookingProcessCustomerDetails::query();
        $booking_processes_ids = $booking_processes_ids->where('customer_id', $userId);
        $booking_processes_ids = $booking_processes_ids->pluck('booking_process_id');
    $booking_processes_courses_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)->where('is_enrolled', 1)->pluck('course_id');
        //dd($booking_processes_courses_ids);
        if ($request->course_type=='my_courses') {
            $my_courses = Course::whereIn('id', $booking_processes_courses_ids)
            ->with(['booking_course_detail'])
            ->with(['category_detail'=>function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['difficulty_level_detail'=>function ($query) {
                $query->select('id', 'name');
            }]);
        } elseif ($request->course_type=='feature_course') {
            $feature_course = Course::query();
            $feature_course = $feature_course->where('is_feature_course', 1);
        } else {
            $my_courses = Course::whereIn('id', $booking_processes_courses_ids)
            ->with(['booking_course_detail'])
            ->with(['category_detail'=>function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['difficulty_level_detail'=>function ($query) {
                $query->select('id', 'name');
            }]);
            
            $feature_course = Course::query();
            $feature_course = $feature_course->where('is_feature_course', 1);
        }
        
        if ($request->search) {
            $search = $request->search;
            if (!empty($my_courses)) {
                $my_courses = $my_courses->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                });
            }
            if (!empty($feature_course)) {
                $feature_course = $feature_course->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                });
            }
        }
        
        if (!empty($my_courses)) {
            $my_courses = $my_courses->skip($perPage*($page-1))->take($perPage)->get();
        }
        if (!empty($feature_course)) {
            $feature_course = $feature_course->skip($perPage*($page-1))->take($perPage)->get();
        }
        //dd($my_courses[0]);
        $data = [
            'my_courses' => $my_courses,
            'feature_course' => $feature_course
        ];
        return $this->sendResponse(true, 'success', $data);
    }

    /* List all customer courses New API */
    public function listCustomerCourseNew(Request $request)
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
        $userId = auth()->user()->contact_id;
        $my_courses = array();
        $completed_course = array();
        $feature_course = array();
        $current_date = date('Y-m-d H:i:s');
        $booking_processes_ids = BookingProcessCustomerDetails::query();
        $booking_processes_ids = $booking_processes_ids->where(function($query)use($userId){
            $query->where('customer_id', $userId);
            $query->where('is_customer_enrolled', 1);
        });
        
        $booking_processes_ids = $booking_processes_ids->orWhere('payi_id', $userId);
        $booking_processes_ids = $booking_processes_ids->pluck('booking_process_id');
        
        /**Get customer course ids */
        $course_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)->pluck('course_id');
        /** */

        $completed_course_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)->where('EndDate_Time', '<', $current_date)->pluck('booking_process_id');
        
        $booking_processes_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
        ->where('StartDate_Time', '>', $current_date)
        ->orderBy('StartDate_Time','ASC')
        ->pluck('booking_process_id');
        
        if ($request->course_type=='my_courses') {
            $my_courses = BookingProcesses::join('booking_process_course_details', 'booking_process_course_details.booking_process_id', '=', 'booking_processes.id')
            ->orderBy('booking_process_course_details.StartDate_Time','ASC')
            ->whereIn('booking_processes.id', $booking_processes_ids)
            ->select('booking_processes.*')
            ->with(['course_detail.course_data']);
        } elseif ($request->course_type=='feature_course') {
            $feature_course = Course::query();
            $feature_course = $feature_course
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where('is_display_on_website', true)
            ->whereNotIn('id',$course_ids);
        } elseif ($request->course_type=='completed_course') {
            $completed_course = BookingProcesses::join('booking_process_course_details', 'booking_process_course_details.booking_process_id', '=', 'booking_processes.id')
            ->orderBy('booking_process_course_details.EndDate_Time','DESC')
            ->whereIn('booking_processes.id', $completed_course_ids)
            ->select('booking_processes.*')
            ->with(['course_detail.course_data']);
        } else {
            $my_courses = BookingProcesses::join('booking_process_course_details', 'booking_process_course_details.booking_process_id', '=', 'booking_processes.id')
            ->orderBy('booking_process_course_details.StartDate_Time','ASC')
            ->whereIn('booking_processes.id', $booking_processes_ids)
            ->select('booking_processes.*')
            ->with(['course_detail.course_data']);

            $completed_course = BookingProcesses::join('booking_process_course_details', 'booking_process_course_details.booking_process_id', '=', 'booking_processes.id')
            ->orderBy('booking_process_course_details.EndDate_Time','DESC')
            ->whereIn('booking_processes.id', $completed_course_ids)
            ->select('booking_processes.*')
            ->with(['course_detail.course_data']);

            $feature_course = Course::query();
            $feature_course = $feature_course
            ->where('is_active', true)
            ->where('is_archived', false)
            ->where('is_display_on_website', true)
            ->whereNotIn('id',$course_ids);
        }
        
        if ($request->search) {
            $search = $request->search;
            if (!empty($my_courses)) {
                $booking_processes_course_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)->pluck('course_id');
                $course = Course::whereIn('id', $booking_processes_course_ids);
                $course = $course->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                })->pluck('id');
                $booking_processes_ids1 = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)->whereIn('course_id', $course)->pluck('booking_process_id');
                $my_courses = BookingProcesses::whereIn('id', $booking_processes_ids1)
                    ->with(['course_detail.course_data']);
            }
            if (!empty($feature_course)) {
                $feature_course = $feature_course->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                });
            }

            if (!empty($completed_course)) {
                $booking_processes_course_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)->pluck('course_id');
                $course = Course::whereIn('id', $booking_processes_course_ids);
                $course = $course->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%$search%");
                })->pluck('id');
                $booking_processes_ids1 = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)->whereIn('course_id', $course)->pluck('booking_process_id');
                $completed_course = BookingProcesses::whereIn('id', $booking_processes_ids1)
                ->with(['course_detail.course_data']);
            }
        }
        
        if (!empty($my_courses)) {
            $my_courses = $my_courses->skip($perPage*($page-1))->take($perPage)->get();
        }
        if (!empty($feature_course)) {
            $feature_course = $feature_course->skip($perPage*($page-1))->take($perPage)->get();
        }
        if (!empty($completed_course)) {
            $completed_course = $completed_course->skip($perPage*($page-1))->take($perPage)->get();
            
            $completed_course->map(function ($completed_course) {
                $feedback = Feedback::select('id', 'customer_id', 'booking_id', 'average_rating')->where('customer_id', auth()->user()->id)->where('booking_id', $completed_course['id'])->first();
                if ($feedback) {
                    $completed_course['feedback_id'] = $feedback->id;
                    $completed_course['average_rating'] = $feedback->average_rating;
                } else {
                    $completed_course['feedback_id'] = null;
                    $completed_course['average_rating'] = null;
                }
                return $completed_course;
            });
        }
        $data = [
            'my_courses' => $my_courses,
            'feature_course' => $feature_course,
            'completed_course' => $completed_course,
        ];
        return $this->sendResponse(true, 'success', $data);
    }

    /* Get course name and booking process data from booking process Qr scan*/
    public function getCourseFromQr($qr_num)
    {
        $booking_processes = BookingProcesses::where('QR_number', $qr_num)->first();
        if (!$booking_processes) {
            return $this->sendResponse(false, 'Booking Process Qr number not found');
        }
        $booking_processes_course_id = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->pluck('course_id');
        $course = Course::find($booking_processes_course_id)->first();

        if (!$course) {
            return $this->sendResponse(false, 'Course not found');
        }

        $data = [
            'booking_processes_id' => $booking_processes->id,
            'booking_processes_no' => $booking_processes->booking_number,
            'course_name' => $course->name
        ];
        return $this->sendResponse(true, 'success', $data);
    }

    /* Get course detail data */
    public function getCourseDetailIdAndPrice(Request $request)
    {
        /**
         * For check booking criteria are valid or not
         * Date : 05-08-2020
         * This code is comment because this code is not need for current functionality
         */
        // if(isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['course_id'])){
        //     $start_date_time = $_GET['start_date'].' '.$_GET['start_time'];
        //     $end_date_time = $_GET['end_date'].' '.$_GET['end_time'];

        //     $datetime1 = new DateTime($start_date_time);
        //     $datetime2 = new DateTime($end_date_time);

        //     $course_id = $_GET['course_id'];
        //     $interval = $datetime2->diff($datetime1);
        //     $hours = $interval->format('%h');
        //     $days = $interval->format('%d');
        //     $minutes = $interval->format('%i');

        //     $customer_vaid_for_course = $this->checkCustomerValidForCourse($course_id, $days, $hours, $minutes, $start_date_time, $end_date_time);

        //     if(!$customer_vaid_for_course){
        //         return $this->sendResponse(false, __('strings.booking_criteria_not_valid'));
        //     }
        // }
        // else{
        //     return $this->sendResponse(false, 'Insufficient Data');
        // }
        /**End */

        /* if (isset($_GET['course_id']) && isset($_GET['session']) && isset($_GET['time']) && isset($_GET['no_of_days']) && isset($_GET['cal_payment_type'])) {
            $course_detail_data = CourseDetail::where('session', $_GET['session'])->where('course_id', $_GET['course_id'])->where('time', $_GET['time'])->where('no_of_days', $_GET['no_of_days'])->where('cal_payment_type',$_GET['cal_payment_type'])->first();
           
            if (isset($_GET['hours_per_day'])) {
                $course_detail_ids1 = CourseDetail::where('session', $_GET['session'])->where('time', $_GET['time'])->where('course_id', $_GET['course_id'])->where('no_of_days', $_GET['no_of_days'])->where('hours_per_day', $_GET['hours_per_day'])->first();
                $course_detail_data = $course_detail_ids1;
            }
        else {
            return $this->sendResponse(false, 'Insufficient Data');
        } */
        
        if(isset($_GET['start_date']) && isset($_GET['end_date']) && isset($_GET['start_time']) && isset($_GET['end_time'])){
            $start_date = $_GET['start_date'];
            $end_date = $_GET['end_date'];
            $start_time = $_GET['start_time'];
            $end_time = $_GET['end_time'];
            
            /**For get season and daytime base startdate, enddate, startime, endtime */
            $data = $this->getSeasonDaytime($start_date, $end_date, $start_time, $end_time);

            /* $season = SeasonDaytimeMaster::
                Where(function ($query) use ($start_date) {
                    $query->where('start_date', '<=', $start_date);
                    $query->where('end_date', '>=', $start_date);
                })
                ->orWhere(function ($query) use ($end_date) {
                    $query->where('start_date', '<=', $end_date);
                    $query->where('end_date', '>=', $end_date);
                })->pluck('name');
    
            $daytime = SeasonDaytimeMaster::
                Where(function ($query) use ($start_time) {
                    $query->where('start_time', '<=', $start_time);
                    $query->where('end_date', '>=', $start_time);
                })
                ->orWhere(function ($query) use ($end_time) {
                    $query->where('start_time', '<=', $end_time);
                    $query->where('end_time', '>=', $end_time);
                })->pluck('name'); */
        }else {
                return $this->sendResponse(false, 'Insufficient Data');
        }

         if (isset($_GET['course_id']) && isset($data['season']) && isset($data['daytime']) && isset($_GET['no_of_days']) && isset($_GET['cal_payment_type'])) {
            //$course_detail_data = CourseDetail::where('session', $_GET['session'])->where('course_id', $_GET['course_id'])->where('time', $_GET['time'])->where('no_of_days', $_GET['no_of_days'])->where('cal_payment_type', $_GET['cal_payment_type'])->first();
            $course_detail_data = $this->checkCourseForDays($data['season'],$_GET['course_id'],$data['daytime'],$_GET['no_of_days'],$_GET['cal_payment_type']);
        } 
        elseif (isset($_GET['course_id']) && isset($data['season']) && isset($data['daytime']) && isset($_GET['hours_per_day']) && isset($_GET['cal_payment_type'])) {
                    $course_detail_data = $this->checkCourseForHours($data['season'],$_GET['course_id'],$data['daytime'],$_GET['hours_per_day'],$_GET['cal_payment_type']);
                    //$course_detail_ids1 = CourseDetail::where('session', $_GET['session'])->where('time', $_GET['time'])->where('course_id', $_GET['course_id'])->where('hours_per_day', $_GET['hours_per_day'])->where('cal_payment_type', $_GET['cal_payment_type'])->first();
                    //$course_detail_data = $course_detail_ids1;
        }
        /* else {
            return $this->sendResponse(false, 'Insufficient Data');
        } */


        if (empty($course_detail_data)) {
            $course_detail_data = new class {
            };
        }
        return $this->sendResponse(true, 'success', $course_detail_data);
    }

    //Check course exist for given day
    public function checkCourseForDays($session,$course_id,$time,$no_of_days,$cal_payment_type) 
    {
        if($no_of_days == 0) {
            return null;
        }
        $course_detail_data = CourseDetail::where('session', $session)->where('course_id', $course_id)->where('no_of_days', $no_of_days)->where('cal_payment_type', $cal_payment_type);

        /**If get course with input details other wise get default value base course */
        $course_detail_data = $course_detail_data->where(function ($q) use ($time) {
            $q->where('time', $time);
            $q->orWhere('time', 'Whole Day');
        })->first();

        if($course_detail_data) {
            return $course_detail_data;
        }
        $no_of_days = $no_of_days - 1 ;
        $data = $this->checkCourseForDays($session,$course_id,$time,$no_of_days,$cal_payment_type);
        if($data) return $data;
    }

    //Check course exist for given hours
    public function checkCourseForHours($session,$course_id,$time,$hours_per_day,$cal_payment_type) 
    {
        if($hours_per_day == 0) {
            return null;
        }
        $course_detail_data = CourseDetail::where('session', $session)->where('course_id', $course_id)->where('hours_per_day', $hours_per_day)->where('cal_payment_type', $cal_payment_type);

        /**If get course with input details other wise get default value base course */
        $course_detail_data = $course_detail_data->where(function ($q) use ($time) {
            $q->where('time', $time);
            $q->orWhere('time', 'Whole Day');
        })->first();

        if($course_detail_data) {
            return $course_detail_data;
        }
        $hours_per_day = $hours_per_day - 1 ;
        $data = $this->checkCourseForHours($session,$course_id,$time,$hours_per_day,$cal_payment_type);
        if($data) return $data;
    }

    //
    public function assignTeachingMaterialCorse(Request $request)
    {
        $v = validator($request->all(), [
            'course_id' => 'required|integer|min:1',
            'material_detail' => 'array',
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        
        $id = $request->course_id;
        $course = Course::find($id);
        if (!$course) {
            return $this->sendResponse(false, 'Course not found');
        }
        $teaching_material = array();
        $c=0;
        if ($request->material_detail) {
            CourseTeachingMaterialDetail::where('course_id', $id)->delete();
            $material = array();
            $material_detail1 = $request->material_detail;
            foreach ($material_detail1 as $key => $material_id) {
                $teaching_material = TeachingMaterial::find($material_id);
                if (!$teaching_material) {
                    $c = $c+1;
                }
            }
            if ($c>0) {
                return $this->sendResponse(false, 'Teaching Material not found');
            }

            $courseDetail = array_map(function ($material_data) use ($course, $material,$c) {
                $material['teaching_material_id'] = $material_data;
                $material['course_id'] = $course->id;
                $material['created_at'] = date("Y-m-d H:i:s");
                $material['updated_at'] = date("Y-m-d H:i:s");
                return $material;
            }, $request['material_detail']);
            
            CourseTeachingMaterialDetail::insert($courseDetail);
        }

        return $this->sendResponse(true, __('strings.course_teaching_material_update_success'));
    }

    /**Get difficulty details */
    public function getDifficultyLevel()
    {
       $difficultyLevel = CourseDifficultyLevel::get();
       return $this->sendResponse(true, __('success'),$difficultyLevel);
    }

    /**Bulk update course is display on website status */
    public function updateDisplayWebsiteStatus(Request $request)
    {
        /**Validation rules */
        $v = validator($request->all(), [
            'course_ids' => 'required|array',
            'is_display_on_website' => 'required|boolean',
        ]);

        /**Check validation invalid then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /**Update each course is_display_on_website value */
        foreach($request->course_ids as $id){
            $course = Course::find($id);
            /**Course not found then return error response and transactions rollback */
            if(!$course){
                return $this->sendResponse(false, __('strings.course_not_found'));
            }
            $course->is_display_on_website = $request->is_display_on_website;
            $course->save();
        }
        /**Return success response with json encoded */
        return $this->sendResponse(true, __('strings.course_update_success'));
    }

    /**Update course is archived status */
    public function updateArchivedStatus(Request $request)
    {
        /**Validation rules */
        $v = validator($request->all(), [
            'course_id' => 'required|exists:courses,id',//course id is must be exist in course table
            'is_archived' => 'required|boolean',
        ]);

        /**Check validation invalid then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        /**Update course is_archived value */
        $course = Course::find($request->course_id);
        /**Course not found then return error response and transactions rollback */
        if(!$course){
            return $this->sendResponse(false, __('strings.course_not_found'));
        }
        $course->is_archived = $request->is_archived;
        $course->save();
        /**Return success response with json encoded */
        return $this->sendResponse(true, __('strings.course_update_success'));
    }

    /**Update course is archived status */
    public function deleteCourse($id)
    {
        $course = Course::find($id);
        /**Course not found then return error response and transactions rollback */
        if(!$course){
            return $this->sendResponse(false, __('strings.course_not_found'));
        }

        $course = $course->where('id',$id)->where('is_archived',1)->first();
        /**Archived course not found then return error response and transactions rollback */
        if(!$course){
            return $this->sendResponse(false, __('strings.archived_course_not_found'));
        }
        $course_exist_booking = BookingProcessCourseDetails::where('course_id',$id)->first();

        $course_exist_estimate_booking = BookingEstimate::where('course_id',$id)->first();

        if($course_exist_booking || $course_exist_estimate_booking){
            return $this->sendResponse(false, __('strings.course_exist_in_booking'));
        }
        $course->delete();
        /**Return success response with json encoded */
        return $this->sendResponse(true, __('strings.course_delete_success'));
    }

    /**Export course base booking details */
    public function exportCourseBaseBookings()
    {
        $course_ids = Course::where('is_active', true)->pluck('id');
        if((isset($_GET['start_date']) && $_GET['start_date']) && (isset($_GET['end_date']) && $_GET['end_date'])){
            $start_date = $_GET['start_date'];
            $end_date = $_GET['end_date'];

            $course_ids = BookingProcessCourseDetails::whereDate('start_date','>=', $_GET['start_date'])
            ->whereDate('end_date','<=', $_GET['end_date'])
            ->pluck('course_id')->toArray();
            $course_ids = array_unique($course_ids);
        }
        $i = 0;
        $total_price_sum = 0;
        $cancelled_total_sum = 0;
        $vat_amount_sum = 0;
        $vat_excluded_amount_sum = 0;
        $net_price_sum = 0;
        $discounted_amount_sum = 0;
        $column_base_total_amount = 0;
        $axtended_net_price_sum = 0;

        $course_report_data = array();
        
        foreach($course_ids as $id){
            $course = Course::find($id);
            $course_report_data[$i]['course_name'] = ($course ? $course->name : null);
            
            $total_bookings = BookingProcessCourseDetails::where('course_id', $id)
            ->count();
            $course_report_data[$i]['total_bookings'] = $total_bookings;

            $total_price = BookingProcessCourseDetails::join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'booking_process_course_details.booking_process_id')
            ->where('course_id', $id)
            ->sum('bpd.total_price');
            $course_report_data[$i]['total_price'] = round($total_price, 2);
            $total_price_sum = $total_price_sum + $course_report_data[$i]['total_price'];

            $cancelled_total = BookingProcessCourseDetails::join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'booking_process_course_details.booking_process_id')
            ->where('course_id', $id)
            ->where('bpd.is_cancelled', true)
            ->sum('bpd.net_price');
            $course_report_data[$i]['cancelled_total'] = round($cancelled_total, 2);
            $cancelled_total_sum = $cancelled_total_sum + $course_report_data[$i]['cancelled_total'];

            $vat_amount = BookingProcessCourseDetails::join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'booking_process_course_details.booking_process_id')
            ->where('course_id', $id)
            ->sum('bpd.vat_amount');
            $course_report_data[$i]['vat_amount'] = round($vat_amount, 2);
            $vat_amount_sum = $vat_amount_sum + $course_report_data[$i]['vat_amount'];

            $course_report_data[$i]['vat_percentage'] = $this->getVat();
            
            $vat_excluded_amount = BookingProcessCourseDetails::join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'booking_process_course_details.booking_process_id')
            ->where('course_id', $id)
            ->sum('bpd.vat_excluded_amount');
            $course_report_data[$i]['vat_excluded_amount'] = round($vat_excluded_amount, 2);
            $vat_excluded_amount_sum = $vat_excluded_amount_sum + $course_report_data[$i]['vat_excluded_amount'];
            
            $net_price = BookingProcessCourseDetails::join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'booking_process_course_details.booking_process_id')
            ->where('course_id', $id)
            ->sum('bpd.net_price');
            $course_report_data[$i]['net_price'] = $net_price;
            $net_price_sum = $net_price_sum + $net_price;

            $axtended_net_price = BookingProcessCourseDetails::join('booking_process_payment_details as bpd', 'bpd.booking_process_id', '=', 'booking_process_course_details.booking_process_id')
            ->where('course_id', $id)
            ->where('bpd.is_new_invoice', true)
            ->sum('bpd.net_price');
            $course_report_data[$i]['axtended_net_price'] = round($axtended_net_price, 2);
            $axtended_net_price_sum = $axtended_net_price_sum + $axtended_net_price;

            $course_report_data[$i]['discounted_amount'] = $total_price - $net_price;
            $discounted_amount_sum = $discounted_amount_sum + ($total_price - $net_price);

            $course_report_data[$i]['row_base_total_amount'] = $total_price + $cancelled_total + $vat_amount + $vat_excluded_amount + $net_price + $axtended_net_price;

            $column_base_total_amount = $column_base_total_amount + $course_report_data[$i]['row_base_total_amount'];
            $i = $i + 1;
        }
        $data['course_data'] = $course_report_data;
        $data['total_price_sum'] = $total_price_sum;
        $data['cancelled_total_sum'] = $cancelled_total_sum;
        $data['vat_amount_sum'] = $vat_amount_sum;
        $data['vat_excluded_amount_sum'] = $vat_excluded_amount_sum;
        $data['net_price_sum'] = $net_price_sum;
        $data['discounted_amount_sum'] = $discounted_amount_sum;
        $data['column_base_total_amount'] = $column_base_total_amount;
        $data['axtended_net_price_sum'] = $axtended_net_price_sum;

        $booking_process_ids = BookingProcessCourseDetails::whereIn('course_id', $course_ids)->pluck('booking_process_id');

        $payment_ids = BookingProcessPaymentDetails::whereIn('booking_process_id', $booking_process_ids)
        ->where('payment_id', '!=', null)
        ->pluck('payment_id');

        $payments = BookingPayment::with('payment_type_detail')
        ->with('invoice_detail')
        ->whereIn('id', $payment_ids)
        ->orderBy('id', 'desc')->get();

        $payment_amonut = 0;
        $card_type_reverce_amount = 0;
        $payment_type_base = 0;
        $payment_card_reverce_amount = 0;
        $credit_card_amount = 0;
        $payment_method = array();
        $credit_card_type = array();
        $payment_card_type = array();
        $credit_card_type_details = array();
        $payment_card_base_details = array();
        $payment_method_base_details = array();

        foreach($payments as $payment){
            if(in_array($payment['payment_card_brand'], $payment_card_type)){
                $payment_amonut = $payment_amonut + $payment['total_net_amount'];
            }else{
                $payment_card_type[] = $payment['payment_card_brand']; 
                // $payment_amonut = 0;
                $payment_amonut = $payment_amonut + $payment['total_net_amount'];
            }

            if($payment['payment_type'] && in_array($payment['payment_type'], $payment_method)){
                $payment_type_base = $payment_type_base + $payment['total_net_amount'];
            }else{
                $payment_method[] = $payment['payment_type']; 
                // $payment_type_base = 0;
                $payment_type_base = $payment_type_base + $payment['total_net_amount'];
            }
            $payment_method_base_details[$payment['payment_type_detail']['type']] = $payment_type_base;

            if($payment['credit_card_type'] && in_array($payment['credit_card_type'], $credit_card_type)){
                $credit_card_amount = $credit_card_amount + $payment['total_net_amount'];
            }else{
                $credit_card_type[] = $payment['credit_card_type']; 
                // $credit_card_amount = 0;
                $credit_card_amount = $credit_card_amount + $payment['total_net_amount'];
            }

            if(!$payment['payment_card_brand']){
                // continue;
            }else{
                $payment_card_base_details[$payment['payment_card_brand']]['total_payment_amonut'] = $payment_amonut;

                $payment_card_base_details[$payment['payment_card_brand']]['payment_card_reverce_amount'] = 0;
                if(!$payment['invoice_detail'][0]['vat_percentage']){
                    $payment_card_reverce_amount = $payment_card_reverce_amount + $payment['total_net_amount'];
                    $payment_card_base_details[$payment['payment_card_brand']]['payment_card_reverce_amount'] = $payment_card_reverce_amount;
                }
            }
            if(empty($payment['credit_card_type'])){
                // continue;
            }else{
                $credit_card = $payment->credit_card_detail->name;
                $credit_card_type_details[$credit_card]['total_payment_amonut'] = $credit_card_amount;
                
                $credit_card_type_details[$credit_card]['card_type_reverce_amount'] = 0;
                if(!$payment['invoice_detail'][0]['vat_percentage']){
                    $card_type_reverce_amount = $card_type_reverce_amount + $payment['total_net_amount'];
                    $credit_card_type_details[$credit_card]['card_type_reverce_amount'] = $card_type_reverce_amount;
                }
            }

        }
        /**Find remain payment method base data */
        $types = PaymentMethod::pluck('type')->toArray();
        foreach($payment_method_base_details as $key => $val){
            $keys[] = $key;
        }
        $empty_type = array_diff($types, $keys);
        
        foreach($empty_type as $val){
            $payment_method_remain_details[$val] = 0;
        }
        $payment_method_base_details = array_merge($payment_method_base_details, $payment_method_remain_details);
        /** */

        /**Find remain credit card base data */
        $keys = array();
        $credit_cards_names = CreditCardMaster::pluck('name')->toArray();
        foreach($credit_card_type_details as $key => $val){
            $keys[] = $key;
        }
        $empty_credit_card_types = array_diff($credit_cards_names, $keys);
        foreach($empty_credit_card_types as $val){
            $credit_card_remain_details[$val]['total_payment_amonut'] = 0;
            $credit_card_remain_details[$val]['card_type_reverce_amount'] = 0;
        }
        $credit_card_type_details = array_merge($credit_card_type_details, $credit_card_remain_details);
        /** */
        
        return Excel::download(new CourseBaseBookingExport($data, $payment_card_base_details, $payment_method_base_details, $credit_card_type_details), 'CourseBaseBookingsAmount.csv');
    }
}
