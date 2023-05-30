<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExceptionManagement extends Model
{
     protected $table = 'exception_management';
     protected $fillable = ['message','stack_trace','file','line','header_info','ip','created_at','created_by','updated_at','updated_by'];
}
