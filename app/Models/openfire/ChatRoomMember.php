<?php

namespace App\Models\openfire;

use Illuminate\Database\Eloquent\Model;

class ChatRoomMember extends Model
{
	
    protected $table="ofMucMember";
    protected $connection= 'mysql_external'; 

   
}