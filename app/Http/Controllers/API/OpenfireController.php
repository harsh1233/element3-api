<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Jobs\UpdateChatRoom;
use Illuminate\Http\Request;
use App\Models\Courses\Course;
use App\Models\openfire\ChatRoom;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Functions;
use App\Jobs\GroupChatNotification;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\openfire\ChatRoomMember;
use App\Models\openfire\MessageArchive;
use Illuminate\Support\Facades\Storage;
use App\Models\BookingProcess\BookingProcesses;

class OpenfireController extends Controller
{
    use Functions;

    /* Creating new chat by element3 */
    public function createE3Chat(Request $request)
    {
        $v = validator($request->all(), [
            'message' => 'required',
            'attachment' => 'file|mimes:jpeg,bmp,png,doc,pdf,docx,application/octet-stream,audio/mpeg,mpga,mp3,wav',//audio,image,doc.pdf
            'contact_ids' => 'required|array',
        ]);

        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        $admin = User::whereHas('role_detail', function($q){
            $q->where('code','CHTADM');
        })->first();
        if(!$admin || !$admin->jabber_id) return $this->sendResponse(false, 'E3 admin is not created yet.');
        $internal_ip = config('constants.openfireInternalIp');
        $fromJID = $admin->jabber_id.'@'.$internal_ip;
        //$fromJIDResource = 'Web';
        $body = $request->message;
        $conversationID = 0;
        $sentDate = time()*1000;
        $element3Admin = config("constants.element3_admin");

        $attachment = $request->attachment;
        $fileUrl = null;
        $fileType = 'other';
        $insertData = [];
        $originalName = null;
        $extension = null;

        if($attachment) {
            $originalName = $attachment->getClientOriginalName();
            $extension = $attachment->getClientOriginalExtension();
            $fileName = pathinfo($originalName,PATHINFO_FILENAME);
            $directory = 'chat_attachments';
            $filenametostore = $directory.'/'.$fileName.'_'.time().'.'.$extension;
            //Upload File to s3
            Storage::disk('s3')->put($filenametostore, fopen($attachment, 'r+'), 'public');
            $fileUrl = Storage::disk('s3')->url($filenametostore);

            if($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png' || $extension === 'bmp') {
                $fileType = 'image';
            } else if($extension === 'doc' || $extension === 'docx' || $extension === 'pdf') {
                $fileType = 'document';
            } else if($extension === 'mp3' || $extension === 'wav') {
                $fileType = 'audio';
            } else {
                $fileType = 'other';
            }
        }
        $users =  User::select('id','name','email','jabber_id','device_token','device_type','is_notification')->whereIn('contact_id',$request->contact_ids)->get();
        if(count($users) > 0) {
            foreach($users as $user) {
                if(!$user->jabber_id) continue;

                $toJID = $user->jabber_id.'@'.$internal_ip;
                $stanza = '<message to="'.$fromJID.'" type="privatechat" from="'.$toJID.'/'.$fromJID.'"><body>'.$body.'</body><properties><message_type>text</message_type><from_name>'.$element3Admin.'</from_name><sentDate>'.$sentDate.'</sentDate></properties></message>';
                $insertData =  [
                    'conversationID'=>$conversationID,
                    'fromJID'=>$fromJID,
                    'toJID'=>$toJID,
                    'sentDate'=>$sentDate,
                    'body'=>$body,
                    'fileName'=>null,
                    'stanza'=>$stanza,
                    'offlineSent'=>1
                ];
                $MessageArchive = MessageArchive::create($insertData);
                if($fileUrl) {
                    $stanza2 = '<message to="'.$fromJID.'" type="privatechat" from="'.$toJID.'/'.$fromJID.'"><body>'.$fileUrl.'</body><properties><message_type>'.$fileType.'</message_type><from_name>'.$element3Admin.'</from_name><fileName>'.$originalName.'</fileName><fileExtension>'.$extension.'</fileExtension><sentDate>'.$sentDate.'</sentDate></properties></message>';
                    $fileData = [
                        'conversationID'=>$conversationID,
                        'fromJID'=>$fromJID,
                        'toJID'=>$toJID,
                        'sentDate'=>$sentDate,
                        'body'=>$fileUrl,
                        'fileName'=>$originalName,
                        'stanza'=>$stanza2,
                        'offlineSent'=>1
                    ];
                    //$insertData = [$insertData,$fileData];
                    $attachmentArchive = MessageArchive::create($fileData);

                    //Send  attachment to user
                    if($user['device_token'] && $user['is_notification']) {
                        $title = config("constants.element3_admin");
                        $type = 21;
                        $data = [
                            'fileName' => $originalName,
                            'fromJID' => $fromJID,
                            'messageID' => $attachmentArchive['id'],
                            'sentDate' => $sentDate,
                            'stanza' => $stanza2
                        ];
                        SendPushNotification::dispatch($user['device_token'], $user['device_type'], $title, $body, $type, $data);
                    }
                }
                //$MessageArchive = MessageArchive::insertGetId($insertData);

                //Send push notification to user
                if($user['device_token'] && $user['is_notification']) {
                    $title = config("constants.element3_admin");
                    $type = 21;
                    $data = [
                        'fileName' => null,
                        'fromJID' => $fromJID,
                        'messageID' => $MessageArchive['id'],
                        'sentDate' => $sentDate,
                        'stanza' => $stanza
                    ];
                    //Log::info("E3 admin token");
                    //Log::info($user['device_token']);
                    SendPushNotification::dispatch($user['device_token'], $user['device_type'], $title, $body, $type, $data);
                }
            }
        }

        return $this->sendResponse(true,__('strings.chat_sent_success'),$insertData);
    }

    /* Get Element3 chat detail */
    public function getE3Chat(Request $request)
    {
        $admin = User::whereHas('role_detail', function($q){
            $q->where('code','CHTADM');
        })->first();
        if(!$admin || !$admin->jabber_id) return $this->sendResponse(false, 'E3 admin is not created yet.');
        $internal_ip = config('constants.openfireInternalIp');
        $fromJID = $admin->jabber_id.'@'.$internal_ip;

        if((auth()->user()->type() === 'Customer') || (auth()->user()->type() === 'Instructor')) {
            $jabber_id = auth()->user()->jabber_id;
        } else {
            $user = User::where('contact_id',$request->contact_id)->first();
            if(!$user) return $this->sendResponse(false, 'User not found');
            $jabber_id = $user->jabber_id;
        }
        $toJID = $jabber_id.'@'.$internal_ip;

       $messages = MessageArchive::select('messageID','fromJID','toJID','body','sentDate','fileName','stanza')->where('fromJID',$fromJID)->where('toJID',$toJID);
       if($request->page && $request->perPage) {
         $page = $request->page;  
         $perPage = $request->perPage;
        $messages = $messages->skip($perPage*($page-1))->take($perPage);    
       } 
       $messages = $messages->get();

       return $this->sendResponse(true,__('chat messages'),$messages);
    }

    /* Get list of chatroom for user (customer/instructor) */
    public function getUserChatRoom(Request $request)
    {
        $internal_ip = config('constants.openfireInternalIp');
        $conference_ip = 'conference.'.config('constants.openfireInternalIp');
        if((auth()->user()->type() === 'Customer') || (auth()->user()->type() === 'Instructor')) {
            $jabber_id = auth()->user()->jabber_id;
        } else {
            $user = User::where('contact_id',$request->contact_id)->first();
            if(!$user) return $this->sendResponse(false, 'User not found');
            $jabber_id = $user->jabber_id;
        }
        $JID = $jabber_id.'@'.$internal_ip;

       $room_ids = ChatRoomMember::where('jid',$JID)->pluck('roomID');
       $chatroom = ChatRoom::select('roomID','name','naturalName','creationDate')->whereIn('roomID',$room_ids)->orderBy('creationDate','desc')->get();
       $data = [];
       if(count($chatroom) > 0) {
           foreach($chatroom as $cr) {
               /* BookingProcesses::select('id','QR_number')->where('QR_number',$cr['name'])->with(['course_detail'=>function($q){
                $q->select('id','booking_process_id','course_id')->with(['course_data'=>function($q2){
                    $q2->select('id','name','course_banner');
                }]);
               }]); */
              $course =  Course::select('id','name','course_banner')->where('name',$cr['naturalName'])->first();
              $booking = BookingProcesses::select('id','QR_number','booking_number')->where('QR_number',$cr['name'])->first();
              if(!$course || !$booking) continue;
              $roomJID = $cr['name'].'@'.$conference_ip;
        
             $last_message = MessageArchive::
             /**Date : 20-01-2021
              * Description : Now need room last message
              */
            //  where('fromJID',$JID)
             where('toJID',$roomJID)->orderBy('messageID','desc')->first();
              $data[] = [
                'name' => $cr['name'],
                'naturalName' => $cr['naturalName'],
                'course_banner' => $course['course_banner'],
                'course_id' => $course['id'],
                'booking_id' => $booking['id'],
                'booking_number' => $booking['booking_number'],
                'creationDate' => $cr['creationDate'],
                'last_message' => $last_message
              ];
           }
       } 

       return $this->sendResponse(true,__('Chat room list'),$data);
    }

    /* Creating new chat by room name */
    public function createRoomChat(Request $request)
    {
        $v = validator($request->all(), [
            'roomName' => 'required',
            'message' => 'required',
            'attachment' => 'file|mimes:jpeg,bmp,png,doc,pdf,docx,application/octet-stream,audio/mpeg,mpga,mp3,wav',//audio,image,doc.pdf
        ]);

        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());
        $roomName = $request->roomName;
        $element3Admin = config("constants.element3_admin");
        $booking = BookingProcesses::select('id','QR_number')->where('QR_number',$roomName)->first();
        if(!$booking) return $this->sendResponse(true,__('Invalid room name'));
        $checkroom = ChatRoom::where('name',$roomName)->count();
        if(!$checkroom) {
            //Update chatroom for openfire
            UpdateChatRoom::dispatch(true,$booking->id);
        }
        $admin = User::whereHas('role_detail', function($q){
            $q->where('code','CHTADM');
        })->first();
        if(!$admin || !$admin->jabber_id) return $this->sendResponse(false, 'E3 admin is not created yet.');
        $internal_ip = config('constants.openfireInternalIp');
        $conference_ip = 'conference.'.config('constants.openfireInternalIp');
        
        $fromJID = $admin->jabber_id.'@'.$internal_ip;
        $roomJID = $roomName.'@'.$conference_ip;
        //$fromJIDResource = 'Web';
        $body = $request->message;
        $conversationID = 0;
        $sentDate = time()*1000;

        $attachment = $request->attachment;
        $fileUrl = null;
        $extension = null;
        $originalName = null;
        $textMessageID = null;
        $attachmentMessageID = null;
        $fileType='other';
        if($attachment) {
            $originalName = $attachment->getClientOriginalName();
            $extension = $attachment->getClientOriginalExtension();
            $fileName = pathinfo($originalName,PATHINFO_FILENAME);
            $directory = 'chat_attachments';
            $filenametostore = $directory.'/'.$fileName.'_'.time().'.'.$extension;
            //Upload File to s3
            Storage::disk('s3')->put($filenametostore, fopen($attachment, 'r+'), 'public');
            $fileUrl = Storage::disk('s3')->url($filenametostore);

            if($extension === 'jpg' || $extension === 'jpeg' || $extension === 'png' || $extension === 'bmp') {
                $fileType = 'image';
            } else if($extension === 'doc' || $extension === 'docx' || $extension === 'pdf') {
                $fileType = 'document';
            } else if($extension === 'mp3' || $extension === 'wav') {
                $fileType = 'audio';
            } else {
                $fileType = 'other';
            }
        }
        $stanza = '<message to="'.$fromJID.'" type="groupchat" from="'.$roomJID.'/'.$fromJID.'"><body>'.$body.'</body><properties><message_type>text</message_type><from_name>'.$element3Admin.'</from_name><sentDate>'.$sentDate.'</sentDate></properties></message>';

        $insertData =  [
            'conversationID'=>$conversationID,
            'fromJID'=>$fromJID,
            'toJID'=>$roomJID,
            'sentDate'=>$sentDate,
            'body'=>$body,
            'fileName'=>null,
            'stanza'=>$stanza,
            'offlineSent'=>1
        ];
        //$MessageArchive = MessageArchive::create($insertData);
        if($fileUrl) {
            $stanza2 = '<message to="'.$fromJID.'" type="groupchat" from="'.$roomJID.'/'.$fromJID.'"><body>'.$fileUrl.'</body><properties><message_type>'.$fileType.'</message_type><from_name>'.$element3Admin.'</from_name><fileName>'.$originalName.'</fileName><fileExtension>'.$extension.'</fileExtension><sentDate>'.$sentDate.'</sentDate></properties></message>';
            $fileData = [
                'conversationID'=>$conversationID,
                'fromJID'=>$fromJID,
                'toJID'=>$roomJID,
                'sentDate'=>$sentDate,
                'body'=>$fileUrl,
                'fileName'=>$originalName,
                'stanza'=>$stanza2,
                'offlineSent'=>1
            ];
            //$insertData = [$insertData,$fileData];
            //$attachmentArchive = MessageArchive::create($fileData);
            $attachmentMessageID = time().mt_rand(100, 999);

            $attachmentData = [
                'fileName' => $originalName,
                'fromJID' => $fromJID,
                'messageID' => $attachmentMessageID,
                'sentDate' => $sentDate,
                'stanza' => $stanza2
            ];

            //Send attachment to offline user
            GroupChatNotification::dispatch($roomName,$fileUrl,$admin->jabber_id,$attachmentData, true);
        }
        //MessageArchive::insert($insertData);
        $textMessageID = time().mt_rand(100, 999);
        $data = [
            'fileName' => null,
            'fromJID' => $fromJID,
            'messageID' => $textMessageID,
            'sentDate' => $sentDate,
            'stanza' => $stanza
        ];

        //Send push notification to offline user
        GroupChatNotification::dispatch($roomName,$body,$admin->jabber_id,$data, true);

        $adminData = [
            'sentDate' => $sentDate,
            'text' => [
                'messageID' => $textMessageID,
                'body' => $body
            ],
            'attachment' => [
                'messageID' => $attachmentMessageID,
                'body' => $fileUrl,
                'fileType' => $fileType,
                'fileName' => $originalName,
                'extension' => $extension
            ],
        ];
        return $this->sendResponse(true,__('strings.chat_sent_success'),$adminData);
    }

     /* Get chat detail by room name */
     public function getRoomChat(Request $request)
     {
         $v = validator($request->all(), [
             'roomName' => 'required',
         ]);
 
         if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());
         $roomName = $request->roomName;
         $booking = BookingProcesses::select('id','QR_number')->where('QR_number',$roomName)->first();
         if(!$booking) return $this->sendResponse(true,__('Invalid room name'));
         
         $conference_ip = 'conference.'.config('constants.openfireInternalIp');
         $roomJID = $roomName.'@'.$conference_ip;

         $messages = MessageArchive::where('toJID',$roomJID)->get();
         $data = [];
        foreach($messages as $message) {
            if(!$message['fromJID']) continue;
            $fromJID = explode("@",$message['fromJID'])[0];
            $user = User::select('id','contact_id','name','jabber_id','is_app_user','role')->with('contact_detail')->where('jabber_id',$fromJID)->first();
            if(!$user) continue;
            
            $user_detail = [
                'id' => $user['id'],
                'name' => $user['name'],
                'profile_pic' => $user->contact_detail ? $user->contact_detail->profile_pic : '',
                'is_app_user' => $user->role ? 0 : 1
            ];
            $data[] = [
                'messageID'=>$message['messageID'],
                'fromJID'=>$message['fromJID'],
                'toJID'=>$message['toJID'],
                'sentDate'=>$message['sentDate'],
                'stanza'=>$message['stanza'],
                'body'=>$message['body'],
                'fileName'=>$message['fileName'],
                'user_detail' => $user_detail
            ];   
        }
         return $this->sendResponse(true,__('Chat detail'),$data);
     }
}
