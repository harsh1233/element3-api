<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonDaytimeMaster extends Model
{
    protected $fillable = ['name','start_date','end_date','start_time','end_time'];
}
