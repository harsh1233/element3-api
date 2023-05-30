<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubCustomer extends Model
{
    public $timestamps = false;
    protected $fillable = ['first_name',
        'last_name',
        'email',
        'mobile1',
        'mobile2',
        'address',
        'zip',
        'dob',
        'city',
        'country',
        'accomodation',
        'created_by',
        'created_at'
    ];
}
