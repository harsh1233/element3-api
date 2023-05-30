<?php

namespace App\Models\BookingProcess;

use Illuminate\Database\Eloquent\Model;
use App\Models\Contact;
use App\Models\BookingProcess\BookingInstructorDetailMap;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingProcessInstructorDetails extends Model
{
    use SoftDeletes;
    protected $fillable = ['booking_process_id','contact_id','booking_token','is_course_confirmed','confirmed_at','created_by','updated_by','openfire_registration'];

    // protected $appends = ['course_status'];

    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function selected_booked_dates()
    {
        return $this->hasMany(BookingInstructorDetailMap::class,'contact_id','contact_id');
    }

    /* public function getCourseStatusAttribute()
    {
            $is_course_confirmed = $this->is_course_confirmed;
            if($is_course_confirmed === 1){
                return 'Cofirmed';
            }else
            {
                return 'Uncofirmed';
            }
    } */
}
