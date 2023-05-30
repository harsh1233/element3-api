<?php

namespace App\Models\BookingProcess;

use Illuminate\Database\Eloquent\Model;
use App\Models\BookingProcess\BookingPayment;
use App\Models\BookingProcess\BookingProcesses;

class InvoicePaymentHistory extends Model
{
    protected $table = 'invoice_payment_history';

    public $timestamps = false;
    
    protected $fillable = [
        'booking_process_id',
        'invoice_id',
        'booking_payment_id',
        'amount',
        'created_at',
        'created_by'
    ];

    public function payment_detail()
    {
        return $this->belongsTo(BookingPayment::class, 'booking_payment_id');
    }

    public function booking_detail()
    {
        return $this->belongsTo(BookingProcesses::class, 'booking_process_id');
    }
}
