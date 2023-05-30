<?php

namespace App\Models;

use App\Models\Category;
use Illuminate\Database\Eloquent\Model;

class Subcategory extends Model
{
    protected $fillable = ['name','category_id','created_by','updated_by','is_system'];

    public function category_detail()
    {
        return $this->belongsTo(Category::class,'category_id');
    }
}
