<?php

namespace App\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class CustomerUpdates extends Model
{
    protected $fillable = ['instructor_id','customer_id','description','urls','created_at','updated_at'];
 
     public function getUrlsAttribute($value)
    {
        $urls = [];
        if($value){
            $urls = explode(",", $value);
        }
        return $urls;
    }

    public function instructor_detail()
    {
        return $this->belongsTo(Contact::class,'instructor_id');
    }
    public function conatct_detail()
    {
        return $this->belongsTo(Contact::class,'customer_id');
    }
    
}
