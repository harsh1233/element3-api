<?php

namespace App\Models\SubChild;

use App\Models\Language;
use Illuminate\Database\Eloquent\Model;

class SubChildContactLanguage extends Model
{
    public $timestamps = false;
    protected $fillable = ['language_id','sub_childe_contact_id'];

    public function language()
    {
        return $this->belongsTo(Language::class,'language_id');
    }
}
