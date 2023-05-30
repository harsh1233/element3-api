<?php

namespace App\Models\TeachingMaterial;

use App\Models\TeachingMaterial\TeachingMaterial;
use App\Models\TeachingMaterial\TeachingMaterialCategory;
use Illuminate\Database\Eloquent\Model;

class TeachingMaterialCategory extends Model
{
    protected $fillable = ['name','is_active','parent_id','color','created_by','updated_by'];

    public function teaching_material_detail()
    {
        return $this->hasMany(TeachingMaterial::class,'teaching_material_sub_category_id');
    }

    public function parent_detail()
    {
        return $this->belongsTo(TeachingMaterialCategory::class,'parent_id');
    }

    //each category might have multiple children
   public function sub_category() {
    return $this->hasMany(static::class, 'parent_id')->orderBy('parent_id', 'asc');
   }
}
