<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use App\Models\openfire\ChatRoom;
use App\Jobs\SendPushNotification;
use App\Models\openfire\UserStatus;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Models\openfire\ChatRoomMember;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GroupChatNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $roomName;
    public $message;
    public $sender;
    public $notificationData;
    public $isAdmin;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($roomName,$message, $sender,$notificationData, $isAdmin=false)
    {
        $this->roomName = $roomName;
        $this->message = $message;
        $this->sender = $sender;
        $this->notificationData = $notificationData;
        $this->isAdmin = $isAdmin;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $roomName = $this->roomName;
        $body = $this->message;
        $sender = $this->sender;
        $isAdmin = $this->isAdmin;
        $room =  ChatRoom::select('roomID','name')->where('name',$roomName)->first();
        //Secound test log
        Log::info("SECOUNDLOG : ".json_encode($room));

        if(!$room) return;
        //Three test log
        Log::info("THIRDLOG : ".json_encode($room));

        $jids = ChatRoomMember::where('roomID',$room['roomID'])->pluck('jid');
        $notificationData = $this->notificationData;
        //Four test log
        Log::info("FOURTHLOG : ".json_encode($notificationData));
        
        if(count($jids) > 0) {
            foreach($jids as $jid) {
                $username = strstr($jid,'@',true);
                if($username === $sender) continue;
                //if(!$isAdmin) {
                    $checkStatus =  UserStatus::where('username',$username)->where('online',1)->count();
                    Log::info("User online status:");
                    Log::info($checkStatus);
                    if($checkStatus) continue;
                //}
                $user = User::select('id','jabber_id','device_token','device_type','is_notification','name')->where('jabber_id',$username)->first();
                //Five test log
                Log::info("FIFTHLOG : ".json_encode($user));

                if(!$user) continue;
                if($user['device_token'] && $user['is_notification']) {
                    //Six test log
                    Log::info("SIXTHLOG : token = ".$user['device_token']." notification status = ". $user['is_notification']);

                    $title = $isAdmin ? config("constants.element3_admin") : $user['name'];
                    $type = $isAdmin ? 22 : 24 ;
                    SendPushNotification::dispatch($user['device_token'], $user['device_type'], $title, $body, $type, $notificationData);
                } 
            }
        }
    }
}
