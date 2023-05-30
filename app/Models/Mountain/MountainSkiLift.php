<?php

namespace App\Models\Mountain;

use App\Models\Mountain\Mountain;
use Illuminate\Database\Eloquent\Model;

class MountainSkiLift extends Model
{
    protected $fillable = ['name','mountain_id'];

    public function mountain_detail()
    {
        return $this->belongsTo(Mountain::class,'mountain_id');
    }
}
