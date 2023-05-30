<?php

namespace App\Models\BookingProcess;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use App\Models\LeaveManagement\LeaveMst;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessLanguageDetails;
use App\Models\BookingProcess\BookingProcessExtraParticipant;
use App\Models\BookingProcess\BookingProcessInstructorDetails;
use App\Models\BookingProcess\BookingInstructorDetailMap;
use App\Models\BookingProcess\BookingProcessRequestInstructor;

class BookingProcesses extends Model
{
    use SoftDeletes;
    protected $fillable = ['booking_number','QR_number','is_draft','note','created_by','updated_by','is_third_party','is_cancel'];
    protected $appends = ['booking_qr'];

    public function course_detail()
    {
        return $this->hasOne(BookingProcessCourseDetails::class,'booking_process_id');
    }

    public function payi_detail()
    {
        return $this->belongsTo(Contact::class,'payi_id');
    }

    public function customer_detail()
    {
        return $this->hasMany(BookingProcessCustomerDetails::class,'booking_process_id');
    }

    public function extra_participant_detail()
    {
        return $this->hasMany(BookingProcessExtraParticipant::class,'booking_process_id');
    }

    public function instructor_detail()
    {
        return $this->hasMany(BookingProcessInstructorDetails::class,'booking_process_id');
    }
    public function payment_detail()
    {
        return $this->hasMany(BookingProcessPaymentDetails::class,'booking_process_id');
    }
    public function language_detail()
    {
        return $this->hasMany(BookingProcessLanguageDetails::class,'booking_process_id');
    }

    public function booking_instructor_map_detail()
    {
        return $this->hasMany(BookingInstructorDetailMap::class,'booking_process_id');
    }

    public function getBookingQrAttribute()
    {
        if($this->QR_number){
            $url = url('/').'/bookingProcessQr/'.$this->QR_number;
            $qr_code = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=".$url."&choe=UTF-8";
            return $qr_code;
        }
    }

    public function getRequest(){
        return $this->hasMany(LeaveMst::class,'booking_id');
    }

    public function request_instructor_detail()
    {
        return $this->hasMany(BookingProcessRequestInstructor::class,'booking_process_id');
    }
}
