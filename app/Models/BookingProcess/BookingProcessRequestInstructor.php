<?php

namespace App\Models\BookingProcess;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcesses;

class BookingProcessRequestInstructor extends Model
{
    use SoftDeletes;
    
    protected $fillable = ['booking_process_id','contact_id'];

    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function booking()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_process_id');
    }
}
