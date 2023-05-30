<?php

namespace App\Models;

use App\Models\Contact;
use App\Models\SalaryGroup;
use Illuminate\Database\Eloquent\Model;

class ContactBankDetail extends Model
{
    public $timestamps = false;
    
    protected $fillable = ['contact_id','iban_no','account_no','bank_name','salary_group','biz'];

    public function contact()
    {
        return $this->belongsTo(Contact::class,'contact_id');
    }

    public function salary_group_detail()
    {
        return $this->belongsTo(SalaryGroup::class,'salary_group');
    }
}
