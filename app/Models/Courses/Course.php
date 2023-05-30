<?php

namespace App\Models\Courses;

use App\Models\InstructorLevel;
use App\Models\Courses\CourseDetail;
use App\Models\Courses\CourseCategory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Courses\CourseDifficultyLevel;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\TeachingMaterial\CourseTeachingMaterialDetail;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\MeetingPoint;

class Course extends Model
{
    use SoftDeletes;
    protected $fillable = [
        'name',
        'name_en',
        'type',
        'category_id',
        'difficulty_level',
        'maximum_participant',
        'is_active',
        'is_feature_course',
        'created_by',
        'updated_by',
        'maximum_instructor',
        'notes',
        'notes_en',
        'course_banner',
        'created_at',
        'updated_at',
        'cal_payment_type',
        'is_display_on_website',
        'start_time',
        'end_time',
        'meeting_point_id',
        'restricted_start_time',
        'restricted_end_time',
        'price_per_item',
        'restricted_no_of_hours',
        'restricted_start_date',
        'restricted_end_date',
        'restricted_no_of_days',
        'is_include_lunch_hour'];

    public function category_detail()
    {
        return $this->belongsTo(CourseCategory::class,'category_id');
    }
    public function difficulty_level_detail()
    {
        return $this->belongsTo(CourseDifficultyLevel::class,'difficulty_level');
    }
    public function course_detail()
    {
        return $this->hasMany(CourseDetail::class,'course_id');
    }
    public function teaching_material_detail()
    {
        return $this->hasMany(CourseTeachingMaterialDetail::class,'course_id');
    }
    public function booking_course_detail()
    {
        return $this->hasOne(BookingProcessCourseDetails::class,'course_id');
    }
    public function booking_course_detail1()
    {
        return $this->hasMany(BookingProcessCourseDetails::class,'course_id');
    }
    public function meeting_point_detail(){
        return $this->belongsTo(MeetingPoint::class,'meeting_point_id');
    }
}
