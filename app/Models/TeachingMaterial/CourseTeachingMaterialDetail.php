<?php

namespace App\Models\TeachingMaterial;

use Illuminate\Database\Eloquent\Model;
use App\Models\TeachingMaterial\TeachingMaterial;

class CourseTeachingMaterialDetail extends Model
{
    protected $table = 'course_teaching_material_detail';
    protected $fillable = ['course_id','teaching_material_id','created_at','updated_at'];

    public function teaching_material_data()
    {
        return $this->belongsTo(TeachingMaterial::class,'teaching_material_id');
    }
}
