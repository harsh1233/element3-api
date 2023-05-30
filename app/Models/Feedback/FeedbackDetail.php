<?php

namespace App\Models\Feedback;

use App\Models\Feedback\Feedback;
use Illuminate\Database\Eloquent\Model;
use App\Models\Feedback\FeedbackQuestion;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeedbackDetail extends Model
{
    use SoftDeletes;
    
    protected $fillable = [
        'feedback_id',
        'question_id',
        'is_element3',
        'rating',
        'created_by',
        'updated_by',
    ];

    public function feedback()
    {
        return $this->belongsTo(Feedback::class,'feedback_id');
    }

    public function feedback_question()
    {
        return $this->belongsTo(FeedbackQuestion::class,'question_id');
    }
}
