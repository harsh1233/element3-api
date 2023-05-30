<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Subcategory;

class Category extends Model
{
    public function subcategories()
    {
        return $this->hasMany(Subcategory::class,'category_id');
    }
}
