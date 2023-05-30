<?php

namespace App\Models\BookingProcess;

use App\Models\Contact;
use Illuminate\Support\Str;
use App\Models\PaymentMethod;
use App\Models\Finance\Voucher;
use App\Models\CreditCardMaster;
use App\Models\Courses\CourseDetail;
use App\Models\SeasonTicketManagement;
use Illuminate\Database\Eloquent\Model;
use App\Models\SubChild\SubChildContact;
use App\Models\BookingProcess\BookingPayment;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\CancelledBooking;
use App\Models\BookingProcess\InvoicePaymentHistory;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;

class BookingProcessPaymentDetails extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'booking_process_id',
        'booking_process_customer_detail_id',
        'invoice_number',
        'uuid',
        'refund_payment',
        'customer_id',
        'payment_method_id',
        'status',
        'payment_link',
        'invoice_link',
        'total_price',
        'extra_person_charge',
        'payi_id',
        'no_of_days',
        'course_detail_id',
        'price_per_day',
        'extra_participant',
        'discount',
        'net_price',
        'is_new_invoice',
        'no_invoice_sent',
        'cal_payment_type',
        'is_include_lunch',
        'include_lunch_price',
        'vat_percentage',
        'vat_amount',
        'vat_excluded_amount',
        'lunch_vat_amount',
        'lunch_vat_excluded_amount',
        'lunch_vat_percentage',
        'voucher_id',
        'include_extra_price',
        'payment_id',
        'created_by',
        'updated_by',
        'lunch_vat_percentage',
        'lunch_vat_amount',
        'lunch_vat_excluded_amount',
        'is_include_lunch',
        'include_lunch_price',
        'cal_payment_type',
        'tax_consultant',
        'settlement_amount',
        'settlement_description',
        'season_ticket_number',
        'is_cancelled',
        'cancel_id',
        'outstanding_amount',
        'is_reverse_charge',
        'credit_card_type',
        'sub_child_id'
    ];

    protected $appends = ['reference_number'];

    public function payment_deta()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function customer()
    {
        return $this->belongsTo(Contact::class, 'customer_id');
    }

    public function payi_detail()
    {
        return $this->belongsTo(Contact::class, 'payi_id');
    }

    public function lead_datails()
    {
        return $this->hasOne(BookingProcessCourseDetails::class, 'booking_process_id', 'booking_process_id');
    }

    public function booking_customer_datails()
    {
        return $this->hasOne(BookingProcessCustomerDetails::class, 'booking_process_id', 'booking_process_id');
    }

    public function payment_detail()
    {
        return $this->belongsTo(BookingPayment::class, 'payment_id');
    }

    public function booking_detail()
    {
        return $this->belongsTo(BookingProcesses::class, 'booking_process_id');
    }

    public function course_detail()
    {
        return $this->belongsTo(CourseDetail::class, 'course_detail_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'voucher_id');
    }

    public function invoice_history()
    {
        return $this->hasMany(InvoicePaymentHistory::class, 'invoice_id');
    }

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $uuid = Str::random(50);
            $url = config('constants.crm_payment_page').'/'.$uuid;

            $record->uuid = $uuid;
            $record->payment_link = $url;
        });
    }

    public function season_ticket_details()
    {
        return $this->belongsTo(SeasonTicketManagement::class,'season_ticket_number','ticket_number');
    }

    public function cancell_details()
    {
        return $this->belongsTo(CancelledBooking::class,'cancel_id','id');
    }

    // Accessor
    public function getReferenceNumberAttribute()
    {
        $reference = null;
        if($this->status != 'Success'){
            $exploded_invoice_number = explode('INV', $this->invoice_number);
            $reference = ( isset($exploded_invoice_number[1]) ? $exploded_invoice_number[1] : null);
        }
        return $reference;
    }

    public function credit_card_detail()
    {
        return $this->belongsTo(CreditCardMaster::class, 'credit_card_type');
    }

    public function sub_child_detail()
    {
        return $this->belongsTo(SubChildContact::class,'sub_child_id');
    }
}
