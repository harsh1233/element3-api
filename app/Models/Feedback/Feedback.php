<?php

namespace App\Models\Feedback;

use App\User;
use App\Models\Courses\Course;
use App\Models\Feedback\FeedbackDetail;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BookingProcess\BookingProcesses;

class Feedback extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'instructor_id',
        'customer_id',
        'booking_id',
        'course_id',
        'course_taken_date',
        'average_rating',
        'final_comment',
        'created_by',
        'updated_by',
    ];

    protected $dates = ['deleted_at'];

    public function instructor_detail()
    {
        return $this->belongsTo(User::class,'instructor_id');
    }

    public function customer_detail()
    {
        return $this->belongsTo(User::class,'customer_id');
    }

    public function booking_detail()
    {
        return $this->belongsTo(BookingProcesses::class,'booking_id');
    }

    public function course_detail()
    {
        return $this->belongsTo(Course::class,'course_id');
    }

    public function feedback_detail()
    {
        return $this->hasMany(FeedbackDetail::class,'feedback_id');
    }

    protected static function boot() 
    {
      parent::boot();
      static::deleting(function($feedback) {
         foreach ($feedback->feedback_detail()->get() as $detail) {
            $detail->delete();
         }
      });
    }
}
