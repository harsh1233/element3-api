<?php

namespace App\Models;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class CustomerGroup extends Model
{
    protected $fillable = ['group_id','contact_id','added_by'];

    public function customer_detail()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }
}
