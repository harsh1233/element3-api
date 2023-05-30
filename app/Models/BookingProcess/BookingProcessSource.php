<?php

namespace App\Models\BookingProcess;

use Illuminate\Database\Eloquent\Model;

class BookingProcessSource extends Model
{
    public $timestamps = false;
    protected $fillable = ['source','type'];
}
