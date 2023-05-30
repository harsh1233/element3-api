<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    public $timestamps = false;
    protected $fillable = ['type','is_active'];
}
