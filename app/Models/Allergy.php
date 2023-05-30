<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Allergy extends Model
{
    use SoftDeletes;    
    protected $fillable = ['name','created_by','updated_by','is_system'];
}
