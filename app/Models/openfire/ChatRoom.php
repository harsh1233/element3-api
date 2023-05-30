<?php

namespace App\Models\openfire;

use Illuminate\Database\Eloquent\Model;

class ChatRoom extends Model
{
	
    protected $table="ofMucRoom";
    protected $connection= 'mysql_external'; 

   
}