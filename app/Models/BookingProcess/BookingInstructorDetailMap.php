<?php

namespace App\Models\BookingProcess;

use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcesses;

class BookingInstructorDetailMap extends Model
{
    use SoftDeletes;
    protected $fillable = ['contact_id','booking_process_id','startdate_time','enddate_time','created_by','updated_by'];

    protected $appends = ['payroll_actual_hour'];

    public function bookings()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_process_id');
    }

    public function getPayrollActualHourAttribute()
    {
        $start_date = $this->startdate_time;
        $end_date = $this->enddate_time;
        $datetime1 = new DateTime($start_date);
        $datetime2 = new DateTime($end_date);
        $interval = $datetime1->diff($datetime2);
        $hours = $interval->format('%h');
        return  $hours;
    }
}
