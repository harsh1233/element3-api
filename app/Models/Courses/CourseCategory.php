<?php

namespace App\Models\Courses;

use App\Models\Courses\Course;
use Illuminate\Database\Eloquent\Model;

class CourseCategory extends Model
{
    protected $fillable = ['name','name_en','type','is_active','created_by','updated_by'];

    public function courses()
    {
        return $this->hasMany(Course::class,'category_id');
    }
}
