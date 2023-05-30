<?php

namespace App\Console\Commands;

use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Functions;
use App\Jobs\GroupChatNotification;
use App\Models\openfire\MessageArchive;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class OpenfireRegister extends Command
{
    use Functions;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'openfire:register';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Register in openfire';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User::where('openfire_registration',0)->get();
        if(count($users) > 0) {
            foreach ($users as $user) {
                if($user->contact_detail) {
                    $user->jabber_id = $user->id.mt_rand(100000000, 999999999);
                    $user->openfire_registration = 1;
                    $user->save();
                    //add new user
                    $this->addUserToOpenfire($user->jabber_id,"123456",$user->name,$user->email);
                    //update or add vcard detail
                    $this->addVcardDetail($user);
                }
            }
        }

       $last_messages =  MessageArchive::where('offlineSent','0')->orderBy('sentDate','desc')->get();
       Log::info("INTIALLOG : ".json_encode($last_messages));
       if(count($last_messages) > 0) {
        Log::info("INTIAL-0.1-LOG : ".json_encode($last_messages));
        $temp = array();
           foreach($last_messages as $last_message) {
            $sender =  strstr($last_message['fromJID'],'@',true);
            $roomName =  strstr($last_message['toJID'],'@',true);
            $body =  $last_message['body'];
            $data = [
                 'fileName' => null,
                 'fromJID' => $last_message['fromJID'],
                 'messageID' => $last_message['id'],
                 'sentDate' => $last_message['sentDate'],
                 'stanza' => $last_message['stanza']
             ];
            //First test log
            Log::info("FIRSTLOG : ".json_encode($data));

            if(!in_array($roomName, $temp)){
                GroupChatNotification::dispatch($roomName,$body,$sender,$data);
                Log::info("offlineSent_UPDATED".$last_message['messageID']);
                $last_message['offlineSent'] = 1;
                $last_message->save();
                // $updateArchive = MessageArchive::where('messageID',$last_message['messageID'])->first();
                // if($updateArchive) {
                //     //Update test log
                //     Log::info("offlineSent_UPDATED".$last_message['messageID']);
                //     $updateArchive->update(['offlineSent'=>1]);
                // }
                $temp[] = $roomName;
            }
        }
       }
    }
}
