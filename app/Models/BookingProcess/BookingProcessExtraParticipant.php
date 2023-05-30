<?php

namespace App\Models\BookingProcess;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingProcessExtraParticipant extends Model
{
     use SoftDeletes;
     protected $fillable = ['booking_process_id','name','age','created_by','updated_by', 'first_name', 'last_name', 'gender', 'dob', 'email', 'mobile', 'skiing_level'];
}
