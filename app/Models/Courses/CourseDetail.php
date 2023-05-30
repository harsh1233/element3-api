<?php

namespace App\Models\Courses;

use App\Models\Courses\Course;
use Illuminate\Database\Eloquent\Model;

class CourseDetail extends Model
{
    protected $fillable = ['course_id','session','time','hours_per_day','price_per_day','no_of_days','extra_person_charge','created_at','updated_at','is_include_lunch','include_lunch_price','cal_payment_type','total_price'];

    public function course_data()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }
}
