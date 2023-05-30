<?php

namespace App\Models\Chat;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Model;

class E3Chat extends Model
{
    protected $table = 'e3_chat_messages'; 	
    protected $fillable = ['sender_id', 'receiver_id','message','file_url','file_name','file_type','is_read'];
    public $timestamps = false;
    protected $appends = ['is_sent'];

    public static function boot()
    {
        parent::boot();

        // create a event to happen on creating
        static::creating(function ($record) {
            $record->created_at = date("Y-m-d H:i:s");
        });
    }

    public function sender_detail()
    {
        return $this->belongsTo(Contact::class,'sender_id');
    }

    public function receiver_detail()
    {
        return $this->belongsTo(Contact::class,'receiver_id');
    }

    // Accessor chat from 
    public function getIsSentAttribute()
    {
        $sender_id = $this->sender_id;
        $is_sent = 0;

        if(!auth()->user()->is_app_user){
            $contact_id = 0;
        }else{
            $contact_id = auth()->user()->contact_id;
        }

        if($sender_id == $contact_id){
            $is_sent = 1;
        }
        return $is_sent;
    }
}
