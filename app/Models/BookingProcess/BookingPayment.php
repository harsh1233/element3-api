<?php

namespace App\Models\BookingProcess;

use App\Models\Contact;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\ConcardisTransaction;
use App\Models\CreditCardMaster;

class BookingPayment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'payment_number',
        'office_id',
        'qbon_number',
        'contact_id',
        'payment_type',
        'payment_card_type',
        'payment_card_brand',
        'is_office',
        'total_amount',
        'total_discount',
        'total_vat_amount',
        'total_net_amount',
        'total_lunch_amount',
        'amount_given_by_customer',
        'amount_returned',
        'created_by',
        'updated_by',
        'total_lunch_amount',
        'total_lunch_vat_amount',
        'credit_card_type',
        'cash_amount',
        'creditcard_amount'
    ];

    public function payee_detail()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function invoice_detail()
    {
        return $this->hasMany(BookingProcessPaymentDetails::class, 'payment_id');
    }

    public function payment_type_detail()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_type');
    }

    public function concardis_transaction()
    {
        return $this->hasOne(ConcardisTransaction::class, 'payment_id');
    }

    public function credit_card_detail()
    {
        return $this->belongsTo(CreditCardMaster::class, 'credit_card_type');
    }

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->created_at = date("Y-m-d H:i:s");
            $record->created_by = auth()->user() ? auth()->user()->id : '2';
        });

        // create a event to happen on updating
        static::updating(function ($record) {
            $record->updated_at = date("Y-m-d H:i:s");
            $record->updated_by = auth()->user() ? auth()->user()->id : '2';
        });
    }
}
