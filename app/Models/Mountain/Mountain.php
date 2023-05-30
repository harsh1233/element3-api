<?php

namespace App\Models\Mountain;

use App\Models\Mountain\MountainSlope;
use Illuminate\Database\Eloquent\Model;
use App\Models\Mountain\MountainSkiLift;

class Mountain extends Model
{
    protected $fillable = ['name'];

    public function ski_lift_detail()
    {
        return $this->hasMany(MountainSkiLift::class,'mountain_id');
    }

    public function slope_detail()
    {
        return $this->hasMany(MountainSlope::class,'mountain_id');
    }
}
