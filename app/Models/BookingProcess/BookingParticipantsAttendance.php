<?php

namespace App\Models\BookingProcess;

use Illuminate\Database\Eloquent\Model;
use App\Models\Contact;

class BookingParticipantsAttendance extends Model
{
    /**Below defined fields are manage with controller to booking_participants_attendances table */
    protected $fillable = ['booking_process_id','customer_id','instructor_id','attendance_date','is_attend','comment'];

    /**Get customer details */
    public function customer_detail()
    {
        return $this->belongsTo(Contact::class,'customer_id');
    }

    /**Get instructor details */
    public function instructor_detail()
    {
        return $this->belongsTo(Contact::class,'instructor_id');
    }


    /**For record create or update time then update some fields */
    public static function boot()
    {
        parent::boot();

        /**For get which user call this API and store in table */
        $user_id = auth()->user()->id;

        // create a event to happen on creating
        static::creating(function ($record) use($user_id){
            $record->created_at = date("Y-m-d H:i:s");
            $record->created_by = $user_id;
        });

        // create a event to happen on updating
        static::updating(function ($record) use($user_id){
            $record->updated_at = date("Y-m-d H:i:s");
            $record->updated_by = $user_id;
        });
    }
}
