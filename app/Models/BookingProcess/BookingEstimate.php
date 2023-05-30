<?php

namespace App\Models\BookingProcess;

use App\Models\Contact;
use App\Models\Courses\Course;
use App\Models\Courses\CourseDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingEstimate extends Model
{
    use SoftDeletes;
    protected $fillable = [
    'estimate_number',
    'customer_id',
    'customer_name',
    'course_id',
    'course_detail_id',
    'start_date',
    'end_date',
    'start_time',
    'end_time',
    'customer_mobile'
    ,'customer_email',
    'total_price',
    'discount',
    'net_price',
    'vat_percentage',
    'vat_excluded_amount',
    'vat_amount',
    'is_include_lunch',
    'include_lunch_price',
    'lunch_vat_amount',
    'lunch_vat_excluded_amount',
    'lunch_vat_percentage',
    'invoice_link',
    'created_by',
    'settlement_amount',
    'settlement_description',
    'updated_by'];

    public function customer_data()
    {
        return $this->belongsTo(Contact::class,'customer_id');
    }
    public function course_detail_data()
    {
        return $this->belongsTo(CourseDetail::class,'course_detail_id');
    }
    public function course_data()
    {
        return $this->belongsTo(Course::class,'course_id');
    }

}
