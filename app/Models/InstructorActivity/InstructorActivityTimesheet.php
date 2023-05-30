<?php

namespace App\Models\InstructorActivity;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\InstructorActivity\InstructorActivity;
use App\Models\BookingProcess\BookingProcessCourseDetails;

class InstructorActivityTimesheet extends Model
{
    use SoftDeletes;

    protected $fillable = ['booking_id','instructor_id','activity_date','start_time','end_time','current_time','status','total_activity_hours','total_break_hours','created_by','updated_by','actual_start_time','actual_end_time','actual_hours','reject_reason','signature'];

    public function booking_detail()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_id');
    }

    public function booking_course_detail()
    {
        return $this->hasOne(BookingProcessCourseDetails::class,'booking_process_id','booking_id');
    }

    public function instructor_detail()
    {
        return $this->belongsTo(User::class,'instructor_id');
    }

}
