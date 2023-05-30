<?php

namespace App\Models;

use App\Models\BookingProcess\BookingProcessPaymentDetails;
use Illuminate\Database\Eloquent\Model;

class ConcardisTransaction extends Model
{
    protected $fillable = [
        'invoice_id',
        'order_id',
        'fields_sent',
        'fields_received',
        'status',
        'payment_id'
    ];

    protected $casts = [
        'fields_sent' => 'json',
        'fields_received' => 'json'
    ];

    public function invoice()
    {
        return $this->belongsTo(BookingProcessPaymentDetails::class);
    }
}
