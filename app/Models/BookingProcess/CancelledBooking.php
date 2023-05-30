<?php

namespace App\Models\BookingProcess;

use Illuminate\Database\Eloquent\Model;

class CancelledBooking extends Model
{
    protected $fillable = [
        'booking_id',
        'customer_id',
        'payee_id',
        'date',
        'time',
        'course_id',
        'cash_taken_out',
        'cancellation_fee',
        'money_back_amount',
        'payback_method',
        'cancellation_receipt',
        'voucher_code'
    ];

    public static function boot()
    {
        parent::boot();

        if(auth()->user()){
            // create a event to happen on creating
            static::creating(function ($record) {
                $record->created_at = date("Y-m-d H:i:s");
                $record->created_by = auth()->user()->id;
            });
    
            // create a event to happen on updating
            static::updating(function ($record) {
                $record->updated_at = date("Y-m-d H:i:s");
                $record->updated_by = auth()->user()->id;
            });
        }
    }
}
