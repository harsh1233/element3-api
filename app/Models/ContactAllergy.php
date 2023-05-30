<?php

namespace App\Models;

use App\Models\Allergy;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class ContactAllergy extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['allergy_id','contact_id'];

    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function allergy()
    {
        return $this->belongsTo(Allergy::class,'allergy_id');
    }
}
