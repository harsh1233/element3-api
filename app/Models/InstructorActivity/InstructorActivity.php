<?php

namespace App\Models\InstructorActivity;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcesses;

class InstructorActivity extends Model
{
    use SoftDeletes;

    protected $fillable = ['instructor_id','booking_id','activity_type','activity_date','activity_time','created_by','updated_by'];

    public function booking_detail()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_id');
    }

    public function instructor_detail()
    {
        return $this->belongsTo(User::class,'instructor_id');
    }
}
