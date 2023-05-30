<?php

namespace App\Models\openfire;

use Illuminate\Database\Eloquent\Model;

class MessageArchive extends Model
{
	
    protected $table="ofMessageArchive";

    protected $fillable = ['messageID','conversationID','offlineSent','fromJID','fromJIDResource','toJID','toJIDResource','sentDate','stanza','body','fileName'];
    //protected $hidden = array('stanza');
    protected $primaryKey = 'messageID';
    //public $incrementing = false;
    public $timestamps = false;
    protected $connection= 'mysql_external'; 

   
}
