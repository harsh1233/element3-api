<?php

namespace App\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class ContactAdditionalPerson extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['contact_id','salutation','name','relationaship','mobile1','mobile2','comments'];

    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }
}
