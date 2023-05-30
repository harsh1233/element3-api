<?php

namespace App\Models\Feedback;

use App\Models\Feedback\FeedbackDetail;
use Illuminate\Database\Eloquent\Model;

class FeedbackQuestion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'question',
        'question_de',
        'is_under_eighteen',
        'is_element3',
    ];

    public function question_detail()
    {
        return $this->hasMany(FeedbackDetail::class,'question_id');
    }
}
