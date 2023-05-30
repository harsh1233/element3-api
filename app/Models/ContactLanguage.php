<?php

namespace App\Models;

use App\Models\Contact;
use App\Models\Language;
use Illuminate\Database\Eloquent\Model;

class ContactLanguage extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['language_id','contact_id'];

    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function language()
    {
        return $this->belongsTo(Language::class,'language_id');
    }
}
