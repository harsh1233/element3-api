<?php

namespace App\Jobs;

use App\User;
use Illuminate\Bus\Queueable;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;

class UpdateChatRoom implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Functions;

    public $isCreate;
    public $bookingId;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($isCreate,$bookingId)
    {
        $this->isCreate = $isCreate;
        $this->bookingId = $bookingId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $bookingId = $this->bookingId;
        $booking =  BookingProcesses::with('course_detail.course_data')->with('customer_detail')->find($bookingId);
        if(!$booking) return;
        if(!$booking->course_detail) return;
        if(!$booking->course_detail->course_data) return;
        
        $courseName = $booking->course_detail->course_data->name;
        $naturalName = $courseName;
        $description = $courseName;
        $roomName = $booking->QR_number;

        if($this->isCreate) {
            $this->createChatRoom($naturalName,$roomName,$description);
            $admin = User::whereHas('role_detail', function($q){
                $q->where('code','CHTADM');
            })->first();
            if($admin->jabber_id) $this->addUserToChatroom($roomName, $admin->jabber_id, 'admins');
        }

       $customer_ids =  BookingProcessCustomerDetails::where('booking_process_id',$booking->id)->pluck('customer_id')->toArray();
       $payee_ids =  BookingProcessCustomerDetails::where('booking_process_id',$booking->id)->pluck('payi_id')->toArray();
       $customer_ids = array_unique(array_merge($customer_ids,$payee_ids));
       $instructor_ids =  BookingProcessInstructorDetails::where('booking_process_id',$booking->id)->pluck('contact_id')->toArray();
       $contact_ids = array_merge($customer_ids,$instructor_ids);
        $jabberIds =  User::whereIn('contact_id',$contact_ids)->pluck('jabber_id');
        if(count($jabberIds) > 0) {
            foreach($jabberIds as $jid) {
                $this->addUserToChatroom($roomName, $jid, 'members');
            }
        }

    }
}
