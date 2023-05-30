<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SequenceMaster extends Model
{
    protected $fillable = ['code','description','sequence'];
}
