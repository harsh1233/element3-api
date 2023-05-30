<?php

namespace App\Models\openfire;

use Illuminate\Database\Eloquent\Model;

class UserStatus extends Model
{
    protected $table="userStatus";
    protected $connection= 'mysql_external'; 
   
}