<?php

namespace App\Models;

use App\Models\PaymentMethod;
use App\Models\Courses\Course;
use App\Models\Courses\CourseDetail;
use Illuminate\Database\Eloquent\Model;
use App\Models\SubChild\SubChildContact;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcessPaymentDetails;

class SeasonTicketManagement extends Model
{
    use SoftDeletes;
    protected $fillable = [
    'ticket_number',
    'customer_id',
    'customer_name',
    'customer_mobile',
    'customer_email',
    'course_id',
    'course_detail_id',
    'start_date',
    'end_date',
    'start_time',
    'end_time',
    'total_price',
    'discount',
    'net_price',
    'vat_percentage',
    'vat_amount',
    'vat_excluded_amount',
    'payment_method_id',
    'payment_status',
    'scaned_count',
    'sub_child_id'
    ];

    protected $appends = ['season_ticket_qr','bookings_total_amount'];

    /**Get customer details */
    public function customer_detail()
    {
        return $this->belongsTo(Contact::class,'customer_id');
    }

    /**Get course */
    public function course()
    {
        return $this->belongsTo(Course::class,'course_id');
    }

    /**Get course details */
    public function course_detail()
    {
        return $this->belongsTo(CourseDetail::class,'course_detail_id');
    }

    /**Get payment method details */
    public function payment_method_detail()
    {
        return $this->belongsTo(PaymentMethod::class,'payment_method_id');
    }

    /**Generate run time season ticket qr code from ticket number */
    public function getSeasonTicketQrAttribute(){
        $qr_code = null;
        if ($this->ticket_number) {
            $url = url('/').'/seasonTicketNumber/'.$this->ticket_number;
            $qr_code = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=".$url."&choe=UTF-8";
        }
        return $qr_code;
    }

    /**For record create or update time then update some fields */
    public static function boot()
    {
        parent::boot();

        /**For get which user call this API and store in table */
        $user_id = auth()->user()?auth()->user()->id:null;

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

    /**Get season ticket number base amount */
    public function getBookingsTotalAmountAttribute(){
        return BookingProcessPaymentDetails::where('season_ticket_number', $this->ticket_number)->sum('net_price');
    }

    /**Sub child details */
    public function sub_child_detail()
    {
        return $this->belongsTo(SubChildContact::class,'sub_child_id');
    }
}
