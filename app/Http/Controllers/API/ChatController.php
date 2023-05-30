<?php

namespace App\Http\Controllers\API;

use App\User;
use App\Models\Contact;
use App\Models\Chat\E3Chat;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Functions;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    use Functions;

    /* Creating new chat by element3 */
    public function sendE3Chat(Request $request)
    {
        if(auth()->user()->is_app_user){
            $v = validator($request->all(), [
                'message' => 'nullable',
                'attachment' => 'nullable|url',//audio,image,doc s3 url
                'file_name' => 'nullable',
                'file_type' => 'nullable|in:document,image,audio,other'
            ]);
        }else{
            $v = validator($request->all(), [
                'message' => 'nullable',
                'attachment' => 'file|mimes:jpeg,bmp,png,doc,pdf,docx,application/octet-stream,audio/mpeg,mpga,mp3,wav',//audio,image,doc.pdf
                'contact_ids' => 'nullable|array',
            ]);
        }

        if ($v->fails()) return $this->sendResponse(false, $v->errors()->first());

        $admin = User::whereHas('role_detail', function($q){
            $q->where('code','CHTADM');
        })->first();

        if(!$admin) return $this->sendResponse(false, 'E3 admin is not created yet.');
        $body = null;
        $attachment = null;

        if($request->message)$body = $request->message;
        if($request->attachment)$attachment = $request->attachment;
        $fileUrl = null;
        $fileType = 'other';
        $insertData = [];
        $originalName = null;
        $extension = null;

        if(!auth()->user()->is_app_user){
            $sender_id = 0;//For admin
        }else{
            $sender_id = auth()->user()->contact_id;
        }

        $users = array();
        $is_app_user = false;

        if(auth()->user()->is_app_user){
            $is_app_user = true;
        }

        if($attachment && !$is_app_user) {
            $originalName = $attachment->getClientOriginalName();
            $extension = $attachment->getClientOriginalExtension();
            $fileName = pathinfo($originalName,PATHINFO_FILENAME);
            $directory = 'chat_attachments';
            $filenametostore = $directory.'/'.$fileName.'_'.time().'.'.$extension;
            $originalFileName = $fileName.'_'.time().'.'.$extension;
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
        if($is_app_user){
            $fileUrl = $attachment;
            $originalFileName = $request->file_name;
            $fileType = $request->file_type;
        }
        
        if($request->contact_ids){
            $users =  User::select('id','name','email','device_token','device_type','is_notification','contact_id')->whereIn('contact_id',$request->contact_ids)->get();
        }

        if($request->is_send_all_instructors)
        {
            $contactIds = Contact::where('is_active', '1')->where('category_id', '2')->pluck('id');
            $users =  User::select('id','name','email','device_token','device_type','is_notification','contact_id')->whereIn('contact_id',$contactIds)->get();
        }

        if($request->is_send_all_customers)
        {
            $contactIds = Contact::where('is_active', '1')->where('category_id', '1')->pluck('id');
            $users =  User::select('id','name','email','device_token','device_type','is_notification','contact_id')->whereIn('contact_id',$contactIds)->get();
        }

        if(count($users) > 0) {
            foreach($users as $user) {
                $insertData =  [
                    'sender_id'=>$sender_id,
                    'receiver_id'=>$user->contact_id,
                    'message'=>$body
                ];
                $MessageArchive = E3Chat::create($insertData);
                if($fileUrl) {
                    $fileData = [
                        'sender_id'=>$sender_id,
                        'receiver_id'=>$user->contact_id,
                        'file_url'=>$fileUrl,
                        'file_type'=>$fileType,
                        'file_name' => $originalFileName
                    ];
                    $MessageArchive = E3Chat::create($fileData);

                    //Send attachment notification to user
                    if($user['device_token'] && $user['is_notification']) {
                        $title = config("constants.element3_admin");
                        $type = 30;
                        $data = [
                            'fileUrl' => $fileUrl,
                            'fileName' => $originalFileName,
                            'fileType' => $fileType,
                            'senderId' => $sender_id,
                            'messageID' => $MessageArchive['id'],
                            'sentDate' => $MessageArchive['created_at']
                        ];
                        SendPushNotification::dispatch($user['device_token'], $user['device_type'], $title, $body, $type, $data);
                    }
                }

                //Send push notification to user
                if($user['device_token'] && $user['is_notification']) {
                    $title = config("constants.element3_admin");
                    $type = 30;
                    $data = [
                        'fileName' => null,
                        'senderId' => $sender_id,
                        'messageID' => $MessageArchive['id'],
                        'sentDate' => $MessageArchive['created_at']
                    ];
                    SendPushNotification::dispatch($user['device_token'], $user['device_type'], $title, $body, $type, $data);
                }
                /**For attachment in notification save default message */
                if($fileUrl) {
                    $body = 'Attachment send';
                }
                Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$user->contact_id,"type"=>30,'message'=>$body]);
            }
        }else{
            $receiver_id = 0;//For admin

            if($body){
                $insertData =  [
                    'sender_id'=>$sender_id,
                    'receiver_id'=>$receiver_id,
                    'message'=>$body
                ];
                $MessageArchive = E3Chat::create($insertData);
            }
            if($fileUrl) {
                $fileData = [
                    'sender_id'=>$sender_id,
                    'receiver_id'=>$receiver_id,
                    'file_name' =>$originalFileName,
                    'file_type'=>$fileType,
                    'file_url'=>$fileUrl
                ];
                $MessageArchive = E3Chat::create($fileData);
                $body = 'Attachment send';
            }
            Notification::create(['sender_id'=>$sender_id,"receiver_id"=>$receiver_id,"type"=>30,'message'=>$body]);
        }
        
        $data = E3Chat::where('id', $MessageArchive->id)->with(['sender_detail' => function($q){
            $q->select('id','salutation','first_name','middle_name','last_name','email','mobile1','mobile2','nationality','designation','dob','gender','profile_pic','color_code','instructor_number');
        },'sender_detail.user_detail','receiver_detail' => function($q){
            $q->select('id','salutation','first_name','middle_name','last_name','email','mobile1','mobile2','nationality','designation','dob','gender','profile_pic','color_code','instructor_number');
        },'receiver_detail.user_detail'])->first();

        return $this->sendResponse(true,__('strings.chat_sent_success'),$data);
    }

    /* Chat list */
    public function e3ChatList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
            'contact_id' => 'nullable|exists:contacts,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if(!auth()->user()->is_app_user){
            $contact_id = 0;//For admin
        }else{
            $contact_id = auth()->user()->contact_id;
        }

        $e3Chat = E3Chat::query();

        if($request->contact_id){
            $contact_id = $request->contact_id;
        }

        $e3Chat->where(function($q) use($contact_id){
            $q->where('sender_id', $contact_id);
            $q->orWhere('receiver_id', $contact_id);
        });

        $e3ChatCount = $e3Chat->count();

        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;
            $e3Chat = $e3Chat->skip($perPage*($page-1))->take($perPage);
        }

        $e3Chat = $e3Chat->with(['sender_detail' => function($q){
            $q->select('id','salutation','first_name','middle_name','last_name','email','mobile1','mobile2','nationality','designation','dob','gender','profile_pic','color_code','instructor_number');
        },'sender_detail.user_detail','receiver_detail' => function($q){
            $q->select('id','salutation','first_name','middle_name','last_name','email','mobile1','mobile2','nationality','designation','dob','gender','profile_pic','color_code','instructor_number');
        },'receiver_detail.user_detail'])
        ->get();

        $unread_chat = E3Chat::where('receiver_id', $contact_id)
        ->where('is_read', false)
        ->count();

        $data = [
            'e3_chats' => $e3Chat,
            'unread_chat' => $unread_chat,
            'count' => $e3ChatCount
        ];

        return $this->sendResponse(true, __('strings.e3_chats'), $data);
    }

    /* Read notification */
    public function notificationRead(Request $request)
    {
        if(!auth()->user()->is_app_user){
            $contact_id = 0;//For admin
        }else{
            $contact_id = auth()->user()->contact_id;
        }

        if($request->notification_id){
            $notification = Notification::where('receiver_id', $contact_id)
            ->find($request->notification_id);
            
            if(!$notification){
                return $this->sendResponse(false, __('strings.not_found_validation',['name' => 'Notification']));
            }
            $notification->is_read = 'R';
            $notification->save();
        }else{
            Notification::where('is_read','U')
            ->where('receiver_id', $contact_id)
            ->update(['is_read' => 'R']);
        }

        return $this->sendResponse(true, __('strings.notification_read_sucess'));
    }

    /* Notification list */
    public function notificationList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if(!auth()->user()->is_app_user){
            $contact_id = 0;//For admin
        }else{
            $contact_id = auth()->user()->contact_id;
        }

        $types = [30, 32];//30: Chat notification, 32: Leave notification
        $notifications = Notification::whereIn('type', $types)->where('receiver_id', $contact_id);

        $enotificationsCount = $notifications->count();

        $baseCount = Notification::whereIn('type', $types)->where('is_read', 'U')
        ->where('receiver_id', $contact_id)->count();

        if($request->page && $request->perPage){
            $page = $request->page;
            $perPage = $request->perPage;
            $notifications = $notifications->skip($perPage*($page-1))->take($perPage);
        }
        $notifications = $notifications->with(['sender' => function($q){
            $q->select('id','salutation','first_name','middle_name','last_name','email','mobile1','mobile2','nationality','designation','dob','gender','profile_pic','color_code','instructor_number');
        },
        'receiver' => function($q){
            $q->select('id','salutation','first_name','middle_name','last_name','email','mobile1','mobile2','nationality','designation','dob','gender','profile_pic','color_code','instructor_number');
        },
        'notificationType'])
        ->orderBy('id', 'desc')
        ->get();
        $data = [
            'notifications' => $notifications,
            'count' => $enotificationsCount,
            'base_count' => $baseCount 
        ];
        return $this->sendResponse(true, __('strings.list_message',['name' => 'Notifications']), $data);
    }

    /**Chat read */
    public function chatRead(Request $request)
    {
        $v = validator($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:e3_chat_messages,id',
        ],[
            'ids.*.exists' => __('validation.id_exists')
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        if(!auth()->user()->is_app_user){
            $contact_id = 0;//For admin
        }else{
            $contact_id = auth()->user()->contact_id;
        }
        
        E3Chat::where('receiver_id', $contact_id)
        ->whereIn('id', $request->ids)
        ->update(['is_read' => true]);
        
        return $this->sendResponse(true,__('strings.success', ['name' => 'Chat read']));
    }
}
