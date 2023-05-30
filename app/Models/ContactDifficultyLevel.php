<?php

namespace App\Models;

use App\Models\Contact;
use App\Models\InstructorLevel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactDifficultyLevel extends Model
{
    //use SoftDeletes;
    
    protected $fillable = ['contact_id','difficulty_level_id'];

    public function difficulty_level_detail()
    {
        return $this->belongsTo(InstructorLevel::class, 'difficulty_level_id');
    }

    public function contact_detail()
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }
}
