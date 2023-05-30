<?php

namespace App\Models\BookingProcess;

use Illuminate\Database\Eloquent\Model;
use App\Models\Language;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingProcessLanguageDetails extends Model
{
    use SoftDeletes;
    protected $fillable = ['booking_process_id','language_id','created_by','updated_by'];

    public function language()
    {
        return $this->belongsTo(Language::class,'language_id');
    }
}
