<?php

namespace App\Models\TeachingMaterial;

use Illuminate\Database\Eloquent\Model;
use App\Models\TeachingMaterial\TeachingMaterialCategory;

class TeachingMaterial extends Model
{
    protected $table = 'teaching_material';
    
    protected $fillable = ['teaching_material_category_id','teaching_material_sub_category_id','name','formate','url','filename','is_active','created_by','updated_by','display_order'];

    public function teaching_material_category_detail()
    {
        return $this->belongsTo(TeachingMaterialCategory::class,'teaching_material_category_id');
    }

    public function teaching_material_sub_category_detail()
    {
        return $this->belongsTo(TeachingMaterialCategory::class,'teaching_material_sub_category_id');
    }
    
}
