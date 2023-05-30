<?php

namespace App\Models;

use App\Models\Contact;
use App\Models\Country;
use Illuminate\Database\Eloquent\Model;

class ContactAddress extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['contact_id','type','street_address1','street_address2','city','state','country','zip'];

    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function country_detail()
    {
        return $this->belongsTo(Country::class,'country');
    }
}
