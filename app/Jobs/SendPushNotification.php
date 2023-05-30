<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $registrationIds;
    public $device_type;
    public $title;
    public $body;
    public $type;
    public $data;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($registrationIds, $device_type, $title, $body, $type = null, $data = [])
    {
        $this->registrationIds = $registrationIds;
        $this->device_type = $device_type;
        $this->title = $title;
        $this->body = $body;
        $this->type = $type;
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $msg = array(
          'body'  => $this->body,
          'title' => $this->title,
         'icon' => 'myicon',/*Default Icon*/
         'sound' => 'mySound'/*Default sound*/
          );
        //$usertoken = UserToken::where('token',$this->registrationIds)->first();
       $this->data['type'] = $this->type;
        if ($this->registrationIds) {
            if ($this->device_type == 'A') {
                $this->data = array_merge($msg, $this->data);
                $fields = array(
              'to'    => $this->registrationIds,
              'data'=>$this->data
              );
            } else {
                $fields = array(
              'to'    => $this->registrationIds,
              'notification'  => $msg,
              'data'=>$this->data
              );
            }
            $headers = array(
            'Authorization: key=' . config('constants.api_access_key'),
            'Content-Type: application/json'
          );
            //dd($fields);
            #Send Reponse To FireBase Server
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
            $result = curl_exec($ch);
            curl_close($ch);
            //dd(json_encode($result));
            Log::info("Payload ".json_encode($fields));
            Log::info("Result ". json_encode($result));
        }
    }
}
