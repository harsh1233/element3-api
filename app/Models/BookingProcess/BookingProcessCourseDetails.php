<?php

namespace App\Models\BookingProcess;

use App\Models\Contact;
use App\Models\Category;
use App\Models\Courses\Course;
use App\Models\Courses\CourseDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessSource;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessLanguageDetails;
use App\Models\BookingProcess\BookingProcessExtraParticipant;
use App\Models\BookingProcess\BookingProcessInstructorDetails;
use App\Models\Courses\CourseDifficultyLevel;

class BookingProcessCourseDetails extends Model
{
    use SoftDeletes;
    protected $fillable = ['booking_process_id','course_id','StartDate_Time','EndDate_Time','start_date','end_date','start_time','end_time','course_type','lead','contact_id','source_id','course_detail_id','no_of_instructor','no_of_participant','meeting_point_id','meeting_point','meeting_point_lat','meeting_point_long','difficulty_level','is_extra_participant','no_of_extra_participant','created_by','updated_by','total_days','total_hours','lunch_hour','lunch_start_time','lunch_end_time'];

    protected $appends = ['date_status','date_status_id'];
    
    public function contact_data()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }
    public function course_detail_data()
    {
        return $this->belongsTo(CourseDetail::class,'course_detail_id');
    }

    public function instructor_detail()
    {
        return $this->hasMany(BookingProcessInstructorDetails::class,'booking_process_id');
    }
    public function lead_data()
    {
        return $this->belongsTo(Category::class,'lead');
    }
    public function sourse_data()
    {
        return $this->belongsTo(BookingProcessSource::class,'source_id');
    }
    public function difficulty_level_detail()
    {
        return $this->belongsTo(CourseDifficultyLevel::class,'difficulty_level');
    }
    public function course_data()
    {
        return $this->belongsTo(Course::class,'course_id');
    }
    public function customer_detail()
    {
        return $this->hasMany(BookingProcessCustomerDetails::class,'booking_process_id');
    }
    public function booking_process_detail()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_process_id');
    }
    public function extra_participant_detail()
    {
        return $this->hasMany(BookingProcessExtraParticipant::class,'booking_process_id');
    }

    public function payment_detail()
    {
        return $this->hasMany(BookingProcessPaymentDetails::class,'booking_process_id');
    }
    public function language_detail()
    {
        return $this->hasMany(BookingProcessLanguageDetails::class,'booking_process_id');
    }

    public function getDateStatusAttribute()
    {
        $start_date = $this->StartDate_Time;
        $end_date = $this->EndDate_Time;
        $current_date = date('Y-m-d H:i:s');
            if($start_date > $current_date){
                return 'Upcoming';
            }
            if($end_date < $current_date){
                return 'Past';
            }
            if($start_date <= $current_date && $end_date >= $current_date){
                return 'Ongoing';
            }
    }
    public function getDateStatusIdAttribute()
    {
        $start_date = $this->StartDate_Time;
        $end_date = $this->EndDate_Time;
        $current_date = date('Y-m-d H:i:s');
            if($start_date > $current_date){
                return '1';
            }
            if($end_date < $current_date){
                return '0';
            }
            if($start_date <= $current_date && $end_date >= $current_date){
                return '2';
            }
    }
}
