<?php

namespace App\Models\BookingProcess;

use DateTime;
use App\Models\Contact;
use App\Models\Courses\CourseDetail;
use App\Models\SeasonTicketManagement;
use Illuminate\Database\Eloquent\Model;
use App\Models\SubChild\SubChildContact;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\CancelledBooking;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;

class BookingProcessCustomerDetails extends Model
{
    use SoftDeletes;
    protected $fillable = ['booking_process_id','customer_id','is_customer_enrolled','additional_participant','accommodation','accommodation_other_name','payi_id','course_detail_id','StartDate_Time','EndDate_Time','start_date','end_date','start_time','end_time','cal_payment_type','is_include_lunch','include_lunch_price','no_of_days','hours_per_day','is_payi','is_updated','is_new_invoice','QR_number','created_by','updated_by','openfire_registration', 'season_ticket_number', 'attended_days', 'is_cancelled',
    'cancel_id','activity','is_include_lunch_hour','sub_child_id'];
 
    protected $appends = ['days_total','customer_qr'];
    
    public function customer()
    {
        return $this->belongsTo(Contact::class,'customer_id');
    }
    public function accommodation_data()
    {
        return $this->belongsTo(Contact::class,'accommodation');
    }

    public function booking_process_detail()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_process_id');
    }

    public function course_detail_data()
    {
        return $this->belongsTo(CourseDetail::class,'course_detail_id');
    }
     public function payi_detail()
    {
        return $this->belongsTo(Contact::class,'payi_id');
    }
    public function bookingProcessCourseDetails()
    {
        return $this->belongsTo(BookingProcessCourseDetails::class,'booking_process_id','booking_process_id');
    }
    public function bookingPaymentDetails()
    {
        return $this->belongsTo(BookingProcessPaymentDetails::class,'customer_id','customer_id');
    }

    public function getDaysTotalAttribute()
    {
        $data = array();
        $start_date = $this->StartDate_Time;
        $end_date = $this->EndDate_Time;
        $datetime1 = new DateTime($start_date);
        $datetime2 = new DateTime($end_date);
        $interval = $datetime1->diff($datetime2);
        $days = $interval->format('%a') + 1;
        $hours = $interval->format('%h');
        $data['days'] = $days;
        $data['hours'] = $hours;
        return  $data;
    }

    public function getCustomerQrAttribute()
    {
        if($this->QR_number){
            $url = url('/').'/customerQr/'.$this->QR_number;
            $customer_qr = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=".$url."&choe=UTF-8";
            return $customer_qr;
        }
    }
    
    public function season_ticket_details()
    {
        return $this->belongsTo(SeasonTicketManagement::class,'season_ticket_number','ticket_number');
    }    
    
    public function cancell_details()
    {
        return $this->belongsTo(CancelledBooking::class,'cancel_id','id');
    }

    public function sub_child_detail()
    {
        return $this->belongsTo(SubChildContact::class,'sub_child_id');
    }
}
