<?php

namespace App\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\NotificationType;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCourseDetails;


class Notification extends Model
{
    //protected $table="notifications";

    protected $fillable = ['sender_id','receiver_id','email_scheduler_type','type','status','is_read','message','booking_process_id','reject_reason','sub_child_id'];
   // public $timestamps = false;

    public function sender()
     {
          return $this->belongsTo(Contact::class,'sender_id');
     }

     public function receiver()
     {
          return $this->belongsTo(Contact::class,'receiver_id');
     }

     public function notificationType()
     {
          return $this->belongsTo(NotificationType::class,'type');
     }
     public function bookingData()
     {
          return $this->belongsTo(BookingProcesses::class,'booking_process_id');
     }
     public function bookingCourseData()
     {
          return $this->hasOne(BookingProcessCourseDetails::class,'booking_process_id','booking_process_id');
     }
}
