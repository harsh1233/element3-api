<?php

namespace App\Http\Controllers;

use DB;
use PDF;
use Hash;
use Mail;
use App\User;
use Carbon\Carbon;
use App\Models\Office;
use App\Models\Contact;
use App\Models\Modules;
use App\Models\Language;
use Illuminate\Support\Str;
use App\Models\ContactLeave;
use App\Models\Finance\Cash;
use App\Models\Notification;
use App\Models\AccountDetail;
use App\Models\AccountMaster;
use App\Models\PaymentMethod;
use App\Models\ContactAddress;
use App\Models\Courses\Course;
use App\Models\openfire\VCard;
use App\Models\SequenceMaster;
use App\Models\ContactLanguage;
use App\Models\Finance\Voucher;
use App\Models\SeasonSchedular;
use App\Models\Permissions\Menu;
use App\Models\Permissions\Role;
use App\Notifications\AdminAlert;
use App\Jobs\SendPushNotification;
use App\Models\CrmUserActionTrail;
use App\Models\InstructorBlockMap;
use App\Models\Permissions\Module;
use App\Models\ExceptionManagement;
use App\Models\Finance\Expenditure;
use App\Models\SeasonDaytimeMaster;
use App\Notifications\SendPassword;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Models\Courses\CourseDetail;
use App\Notifications\UpdateBooking;
use Illuminate\Support\Facades\Cache;
use App\Models\openfire\MessageArchive;
use Illuminate\Support\Facades\Storage;
use App\Models\SubChild\SubChildContact;
use App\Models\Finance\ExpenditureDetail;
use App\Models\Permissions\PrivilegeMenu;
use App\Notifications\BookingAlertFivePm;
use App\Models\BookingProcess\BookingPayment;
use App\Http\Controllers\obonoApi\ObonoRestApi;
use App\Models\BookingProcess\BookingProcesses;
use App\Notifications\BookingConfirmInstructor;
use App\Models\Permissions\ModuleRolePermission;
use App\Http\Controllers\openfire\OpenFireRestApi;
use App\Models\BookingProcess\ConsolidatedInvoice;
use App\Models\BookingProcess\InvoicePaymentHistory;
use App\Models\BookingProcess\BookingInstructorDetailMap;
use App\Models\BookingProcess\ConsolidatedInvoiceProduct;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessInstructorDetails;
use App\Models\InstructorActivity\InstructorActivityTimesheet;

trait Functions
{

    // send json response
    public function sendResponse($status, $message, $data = null, $upcoming_course_status = null, $ongoing_course_status = null, $general_status = null)
    {
        if ($status && ($upcoming_course_status !== null && $ongoing_course_status !== null)) {
            return response()->json(['message' => $message, 'data' => $data, 'upcoming_course_status' => $upcoming_course_status, 'ongoing_course_status' => $ongoing_course_status], 200);
        } elseif ($status && ($upcoming_course_status == null) && $ongoing_course_status == null) {
            return response()->json(['message' => $message, 'data' => $data], 200);
        } elseif ($status && ($general_status !== null)) {
            return response()->json(['message' => $message, 'data' => $data], 200);
        } else {
            return response()->json(['error' => $message], 400);
        }
    }

    // upload image
    public function uploadImage($directory, $name, $file)
    {
        $url = $directory . "/" . $name . "_" . mt_rand(1000000000, time()) . ".png";
        Storage::disk('s3')->put($url, file_get_contents($file));
        $public_url = Storage::disk('s3')->url($url);
        return $public_url;
    }

    // upload file
    public function uploadFile($directory, $name, $file, $formate)
    {
        if ($formate == 'Audio') {
            $url = $directory . "/" . $name . "_" . mt_rand(1000000000, time()) . ".mp3";
        } elseif ($formate == 'Pdf') {
            $url = $directory . "/" . $name . "_" . mt_rand(1000000000, time()) . ".pdf";
        } else {
            $url = $directory . "/" . $name . "_" . mt_rand(1000000000, time()) . ".png";
        }
        Storage::disk('s3')->put($url, file_get_contents($file));
        $public_url = Storage::disk('s3')->url($url);
        return $public_url;
    }

    // get user menus
    public function getMenus($role)
    {
        $result = Role::where('id', '=', $role)->with(['role_privilage_maps'])->first();
        $result = $result->role_privilage_maps->pluck('privilage_id');
        $menu_ids = PrivilegeMenu::whereIn('privilege_id', $result)->get()->pluck('menu_id');
        $parent_ids = Menu::whereIn('id', $menu_ids)->pluck('id');
        $menus = Menu::where('parent_id', 0)->whereIn('id', $parent_ids)->with(['submenu' => function ($query) use ($menu_ids, $result) {
            $query->whereIn('id', $menu_ids);
            /* $query->with(['privileges'=>function($query2) use ($result) {
                $query2->whereIn('privilege_id',$result);
            }]); */
        }])->orderBy('display_order', 'asc')->get();
        return $menus;
    }

    // get booking process list
    public function bookingProcessList1($ids, $trash)
    {
        $booking_processes = BookingProcesses::whereIn('id', $ids)
            ->where('is_trash', $trash)
            ->with(['course_detail.course_data'])
            ->with(['customer_detail.customer', 'customer_detail.sub_child_detail.allergies.allergy', 'customer_detail.sub_child_detail.languages.language'])
            ->with(['extra_participant_detail'])
            ->with(['instructor_detail.contact'])
            ->with(['request_instructor_detail.contact'])
            ->with([
                'payment_detail.customer' => function ($query) {
                    $query->select('id', 'salutation', 'first_name', 'last_name');
                }, 'payment_detail.payi_detail' => function ($query) {
                    $query->select('id', 'salutation', 'first_name', 'last_name');
                },
                'payment_detail.sub_child_detail.allergies.allergy', 'payment_detail.sub_child_detail.languages.language'
            ])
            ->with(['language_detail.language'])
            ->orderBy('id', 'desc')
            ->get();
        return $booking_processes;
    }

    // send push notification for customer user
    public function push_notification($registrationIds, $device_type, $title, $body, $type = null, $data = [])
    {
        $msg = array(
            'body'  => $body,
            'title' => $title,
            'icon' => 'myicon',/*Default Icon*/
            'sound' => 'mySound'/*Default sound*/
        );
        //$usertoken = UserToken::where('token',$registrationIds)->first();
        $data['type'] = $type;
        if ($registrationIds) {
            if ($device_type == 'A') {
                $data = array_merge($msg, $data);
                $fields = array(
                    'to'    => $registrationIds,
                    'data' => $data
                );
            } else {
                $fields = array(
                    'to'    => $registrationIds,
                    'notification'  => $msg,
                    'data' => $data
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
            Log::info("Payload " . json_encode($fields));
            Log::info("Result " . json_encode($result));
        }
    }

    public function setPushNotificationData($customer_details, $type, $data, $title, $body, $booking_process_id, $email_scheduler_type = null)
    {
        $sender_id = null;

        if ($customer_details) {
            foreach ($customer_details as $key => $customer_detail) {
                if ($type == 4 || $type == 3 || $type == 5) {
                    $customer_detail_inputs[] = $customer_detail;
                } else {
                    $customer_detail_inputs[] = $customer_detail['customer_id'];
                }
            }
        }
        $user_tokens = User::whereIn('contact_id', $customer_detail_inputs)->select('id', 'is_notification', 'device_token', 'device_type', 'contact_id')->get()->toArray();

        if ($user_tokens) {
            foreach ($user_tokens as $key => $user_token) {
                $receiver_id = $user_token['contact_id'];

                if ($email_scheduler_type) {
                    $check_notification_send = Notification::where("receiver_id", $receiver_id)->where('email_scheduler_type', $email_scheduler_type)
                        ->where('booking_process_id', $booking_process_id)->count();

                    if ($check_notification_send) {
                        return 1;
                    }
                }

                $notification = Notification::create(['sender_id' => $sender_id, "receiver_id" => $receiver_id, "type" => $type, 'message' => $body, 'booking_process_id' => $booking_process_id, 'email_scheduler_type' => $email_scheduler_type]);
                // Log::info("data",$user_token);
                if ($user_token) {
                    if ($user_token['is_notification'] == 1) {
                        if (!empty($user_token['device_token'])) {
                            SendPushNotification::dispatch($user_token['device_token'], $user_token['device_type'], $title, $body, $type, $data);
                            /* $this->push_notification($user_token['device_token'], $user_token['device_type'], $title, $body, $type, $data); */
                        }
                    }
                }
            }
        }
    }

    public function notification_list($id, $position = 1)
    {
        $notifications = Notification::where('receiver_id', $id)
            ->with('notificationType')
            ->with('bookingData')
            ->with('bookingCourseData', 'bookingCourseData.course_data')
            ->skip(10 * ($position - 1))->take(10)->orderBy('id', 'desc')->get();

        /**TEMP-COMMENT */
        // $baseCount = Notification::where('receiver_id', $id)->where('is_read', 'U')->count();

        // $notifications = [
        //     "notifications" => $notifications,
        //     "base_count" => $baseCount
        // ];
        return $notifications;
    }

    // add or update account entry
    public function updateAccountDetail($account_id, $event_code, $event_id, $event_date, $amount, $account_detail_id = null)
    {
        $account = AccountMaster::find($account_id);
        // enum('OB','CHKIN', 'CHKOUT', 'CASHOUT', 'CASHIN', 'BP')
        // OB - Opening Balance
        // CHKIN = Check in, CHKOUT = Check out at the day end, CASHOUT = Cash out, CASHIN = Cash In
        // BP - Booking Payment
        switch ($event_code) {
            case "OB": // OB - Opening Balance
                $txn_type = "CR";
                $txn_desc = "Opening Balance";
                break;

            case "CHKIN": // CHKIN = Check in
                $txn_type = "CR";
                $txn_desc = "Check in";
                break;

            case "CHKOUT": // CHKOUT = Check out
                $txn_type = "DB";
                $txn_desc = "Check out";
                break;

            case "CASHOUT": // CASHOUT = Cash out
                $txn_type = "DB";
                $txn_desc = "Cash out";
                break;

            case "CASHIN": // CASHIN = Cash In
                $txn_type = "CR";
                $txn_desc = "Cash in";
                break;

            case "BP": // BP = Booking Payment
                $txn_type = "CR";
                $txn_desc = "Booking Payment";
                break;
        }

        //create or update account detail
        if ($account_detail_id) {
            $account_detail = AccountDetail::find($account_detail_id);

            $difference_in_amount = $amount - $account_detail->amount;

            $account_detail->update([
                'event_date' => $event_date,
                'amount' => $amount,
                'transaction_type' => $txn_type,
                'transaction_desc' => $txn_desc,
            ]);

            if ($account_detail->transaction_type == 'DB') {
                $difference_in_amount = $difference_in_amount * (-1);
            } else {
                $difference_in_amount = $difference_in_amount;
            }

            // update all following records
            AccountDetail::where('id', '>', $account_detail->id)
                ->where('account_id', $account_detail->account_id)
                ->increment('running_amount', $difference_in_amount);
        } else {
            if ($event_code != 'OB') {
                //If opening balance is not there, create opening balance with 0
                if ($account->getOpeningStatement() == null) {
                    $account_id = $account->id;
                    $temp_event_code = 'OB';
                    $temp_event_id = null;
                    $temp_event_date = date('Y-m-d H:i:s');
                    $temp_amount = 0;
                    $this->updateAccountDetail($account_id, $temp_event_code, $temp_event_id, $temp_event_date, $temp_amount);
                }
            }

            $account_detail = AccountDetail::create([
                'account_id' => $account_id,
                'event_code' => $event_code,
                'event_id' => $event_id,
                'event_date' => $event_date,
                'transaction_type' => $txn_type,
                'transaction_desc' => $txn_desc,
                'amount' => $amount,
            ]);
        }

        //find previous account detail
        $previous_account_detail = AccountDetail::where('id', '<', $account_detail->id)
            ->where('account_id', $account_id)
            ->orderBy('id', 'desc')->first();

        //update running balance of new account detail
        if ($previous_account_detail) {
            if ($account_detail->transaction_type == 'DB') {
                $running_amount = $previous_account_detail->running_amount - $account_detail->amount;
            } else {
                $running_amount = $previous_account_detail->running_amount + $account_detail->amount;
            }
            $account_detail->update([
                'running_amount' => $running_amount,
            ]);
        } else {
            if ($account_detail->transaction_type == 'DB') {
                $running_amount = $account_detail->amount * (-1);
            } else {
                $running_amount = $account_detail->amount;
            }
            $account_detail->update([
                'running_amount' => $running_amount,
            ]);
        }
    }

    // delete account entry
    public function deleteAccountDetail($account_detail_id)
    {
        $account_detail = AccountDetail::find($account_detail_id);

        $account_detail->delete();

        if ($account_detail->transaction_type == 'DB') {
            $difference_in_amount = $account_detail->amount;
        } else {
            $difference_in_amount = $account_detail->amount * (-1);
        }

        // update all following records
        AccountDetail::where('id', '>', $account_detail->id)
            ->where('account_id', $account_detail->account_id)
            ->increment('running_amount', $difference_in_amount);
    }

    //Instructor timesheet create/update
    public function InstructorTimesheetCreate($instructor_id, $booking_id, $start_date, $end_date, $start_time, $end_time)
    {
        $data['instructor_id'] = $instructor_id;
        $data['booking_id'] = $booking_id;
        $data['created_by'] = auth()->user()->id;
        $total_hours = Carbon::parse($end_time)->diffInSeconds(Carbon::parse($start_time));
        $total_hours = gmdate('H:i:s', $total_hours);
        while (strtotime($start_date) <= strtotime($end_date)) {
            $data['activity_date'] = $start_date;
            $data['actual_start_time'] = $start_time;
            $data['actual_end_time'] = $end_time;
            $data['actual_hours'] = $total_hours;
            $timesheet = InstructorActivityTimesheet::where('booking_id', $booking_id)->where('instructor_id', $instructor_id)->where('activity_date', $start_date)->first();
            if (!$timesheet) {
                InstructorActivityTimesheet::create($data);
            } else {
                $timesheet->update($data);
            }
            $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
        }
    }

    //Instructor Booking Map Table create/update
    public function BookingInstructorMapCreate($contact_id, $booking_id, $start_date, $end_date, $start_time, $end_time)
    {
        $data['contact_id'] = $contact_id;
        $data['booking_process_id'] = $booking_id;
        $startdate_time = $start_date . " " . $start_time;
        $enddate_time = $end_date . " " . $end_time;
        while (strtotime($start_date) <= strtotime($end_date)) {
            $data['startdate_time'] = $start_date . " " . $start_time;
            //$data['enddate_time']=$enddate_time;
            $data['enddate_time'] = $start_date . " " . $end_time;
            $booking_instructor = BookingInstructorDetailMap::where('booking_process_id', $booking_id)->where('contact_id', $contact_id)->where('startdate_time', $startdate_time)->where('enddate_time', $enddate_time)->first();
            if (!$booking_instructor) {
                BookingInstructorDetailMap::create($data);
            } else {
                $booking_instructor->update($data);
            }
            $start_date = date("Y-m-d", strtotime("+1 day", strtotime($start_date)));
        }
    }

    //Get Available Instructor List
    public function getAvailableInstructorList($strt_date, $ed_date, $strt_time, $ed_time, $booking_processId = '')
    {
        $booking_processes_ids = BookingProcessCourseDetails::Where(function ($query) use ($strt_date) {
            $query->where('start_date', '<=', $strt_date);
            $query->where('end_date', '>=', $strt_date);
        })
            ->orWhere(function ($query) use ($ed_date) {
                $query->where('start_date', '<=', $ed_date);
                $query->where('end_date', '>=', $ed_date);
            })
            ->pluck('booking_process_id')->toArray();

        $booking_processes_ids1 = BookingProcessCourseDetails::Where(function ($query) use ($strt_time) {
            $query->where('start_time', '<=', $strt_time);
            $query->where('end_time', '>=', $strt_time);
        })
            ->orWhere(function ($query) use ($ed_time) {
                $query->where('start_time', '<=', $ed_time);
                $query->where('end_time', '>=', $ed_time);
            })
            ->orWhere(function ($query) use ($strt_time, $ed_time) {
                $query->where('start_time', '>=', $strt_time);
                $query->where('start_time', '<=', $ed_time);
            })
            ->orWhere(function ($query) use ($strt_time, $ed_time) {
                $query->where('end_time', '<=', $strt_time);
                $query->where('end_time', '>=', $ed_time);
            })->get();
        // ->pluck('booking_process_id')->toArray();

        if ($booking_processId == '') {
            $booking_processes_ids1 = $booking_processes_ids1->pluck('booking_process_id')->toArray();
        } else {
            $booking_processes_ids1 = $booking_processes_ids1->where('booking_process_id', '!=', $booking_processId)
                ->pluck('booking_process_id')->toArray();
        }

        $booking_processes_ids = array_intersect($booking_processes_ids, $booking_processes_ids1);
        return $booking_processes_ids;
    }

    public function getAvailableInstructorListNew($dates_data, $booking_process_id = '')
    {
        $booking_processes_ids_main = array();
        $leave_contact_ids_main = array();
        $data = array();

        foreach ($dates_data as $key => $dates) {
            $date_inputs = $dates;
            $start_date1 = explode(" ", $date_inputs['StartDate_Time']);
            $end_date1 = explode(" ", $date_inputs['EndDate_Time']);

            // dd($start_date1);

            $strt_date = $start_date1[0];
            $ed_date = $end_date1[0];
            $strt_time = $start_date1[1];
            $ed_time = $end_date1[1];

            $booking_processes_ids = BookingProcessCustomerDetails::where('is_cancelled', false)
                ->where(function ($query) use ($strt_date) {
                    $query->where('start_date', '<=', $strt_date);
                    $query->where('end_date', '>=', $strt_date);
                })
                ->orWhere(function ($query) use ($ed_date) {
                    $query->where('start_date', '<=', $ed_date);
                    $query->where('end_date', '>=', $ed_date);
                })
                ->pluck('booking_process_id')->toArray();


            $booking_processes_ids1 = BookingProcessCustomerDetails::where('is_cancelled', false)
                ->where(function ($query) use ($strt_time) {
                    $query->where('start_time', '<=', $strt_time);
                    $query->where('end_time', '>=', $strt_time);
                })
                ->orWhere(function ($query) use ($ed_time) {
                    $query->where('start_time', '<=', $ed_time);
                    $query->where('end_time', '>=', $ed_time);
                })
                ->orWhere(function ($query) use ($strt_time, $ed_time) {
                    $query->where('start_time', '>=', $strt_time);
                    $query->where('start_time', '<=', $ed_time);
                })
                ->orWhere(function ($query) use ($strt_time, $ed_time) {
                    $query->where('end_time', '<=', $strt_time);
                    $query->where('end_time', '>=', $ed_time);
                })->pluck('booking_process_id')->toArray();


            // $check_available_id = BookingProcessCourseDetails::where('lunch_start_time', $strt_time)->where('lunch_end_time', $ed_time)->pluck('booking_process_id')->toArray();

            // //$check_available_id = array_intersect($check_available_id, $booking_process_id);

            // $booking_processes_ids = array_unique (array_merge ($booking_processes_ids, $check_available_id));

            $booking_processes_ids = array_intersect($booking_processes_ids, $booking_processes_ids1);
            //dd($booking_processes_ids1);

            $booking_processes_ids_main = array_merge($booking_processes_ids_main, $booking_processes_ids);
            //dd($booking_processes_ids_main);

            $leave_contact_ids = ContactLeave::where('leave_status', 'A')
                ->where(function ($query) use ($strt_date) {
                    $query->where('start_date', '<=', $strt_date);
                    $query->where('end_date', '>=', $strt_date);
                })
                ->orWhere(function ($query) use ($ed_date) {
                    $query->where('start_date', '<=', $ed_date);
                    $query->where('end_date', '>=', $ed_date);
                })
                ->pluck('contact_id')->toArray();

            $leave_contact_ids_main = array_merge($leave_contact_ids_main, $leave_contact_ids);
        }

        if ($booking_process_id == '') {
            $data['booking_processes_ids_main'] = $booking_processes_ids_main;
        } else {
            //$data['booking_processes_ids_main'] = array_splice($booking_processes_ids_main, $booking_process_id);
            $data['booking_processes_ids_main'] = $this->removeElementFromArray($booking_processes_ids_main, $booking_process_id);
        }
        /**For if booking is trashed so remove in available booking list */
        $data['booking_processes_ids_main'] = BookingProcesses::whereIn('id', $data['booking_processes_ids_main'])->where('is_trash', false)->pluck('id')->toArray();
        /** */
        $data['leave_contact_ids_main'] = $leave_contact_ids_main;

        return $data;
    }


    //Get available instructor in lunch hour
    public function getAvailableInstructorInLunchHour($dates_data, $booking_process_id = '')
    {
        $booking_processes_lunch_ids = array();
        $lunch_data = array();

        foreach ($dates_data as $key => $dates) {
            $date_inputs = $dates;
            $start_date1 = explode(" ", $date_inputs['StartDate_Time']);
            $end_date1 = explode(" ", $date_inputs['EndDate_Time']);

            $strt_date = $start_date1[0];
            $ed_date = $end_date1[0];
            $strt_time = $start_date1[1];
            $ed_time = $end_date1[1];

            // $check_available_id = BookingProcessCourseDetails::
            //    where(function ($query) use ($strt_time) {
            //            $query->where('lunch_start_time', '<=', $strt_time);
            //            $query->where('lunch_end_time', '>=', $strt_time);
            //        })
            //        ->orWhere(function ($query) use ($ed_time) {
            //            $query->where('lunch_start_time', '<=', $ed_time);
            //            $query->where('lunch_end_time', '>=', $ed_time);
            //        })
            //        ->orWhere(function ($query) use ($strt_time,$ed_time) {
            //            $query->where('lunch_start_time', '>=', $strt_time);
            //            $query->where('lunch_start_time', '<=', $ed_time);
            //        })
            //        ->orWhere(function ($query) use ($strt_time,$ed_time) {
            //            $query->where('lunch_end_time', '<=', $strt_time);
            //            $query->where('lunch_end_time', '>=', $ed_time);
            //        })->pluck('booking_process_id')->toArray();
            //
            $check_available_id = BookingProcessCourseDetails::where('lunch_start_time', '<=', $strt_time)
                ->where('lunch_end_time', '>=', $ed_time)->pluck('booking_process_id')->toArray();
            if ($booking_process_id != '') {
                $check_available_id = $this->removeElementFromArray($check_available_id, $booking_process_id);
            }
        }
        $lunch_data['booking_processes_lunch_ids'] = $check_available_id;
        // dd($data);

        return $lunch_data;
    }
    //Remove element from array
    public function removeElementFromArray($array, $value)
    {
        return array_diff($array, (is_array($value) ? $value : array($value)));
    }

    //Get VAT Percentage from database
    public function getVat()
    {
        $vat =  SequenceMaster::where('code', 'VAT')->first();
        if ($vat) {
            return $vat->sequence;
        }
        return 20;
    }

    //Get Lunch Percentage from database
    public function getLunchVat()
    {
        $vat =  SequenceMaster::where('code', 'LVAT')->first();
        if ($vat) {
            return $vat->sequence;
        }
        return 10;
    }

    //Openfire API configuration
    public function openfireAPIConfig()
    {
        # Create the Openfire Rest api object
        $api = new OpenFireRestApi;
        # Set the required config parameters
        $api->secret = config('constants.openfireSecret');
        $api->host = config('constants.openfireHost');
        $api->port = config('constants.openfirePort');
        return $api;
    }

    //Add user to oprnfire database
    public function addUserToOpenfire($unique_code, $password, $name, $email)
    {
        $api = $this->openfireAPIConfig();
        $result = $api->addUser($unique_code, $password, $name, $email);
    }

    //Delete user from openfire database
    public function deleteUserFronOpenfire($unique_code)
    {
        $api = $this->openfireAPIConfig();
        $result = $api->deleteUser($unique_code);
    }

    //Add User VCARD detail
    public function addVcardDetail($user)
    {
        $unique_code = $user->jabber_id;
        $id = $user->id;
        $vcard_data = '<vCard xmlns="vcard-temp"><FN/><NICKNAME>' . $user->name . '</NICKNAME><AVATAR>' . $user->contact_detail->profile_pic . '</AVATAR><UNIQUECODE>' . $unique_code . '</UNIQUECODE><USERID>' . $id . '</USERID></vCard>';
        $vcard = VCard::where('username', $unique_code);
        if ($vcard->count() > 0) {
            $vcard->update(['vcard' => $vcard_data]);
        } else {
            VCard::create(['username' => $unique_code, 'vcard' => $vcard_data]);
        }
    }

    //Get User conversion from JID
    public function openfireUserConversion($fromJID, $toJID)
    {
        $user_conversion = MessageArchive::where(function ($q) use ($fromJID, $toJID) {
            $q->where('toJID', $toJID)->where('fromJID', $fromJID);
        })->orWhere(function ($q) use ($fromJID, $toJID) {
            $q->where('toJID', $fromJID)->where('fromJID', $toJID);
        })->orderBy('sentDate', 'desc')->get();

        return $user_conversion;
    }

    // common function to createPayment
    /*
     * ----------------------------- Param 1 ----------------------------
     * array $payment_details - all the payment table details
     * payment_details example
     *  [
     *      "qbon_number": "sssss",
     *      "contact_id": 63,
     *      "payment_type": 4,
     *      "is_office": true,
     *      "amount_given_by_customer": "130",
     *      "amount_returned": 1,
     *      "total_amount": 150,
     *      "total_discount": 10,
     *      "total_vat_amount": 0,
     *      "total_net_amount": 120,
     *  ]
     *
     * ----------------------------- Param 2 ----------------------------
     * array $invoice_data - array of invoices ()
     *
     * invoice_data example
     *  [{
     *      // required fields
     *      "id": 425,
     *
     *      // fields you want to update
     *      "total_price": 10,
     *      "discount": "25",
     *      "vat_amount": 1.25,
     *      "vat_excluded_amount": 6.25,
     *      "net_price": 7.5
     *      "voucher_id": <id of voucher>
     *  }]
     */
    public function createPayment($payment_details, $invoice_data)
    {
        $v = validator($payment_details + ['invoice_data' => $invoice_data], [
            'office_id' => 'required',
            'contact_id' => 'required',
            'payment_type' => 'required',
            'is_office' => 'required|bool',
            'amount_given_by_customer' => 'required',
            'total_amount' => 'required',
            'total_discount' => 'required',
            'total_vat_amount' => 'required',
            'total_net_amount' => 'required',
            'invoice_data' => 'required|array',
            'payment_card_type' => 'nullable',
            'payment_card_brand' => 'nullable',
            'consolidated_invoice_id' => 'nullable',
            'credit_card_type' => 'nullable',
            'cash_amount' => 'nullable',
            'creditcard_amount' => 'nullable',
            // optional
            // 'qbon_number' => '',
            // 'amount_returned' => '',
            // 'total_lunch_amount' => '',
        ]);

        if ($v->fails()) {
            throw new \Exception($v->errors()->first());
        }

        //For if payment threw consolidated so can not get any one payee details
        if (!isset($payment_details['is_threw_consolidated'])) {
            $customer = Contact::find($payment_details['contact_id']);
            if (!$customer) {
                throw new \Exception('Customer not found.');
            }
        }

        $office = Office::find($payment_details['office_id']);
        if (!$office) {
            throw new \Exception('Office not found.');
        }

        $payment_method = PaymentMethod::find($payment_details['payment_type']);
        if (!$payment_method) {
            throw new \Exception('Payment method not found.');
        }

        $sequence_data = SequenceMaster::where('code', 'PMT')->first();

        $sequence = $sequence_data->sequence;
        $payment_number = "PMT" . date('m') . date('Y') . $sequence;

        $sequence_data->update([
            'sequence' => $sequence_data->sequence + 1
        ]);

        $payment = BookingPayment::create($payment_details + [
            'payment_number' => $payment_number
        ]);

        // get element3 main account (ALL ACCOUNTING ENTRIES IN E3 "MAIN ACCOUNT")
        $account = AccountMaster::getElement3Account();

        // create account detail
        $account_id = $account->id;
        $event_code = "BP";
        $event_id = $payment->id;
        $event_date = date('Y-m-d H:i:s');

        $this->updateAccountDetail($account_id, $event_code, $event_id, $event_date, $payment->amount_given_by_customer);

        // update balance of user account
        $account->updateBalance($payment->amount_given_by_customer);

        // update office cash register if the PAYMENT TYPE is CASH or CASH AND CREDITCARD
        if ($payment_method->type === 'C' || $payment_method->type === 'CCC') {
            $amount = $payment->amount_given_by_customer;
            if ($payment_method->type === 'CCC') {
                $amount = $payment->cash_amount;
            }
            $cash_entry_data = [
                'date_of_entry' => date('Y-m-d'),
                'type' => 'BOOKPMT',
                'office_id' => $office->id,
                'description' => 'Payment recorded. #' . $payment_number,
                'amount' => $amount
            ];
            $cash_entry = $this->createCashEntry($cash_entry_data);
        }

        $remain_amount = 0;
        $remain_amount1 = 0;
        $available_remain_amount = true;
        foreach ($invoice_data as $invoice) {
            $bookingInvoice =  BookingProcessPaymentDetails::find($invoice['id']);

            /**If is_reverse_charge then remove vat amount */
            if (isset($payment_details['is_reverse_charge']) && $payment_details['is_reverse_charge']) {
                // $bookingInvoice->update(
                //     [
                //         'vat_percentage' => 0,
                //         'vat_amount' => 0,
                //         // 'vat_excluded_amount' => $bookingInvoice->net_price,
                //         'is_reverse_charge' => true,
                //         'lunch_vat_percentage' => 0,
                //         'lunch_vat_amount' => 0,
                //         'net_price' => $bookingInvoice->net_price - $bookingInvoice->vat_amount,
                //         'outstanding_amount' => $bookingInvoice->outstanding_amount - $bookingInvoice->vat_amount,
                //         // 'lunch_vat_excluded_amount' => $bookingInvoice->lunch_vat_amount + $bookingInvoice->lunch_vat_excluded_amount
                //     ]);
                $invoice['vat_percentage'] = 0;
                $invoice['vat_amount'] = 0;
                $invoice['is_reverse_charge'] = true;
                $invoice['lunch_vat_percentage'] = 0;
                $invoice['lunch_vat_amount'] = 0;
                $invoice['net_price'] = $bookingInvoice->net_price - $bookingInvoice->vat_amount;
                $invoice['outstanding_amount'] = $bookingInvoice->outstanding_amount - $bookingInvoice->vat_amount;
            }

            if (!isset($invoice['net_price'])) {
                $invoice['net_price'] = $bookingInvoice->net_price;
            }
            //For if payment threw consolidated so can not get any one payee details
            if (!isset($payment_details['is_threw_consolidated'])) {
                if ($bookingInvoice->payi_id != $payment_details['contact_id']) {
                    throw new \Exception("Invoice `$bookingInvoice->invoice_number` does not have `$customer->first_name $customer->last_name` as payee.");
                }
                if ($bookingInvoice->status === 'Success') {
                    throw new \Exception("Invoice `$bookingInvoice->invoice_number` is already paid.");
                }
            }

            if (isset($invoice['voucher_id'])) {

                // if voucher is already applied, restrict user to apply voucher again.
                if ($bookingInvoice->voucher_id) {
                    throw new \Exception("Invoice `$bookingInvoice->invoice_number` already has voucher applied. You can only apply one voucher per invoice.");
                }

                $voucher = Voucher::find($invoice['voucher_id']);

                if (!$voucher) {
                    throw new \Exception("Voucher applied to Invoice `$bookingInvoice->invoice_number` is not found.");
                }

                $bookingInvoice = $voucher->apply($bookingInvoice->id);
            }

            if (!isset($payment_details['is_threw_consolidated'])) {
                /**Calculate booking invoice amount */
                $total_payed_amount = InvoicePaymentHistory::where('invoice_id', $invoice['id'])->sum('amount');

                if ($payment_details['amount_given_by_customer'] >= $bookingInvoice->outstanding_amount && $remain_amount1 == 0) {
                    $remain_amount1 = $payment_details['amount_given_by_customer'] - $bookingInvoice->outstanding_amount;
                    $payment_amount = $bookingInvoice->outstanding_amount;
                } else {
                    if ($remain_amount1) {
                        $invoice_amount = $bookingInvoice->outstanding_amount - $remain_amount1;
                        $payment_amount = $remain_amount1;
                    } else {
                        $invoice_amount = $bookingInvoice->outstanding_amount - $payment_details['amount_given_by_customer'];
                        $payment_amount = $payment_details['amount_given_by_customer'];
                    }
                    if ($remain_amount1 >= $bookingInvoice->outstanding_amount) {
                        $payment_amount = $bookingInvoice->outstanding_amount;
                    }
                }
                /**End */

                /**Create invoice history data */
                if ($available_remain_amount) {
                    $invoice_history_data = [
                        "booking_process_id" => $bookingInvoice->booking_process_id,
                        "invoice_id" => $invoice['id'],
                        "booking_payment_id" => $payment->id,
                        "amount" => $payment_amount,
                        "created_at" => date('Y-m-d H:i:s'),
                        "created_by" => auth()->user()->id
                    ];
                    InvoicePaymentHistory::create($invoice_history_data);
                    $total_payed_amount = InvoicePaymentHistory::where('invoice_id', $invoice['id'])->sum('amount');

                    /**End */

                    /**Calculate outstanding booking amount */
                    if ($total_payed_amount >= $invoice['net_price'] && $remain_amount == 0) {
                        $remain_amount = $total_payed_amount - $invoice['net_price'];
                        if ($invoice['net_price'] == $total_payed_amount || $remain_amount < 1) {
                            $invoice_amount = 0;
                            $payment_status = 'Success';
                        } else {
                            $invoice_amount = $remain_amount;
                            $payment_status = 'Outstanding';
                        }
                    } else {
                        if ($remain_amount) {
                            $invoice_amount = $invoice['net_price'] - $remain_amount;
                        } else {
                            $invoice_amount = $invoice['net_price'] - $total_payed_amount;
                        }
                        $payment_status = 'Outstanding';

                        if ($invoice_amount < 1) {
                            $payment_status = 'Success';
                            $invoice_amount = 0;
                        }

                        if ($remain_amount >= $invoice['net_price']) {
                            $payment_status = 'Success';
                        }
                    }
                } else {
                    $payment_status = $bookingInvoice->status;
                    $invoice_amount = $bookingInvoice->outstanding_amount;
                }
                if ($bookingInvoice->outstanding_amount > $payment_details['amount_given_by_customer']) {
                    $available_remain_amount = false;
                }
                /**End */
            } else {
                $payment_status = 'Success';
                $invoice_amount = 0;
                $invoice_history_data = [
                    "booking_process_id" => $bookingInvoice->booking_process_id,
                    "invoice_id" => $invoice['id'],
                    "booking_payment_id" => $payment->id,
                    "amount" => $bookingInvoice->outstanding_amount - $bookingInvoice->vat_amount,
                    "created_at" => date('Y-m-d H:i:s'),
                    "created_by" => auth()->user()->id
                ];
                InvoicePaymentHistory::create($invoice_history_data);
            }

            $credit_card_type = null;
            if (isset($payment_details['credit_card_type'])) {
                $credit_card_type = $payment_details['credit_card_type'];
            }

            if ($bookingInvoice) {
                if (!$bookingInvoice->is_cancelled) {
                    $bookingInvoice->update(
                        [
                            'payment_method_id' => $payment_method->id,
                            'status' => $payment_status,
                            'payment_id' => $payment->id,
                            'outstanding_amount' => $invoice_amount,
                            'credit_card_type' => $credit_card_type
                        ] + $invoice
                    );
                }
            }

            /**Add crm user action trail */
            if ($invoice) {
                $action_id = $invoice['id']; //Inoice id
                $action_type = 'RP'; //RP: Record Payment
                $module_id = 23; //module id base module table
                $module_name = "Payments"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);

                /**This variable for send obono to register the total invoices */
                // $invoice = BookingProcessPaymentDetails::where('id', $invoice['id'])
                // ->first();
                $totalInvoice[] = $bookingInvoice->invoice_number;
            }
            /**End manage trail */

            /**For payment is done then send email for payee with invoice attachment */
            // if (($payment_details['payment_type']!=3) || ($payment_details['payment_type']!=4)) {
            if (!isset($payment_details['is_threw_consolidated'])) {
                $urls[] = $this->upatePayementInvoiceLink($invoice['id']);
            }
            $booking_numbers[] = $bookingInvoice->booking_detail->booking_number;
            $invoice_numbers[] = $bookingInvoice->invoice_number;
            // }
            /**End */
        }

        if (isset($payment_details['is_threw_consolidated'])) {
            $consolidated_invoice = ConsolidatedInvoice::find($payment_details['consolidated_invoice_id']);

            if (isset($payment_details['is_reverse_charge']) && $payment_details['is_reverse_charge']) {
                ConsolidatedInvoice::find($payment_details['consolidated_invoice_id'])
                    ->update(
                        [
                            'vat_percentage' => 0,
                            'vat_amount' => 0,
                            'vat_excluded_amount' => $consolidated_invoice->total_amount - $payment_details['total_vat'],
                            'grant_amount' => $consolidated_invoice->grant_amount - $payment_details['total_vat'],
                            'total_amount' => $consolidated_invoice->total_amount - $payment_details['total_vat'],
                            'is_reverse_charge' => true
                        ]
                    );
                ConsolidatedInvoiceProduct::where('consolidated_invoice_id', $payment_details['consolidated_invoice_id'])
                    ->update(
                        [
                            // 'product_vat' => 0,
                        ]
                    );

                $booking_payment_details = BookingProcessPaymentDetails::whereIn('id', $consolidated_invoice->invoices)
                    ->get();
                $inv_numbers = array();
                foreach ($booking_payment_details as $booking_payment) {
                    if ($booking_payment['status'] != 'Success') {
                        $exploded_invoice_number = explode('INV', $booking_payment['invoice_number']);
                        $inv_numbers[] = (isset($exploded_invoice_number[1]) ? $exploded_invoice_number[1] : '');
                    } else {
                        $inv_numbers[] = $booking_payment['invoice_number'];
                    }
                }
                $inv_numbers = implode(", ", $inv_numbers);
                /**Update consoldiate invoice */
                $this->generateConsolidatedInvoice($payment_details['consolidated_invoice_id'], $inv_numbers);
            }

            $emails = $consolidated_invoice->emails;

            if (count($emails) > 0) {
                $booking_number = $booking_numbers;
                $customer_name = $consolidated_invoice->name;

                // get consolidated invoice url link
                $urls[] = $consolidated_invoice->consolidated_receipt;

                $template_data = [
                    'name' => $customer_name,
                    'booking_numbers' => array_unique($booking_numbers),
                    'invoice_numbers' => $invoice_numbers,
                    'payment_method' => $payment_method->type,
                ];

                $mail = new Mail();
                $mail::send('email.customer.after_customer_payment_success', $template_data, function ($message) use ($urls, $emails, $template_data, $customer_name, $invoice_numbers) {
                    $message->to($emails)
                        ->subject(__('email_subject.customer_payment_success', ['customer_name' => $customer_name, 'payment_at' => date('h:i a')]));
                    foreach ($urls as $key => $url) {
                        $message->attachData(file_get_contents($url), $customer_name . "_consolidated_invoice.pdf");
                    }
                });
            }
        } else {
            $email = $customer->email;

            if ($email) {
                $booking_number = $booking_numbers;
                $customer_name = ucfirst($customer->salutation) . " " . ucfirst($customer->first_name) . " " . ucfirst($customer->last_name);
                $template_data = [
                    'name' => $customer_name,
                    'booking_numbers' => array_unique($booking_numbers),
                    'invoice_numbers' => $invoice_numbers,
                    'payment_method' => $payment_method->type,
                ];

                /**Get default locale and set user language locale */
                $temp_locale = \App::getLocale();
                $user = User::where('email', $email)->first();
                if ($user) {
                    \App::setLocale($user->language_locale);
                }
                /**End */

                $mail = new Mail();
                $mail::send('email.customer.after_customer_payment_success', $template_data, function ($message) use ($urls, $email, $template_data, $customer_name, $invoice_numbers) {
                    $message->to($email)
                        ->subject(__('email_subject.customer_payment_success', ['customer_name' => $customer_name, 'payment_at' => date('h:i a')]));
                    foreach ($urls as $key => $url) {
                        $message->attachData(file_get_contents($url), $invoice_numbers[$key] . "_invoice.pdf");
                    }
                });

                /**Set default language locale */
                \App::setLocale($temp_locale);
            }
        }

        /**If cash threw payment then invoice records are add in obono */
        if ($payment_method->type === 'C' || $payment_method->type === 'CC' || $payment_method->type === 'CCC') {
            $belegUuid = $this->generateReceiptObonoUuid(); // Generate V4 Uuid
            $paymentNumber = $payment->payment_number;
            if (!isset($payment_details['is_threw_consolidated'])) {
                $payee = $customer->salutation . " " . $customer->first_name . " " . $customer->last_name;
            } else {
                $payee = $customer_name; //For if payment threw consolidated so can not get any one payee details
            }
            $vat_excluded_amount = ($payment->amount_given_by_customer) - ($payment->total_vat_amount);

            $data = [
                "total_amount" => $payment->total_amount ? $payment->total_amount : 0,
                "total_discount" => $payment->total_discount ? $payment->total_discount : 0,
                "total_net_amount" => $payment->amount_given_by_customer ? $payment->amount_given_by_customer : 0,
                "vat_excluded_amount" => $vat_excluded_amount ? $vat_excluded_amount : 0,
                "payee_name" => $payee,
            ];
            $addReceipt = $this->addReceiptCashRegisterToObono($belegUuid, $paymentNumber, $totalInvoice, $data); //Add Receipt To Cash Register
            if ($addReceipt) {
                $getReceiptDetail = $this->getSingleReceiptsToObono('', $belegUuid); // Get Receipt Detail
                if ($getReceiptDetail) {
                    $receiptData1 = (array)$getReceiptDetail['data'];
                    $receiptData = (array)$receiptData1['Belegdaten'];
                    $belegUUID = $receiptData['Beleg-UUID'];
                    $belegNummer = $receiptData['Belegnummer'];

                    //Get Pdf Export Obono Recipt
                    $api = $this->obonoAPIConfig(); //Obono API Configuration Function

                    //Get Access Token and Registerkasse Uuid Using Obono Auth Api
                    $authData = $this->authToObono();
                    $data = json_encode($authData);
                    $data1 = json_decode($data, true);
                    $accessToken = $data1['data']['accessToken'];

                    $result = $api->exportPdfReceiptBybelegUuid($accessToken, $belegUUID);
                    $result_j = json_decode(json_encode($result), true);


                    $updateData = [
                        "beleg_uuid" => $belegUUID, //Beleg-UUID for obono
                        "beleg_nummer" => $belegNummer, //Belegnummer for obono
                        "obono_pdf_receipt" => $result_j['url'] //Obono Pdf Receipt
                    ];

                    $payment['obono_pdf_receipt'] = $result_j['url'];

                    $update = BookingPayment::where('payment_number', $paymentNumber)
                        ->update($updateData);
                }
            }
        }
        /**End obono integration */

        // $email = $customer->email;

        // if ($email) {
        //     $booking_number = $booking_numbers;
        //     $customer_name = ucfirst($customer->salutation)." ".ucfirst($customer->first_name)." ".ucfirst($customer->last_name);
        //     $template_data = [
        //         'name' => $customer_name,
        //         'booking_numbers' => array_unique($booking_numbers),
        //         'invoice_numbers' => $invoice_numbers,
        //         'payment_method' => $payment_method->type,
        //     ];

        //     $mail = new Mail();
        //     $mail::send('email.customer.after_customer_payment_success', $template_data, function ($message) use ($urls,$email,$template_data,$customer_name,$invoice_numbers) {
        //         $message->to($email)
        //         ->subject(__('email_subject.customer_payment_success',['customer_name' => $customer_name,'payment_at' => date('h:i a')]));
        //     foreach ($urls as $key => $url) {
        //             $message->attachData(file_get_contents($url), $invoice_numbers[$key]."_invoice.pdf");
        //         }
        //     });
        // }


        return $payment;
    }

    public function createCashEntry($inputs)
    {
        $v = validator($inputs, [
            'type'          => 'required|in:CHKIN,CHKOUT,CASHOUT,CASHIN,BOOKPMT',
            'office_id'     => 'required',
            'description'   => 'required',
            'contact_id'    => 'required_if:type,CASHOUT|required_if:type,CASHIN',
            'date_of_entry' => 'required|date',
            'amount'        => 'required|numeric'
        ]);

        if ($v->fails()) {
            throw new \Exception($v->errors()->first());
        }

        $entry_date = $inputs['date_of_entry'];
        $office_id = $inputs['office_id'];
        $type = $inputs['type'];
        $office = Office::find($office_id);
        if (!$office) {
            throw new \Exception('Office not found.');
        }

        // check if this is the first entry of the day
        $today_entries = Cash::where('office_id', $office_id)
            ->where('date_of_entry', $entry_date)
            ->get();

        if ($today_entries->count() === 0) {
            // get last entry before today
            $last_entry_before_today = CASH::where('office_id', $office_id)
                ->where('date_of_entry', '<=', $entry_date)
                ->orderBy('date_of_entry', 'DESC')
                ->orderBy('id', 'DESC')
                ->first();

            $balance = 0;
            if ($last_entry_before_today) {
                $balance = $last_entry_before_today->running_amount;
            } elseif ($office->opening_balance >= 0) {
                $balance = $office->opening_balance;
            }

            // today's first checkin entry
            if ($type === 'CHKIN') {
                // manual
                $checkin_amount = $inputs['amount'];
                $checkin_description = $inputs['description'];
            } else {
                // automated
                if ($balance < $office->day_start_sum) {
                    $checkin_amount = $office->day_start_sum - $balance;
                } else {
                    $checkin_amount = 0;
                }
                $checkin_description = 'AUTOMATED CHECK - IN with "start of day amount"';
            }

            $checkin_entry = Cash::create([
                'type' => 'CHKIN',
                'office_id' => $office_id,
                'date_of_entry' => $entry_date,
                'description' => $checkin_description,
                'amount' => $checkin_amount,
                'running_amount' => $balance + $checkin_amount
            ]);

            // update all following records AFTER THAT DAY
            Cash::where('office_id', $checkin_entry->office_id)
                ->where('date_of_entry', '>', $checkin_entry->date_of_entry)
                ->increment('running_amount', $checkin_amount);

            /* DO NOT MAINTAIN Accounting entries for office */
            /* $account = $office->getAccount();
            //create account detail
            $account_id = $account->id;
            $event_code = 'CHKIN';
            $event_id = $checkin_entry->id;
            $event_date = $checkin_entry->date_of_entry;

            $this->updateAccountDetail($account_id, $event_code, $event_id, $event_date, $checkin_entry->amount);

            // update balance of user account
            $account->updateBalance($checkin_entry->amount); */

            // if first check-in entry return success
            if ($type === 'CHKIN') {
                return $checkin_entry;
            }
        }

        // if check-out has already happened dont allow any cash activity
        if ($today_entries->where('type', 'CHKOUT')->count() === 1) {
            throw new \Exception('You cannot perform any cash entries as you have checked out for the day.');
        }

        if ($type === 'CHKIN') {
            $cash_data = collect($inputs)->only('type', 'office_id', 'description', 'date_of_entry', 'amount');
        } elseif ($type === 'CASHOUT') {
            $cash_data = collect($inputs)->only('type', 'office_id', 'contact_id', 'description', 'date_of_entry', 'amount');
        } elseif ($type === 'CASHIN') {
            $cash_data = collect($inputs)->only('type', 'office_id', 'contact_id', 'description', 'date_of_entry', 'amount');
        } else {
            $cash_data = collect($inputs)->only('type', 'office_id', 'description', 'date_of_entry', 'amount');
        }

        // create cash entry
        $cash_entry = Cash::create($cash_data->toArray());

        // get last cash entry of the office of the day
        $todays_last_entry = CASH::where('id', '<', $cash_entry->id)
            ->where('office_id', $office_id)
            ->where('date_of_entry', $entry_date)
            ->orderBy('id', 'desc')->first();

        // update running balance
        if ($cash_entry->type != 'CASHOUT') {
            $amount = $cash_entry->amount;
            $running_amount = $todays_last_entry->running_amount + $cash_entry->amount;
        } else {
            $amount = (0 - $cash_entry->amount);
            $running_amount = $todays_last_entry->running_amount - $cash_entry->amount;
        }

        $cash_entry->update([
            'running_amount' => $running_amount,
        ]);

        // update all following records AFTER THAT DAY
        Cash::where('office_id', $cash_entry->office_id)
            ->where('date_of_entry', '>', $cash_entry->date_of_entry)
            ->increment('running_amount', $amount);

        /* DO NOT MAINTAIN Accounting entries for office */
        //create account detail
        /* $account = $office->getAccount();
        $account_id = $account->id;
        $event_code = $cash_entry->type;
        $event_id = $cash_entry->id;
        $event_date = $cash_entry->date_of_entry;

        $this->updateAccountDetail($account_id, $event_code, $event_id, $event_date, $cash_entry->amount);

        // update balance of user account
        $account->updateBalance($amount); */

        return $cash_entry;
    }

    public function createChatroom($naturalName, $roomName, $description)
    {
        $data = [
            'naturalName' => $naturalName,
            'roomName' => $roomName,
            'description' => $description,
            'persistent' => true
        ];
        $api = $this->openfireAPIConfig();
        $result = $api->createChatRoom($data);
        Log::info("create chat room");
        Log::info($result);
    }

    public function addUserToChatroom($roomName, $name, $roles)
    {
        $api = $this->openfireAPIConfig();
        $result = $api->addUserRoleToChatRoom($roomName, $name, $roles);
        Log::info("Add user to chat room");
        Log::info($result);
    }

    /* API for send push notification for instructor for send email and push notification for course confirme */
    public function sendNotificationCourseConfirm($booking_process_id, $instructor, $interval = [])
    {
        $email_data['instructor_name'] = Contact::where('id', $instructor)->select('id', 'first_name')->first()->toArray();
        $email_data['app_link'] = 'E3 App';

        $booking_processes = BookingProcesses::find($booking_process_id);
        $booking_number = $booking_processes->booking_number;

        $email_data['course_details'] = BookingProcessCourseDetails::where('booking_process_id', $booking_process_id)->first()->toArray();

        $customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_process_id)->pluck('customer_id')->toArray();

        $contacts = Contact::whereIn('id', $customer_details)->get();
        foreach ($contacts as $contact) {
            $customer_name[] = $contact->first_name;
        }
        $email_data['customer_names'] = $customer_name;

        $course = Course::find($email_data['course_details']['course_id']);

        $user_detail = User::where('contact_id', $instructor)
            ->where('is_notification', 1)
            ->select('id', 'email', 'device_token', 'device_type', 'is_notification')
            ->first();

        ($course ? $course_name = $course->name : $course_name = '');

        if ($user_detail) {
            $title = "Course Alert: Get Ready for your Booking Tomorrow";
            $body = "Hello " . $email_data['instructor_name']['first_name'] . ", your booking " . $course->name . " starts in " . $interval->h . " hours, please confirm the status in ‘Running Bookings’";

            $notification = Notification::create(["receiver_id" => $instructor, "type" => 15, 'email_scheduler_type' => 'FP', 'message' => $body, 'booking_process_id' => $booking_process_id]);

            if ($user_detail['is_notification'] === 1) {
                $notifaction_data['course_id'] = $email_data['course_details']['course_id'];
                $notifaction_data['booking_process_id'] = $booking_process_id;
                $notifaction_data['instructor_id'] = $instructor;

                SendPushNotification::dispatch($user_detail['device_token'], $user_detail['device_type'], $title, $body, 15, $notifaction_data);
            }
            $reset_token = Str::random(50);

            $input['booking_token'] = $reset_token;
            $user = BookingProcessInstructorDetails::where('booking_process_id', $booking_process_id)
                ->where('contact_id', $instructor)
                ->update($input);
            $email_data['deep_link'] = url('/d/instructor/course-confirm?reset_token=' . $reset_token . "&booking_process_id=" . $booking_process_id . "&instructor_id=" . $instructor);

            // $user_detail->notify(new BookingAlertFivePm($email_data, $booking_number, $course_name));
            $user_detail->notify((new BookingAlertFivePm($email_data, $booking_number, $course_name))->locale($user_detail->language_locale));
        }
        return;
    }

    /* Update Expenditure Status */
    public function updateExpenditureStatus($id, $status, $reason)
    {
        $v = validator(['status' => $status] + ['reason' => $reason], [
            'status' => 'required|in:A,R',
            'reason' => 'requiredif:status,R',
        ]);

        if ($v->fails()) {
            throw new \Exception($v->errors()->first());
        }

        \Log::info('Expenditure status update start for ID: ' . $id . ' for Status: ' . $status);

        $expenditure = Expenditure::find($id);
        if (!$expenditure) {
            throw new \Exception("Expenditure `$id` not found.");
        }

        if (auth()->user()->type() === 'Instructor' && $expenditure->user_id !== auth()->user()->id) {
            throw new \Exception('You are not allowed to update the expenditure.');
        }

        $detail = new ExpenditureDetail();
        $detail->action = $status;
        $notification_type = 16;
        $notification_body = __('notifications.expenditure_approved');

        if ($status === 'R') {
            $detail->rejection_deletion_reason = $reason;
            $notification_type = 17;
            $notification_body = __('notifications.expenditure_rejected');
        }

        $expenditure->details()->save($detail);

        $expenditure->update([
            'tax_consultation_status' => $status,
            'tax_consultation_done_by' => auth()->user()->id
        ]);

        $user = $expenditure->user;
        if ($user->type() === 'Instructor') {
            $notification = Notification::create([
                "receiver_id" => $user->contact_detail->id,
                "type"        => $notification_type,
                'message'     => $notification_body,
            ]);
            if ($user->is_notification == 1) {
                if (!empty($user->device_token)) {
                    $title = __('notifications.expenditure_status_changed_title');


                    $data['expenditure_id'] = $expenditure->id;

                    SendPushNotification::dispatch($user->device_token, $user->device_type, $title, $notification_body, $notification_type, $data);
                }
            }
        }

        \Log::info('Expenditure status update end for ID: ' . $id . ' for Status: ' . $status);

        return $expenditure;
    }

    //Check course exist for given day
    public function checkCourseForDays($cal_payment_type, $no_of_days, $course_id, $season, $daytime)
    {
        if ($no_of_days == 0) {
            return null;
        }

        $course_detail_data = CourseDetail::where('session', $season)
            ->where('cal_payment_type', $cal_payment_type)
            ->where('no_of_days', $no_of_days)
            ->where('course_id', $course_id);

        /**If get course with input details other wise get default value base course */
        $course_detail_data = $course_detail_data->where(function ($q) use ($daytime) {
            $q->where('time', $daytime);
            $q->orWhere('time', 'Whole Day');
        })->first();

        if ($course_detail_data) {
            return $course_detail_data;
        }
        $no_of_days = $no_of_days - 1;
        $data = $this->checkCourseForDays($cal_payment_type, $no_of_days, $course_id, $season, $daytime);
        if ($data) {
            return $data;
        }
    }

    //Check course exist for given hours
    public function checkCourseForHours($cal_payment_type, $hours_per_day, $course_id, $season, $daytime)
    {
        if ($hours_per_day == 0) {
            return null;
        }

        $course_detail_data = CourseDetail::where('session', $season)
            ->where('cal_payment_type', $cal_payment_type)
            ->where('hours_per_day', $hours_per_day)
            ->where('course_id', $course_id);

        /**If get course with input details other wise get default value base course */
        $course_detail_data = $course_detail_data->where(function ($q) use ($daytime) {
            $q->where('time', $daytime);
            $q->orWhere('time', 'Whole Day');
        })->first();

        if ($course_detail_data) {
            return $course_detail_data;
        }
        $hours_per_day = $hours_per_day - 1;
        $data = $this->checkCourseForHours($cal_payment_type, $hours_per_day, $course_id, $season, $daytime);
        if ($data) {
            return $data;
        }
    }

    /**
     * Upload private document on amazon s3
     * @param  string $directory Upload directory
     * @param  string $attachment     File attachment
     * @return string           url of document
     */
    public function upload_private_document($directory, $attachment)
    {
        $originalName = $attachment->getClientOriginalName();
        $extension = $attachment->getClientOriginalExtension();
        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
        $filenametostore = $directory . '/' . $fileName . '_' . time() . '.' . $extension;
        //Upload File to s3 private
        Storage::disk('s3Private')->put($filenametostore, fopen($attachment, 'r+'));
        return $filenametostore;
    }

    /**
     * Get Private document from amazon s3
     * @param  string $url url of documents
     * @return string      Temporary url
     */
    public function get_private_document($url)
    {
        $seconds = config('constants.file_access_seconds');
        $src     = Storage::disk('s3Private')->temporaryUrl($url, now()->addSeconds($seconds));
        return $src;
    }

    /**For manage crm user action trail like user do anything in crm admin panel so manage the trail */
    public function addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name = null, $created_by = null)
    {
        if (isset($module_id) && empty($module_name)) {
            $module = Modules::where('id', $module_id)->pluck('name');
            $module_name = $module[0];
        }
        $input['action_id'] = $action_id;
        $input['action_type'] = $action_type;
        $input['module_id'] = $module_id;
        $input['module_name'] = $module_name;
        $input['created_by'] = auth()->user() ? auth()->user()->id : null;

        if ($created_by != null) {
            $input['created_by'] = $created_by;
        }
        $trail = CrmUserActionTrail::create($input);
        return $trail;
    }
    /**Check URL is accessable or not for this Logged in admin */
    public function getRoleBaseUrl($module_data)
    {
        $role_id = auth()->user()->role;
        $data = array();
        $data['module'] = $module_data['module_name'];

        $user_id = auth()->user()->id;
        $duo_valid = User::where('id', $user_id)->where('is_duo_loggedin', true)->count();
        /**For check user logged in with duo */
        if (!$duo_valid) {
            $data['valid'] = false;
            $data['msg'] = 'User not logged in.';
            return $data;
        }

        /**For check module available or not */
        $module = Module::where('name', $module_data['module_name'])->first();
        if (!$module) {
            $data['valid'] = false;
            $data['msg'] = 'Module not found';
            return $data;
        }

        /**For check permissions assigned to module or not */
        $module_permission = ModuleRolePermission::where('role', $role_id)
            ->where('module', $module->id)
            ->first();

        if (!$module_permission) {
            $data['valid'] = false;
            $data['msg'] = 'Module Permissions not assigned for logged in user';
            return $data;
        }

        $permission_array = json_decode($module_permission->premissionArray, true);

        /**For check passed permissions is available in list or not */
        if (array_key_exists($module_data['type'], $permission_array)) {
            $data['msg'] = 'success';
            $data['valid'] = $permission_array[$module_data['type']];
        } else {
            $data['msg'] = 'Invalid Permissions';
            $data['valid'] = false;
        }
        return $data;
    }

    /**For get season and daytime base startdate, enddate, startime, endtime */
    public function getSeasonDaytime($start_date, $end_date, $start_time, $end_time)
    {
        $data = array();
        if (isset($start_date) && isset($end_date) && isset($start_time) && isset($end_time)) {
            $season = SeasonDaytimeMaster::Where(function ($query) use ($start_date) {
                $query->where('start_date', '<=', $start_date);
                $query->where('end_date', '>=', $start_date);
            })
                ->orWhere(function ($query) use ($end_date) {
                    $query->where('start_date', '<=', $end_date);
                    $query->where('end_date', '>=', $end_date);
                })->pluck('name');

            $daytime = SeasonDaytimeMaster::Where(function ($query) use ($start_time) {
                $query->where('start_time', '<=', $start_time);
                $query->where('end_time', '>=', $start_time);
            })
                ->orWhere(function ($query) use ($end_time) {
                    $query->where('start_time', '<=', $end_time);
                    $query->where('end_time', '>=', $end_time);
                })->pluck('name');
        }

        /**If nothing get in critearia then set default values */
        if (count($season) == 0) {
            $season[0] = 'Day';
        }

        if (count($daytime) == 0) {
            $daytime[0] = 'Whole Day';
        }

        $data['season'] = $season[0];
        $data['daytime'] = $daytime[0];

        return $data;
    }

    /**For update payment invoice link */
    public function upatePayementInvoiceLink($id)
    {
        $payment_detail = BookingProcessPaymentDetails::where('id', $id)
            ->first();

        if (!$payment_detail) {
            return $this->sendResponse(false, 'Booking Processes Invoice not found');
        }

        $booking_processes = BookingProcesses::find($payment_detail->booking_process_id);

        if (!$booking_processes) {
            return $this->sendResponse(false, 'Booking Processes not found');
        }

        $pdf_data['booking_no'] = $booking_processes->booking_number;
        $i = 0;
        // foreach ($booking_processes_payment_details as $payment_detail) {
        // dd($payment_detail);

        //foreach ($booking_processes_customer_details as $key => $customer_detail) {
        $contact_data = Contact::with('address.country_detail:id,name,code')->find($payment_detail['customer_id']);
        $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail['payi_id']);

        if (empty($contact_data) || empty($contact)) {
            return;
        }

        /**If sub child exist */
        if (isset($payment_detail['sub_child_id']) && $payment_detail['sub_child_id']) {
            $contact_data = SubChildContact::find($payment_detail['sub_child_id']);
            $pdf_data['customer'][$i]['customer_name'] = $contact_data->first_name . " " . $contact_data->last_name;
        } else {
            $pdf_data['customer'][$i]['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;
        }

        $course = DB::table('booking_process_course_details')->join('courses as c', 'c.id', '=', 'booking_process_course_details.course_id')
            ->where('booking_process_course_details.booking_process_id', $payment_detail->booking_process_id)
            ->first();

        $pdf_data['customer'][$i]['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;

        $contact_address = ContactAddress::with('country_detail')->where('contact_id', $payment_detail['payi_id'])->first();

        ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . ($contact_address->country_detail ? $contact_address->country_detail->name : '') . "." : $address = "";

        $pdf_data['customer'][$i]['payi_id'] = $payment_detail['payi_id'];
        $pdf_data['customer'][$i]['payi_name'] = $contact->salutation . "" . $contact->first_name . " " . $contact->last_name;
        $pdf_data['customer'][$i]['payi_address'] = $address;
        $pdf_data['customer'][$i]['payi_contact_no'] = $contact->mobile1;
        $pdf_data['customer'][$i]['payi_email'] = $contact->email;
        $pdf_data['customer'][$i]['no_of_days'] = $payment_detail['no_of_days'];
        $pdf_data['customer'][$i]['refund_payment'] = $payment_detail['refund_payment'];
        /* $pdf_data['customer'][$i]['StartDate_Time'] = $customer_detail->StartDate_Time;
        $pdf_data['customer'][$i]['EndDate_Time'] = $customer_detail->EndDate_Time; */
        $pdf_data['customer'][$i]['total_price'] = $payment_detail['total_price'];
        $pdf_data['customer'][$i]['extra_participant'] = $payment_detail['extra_participant'];
        $pdf_data['customer'][$i]['discount'] = $payment_detail['discount'];
        /* if($payment_detail['voucher']) {
            if($payment_detail->voucher->amount_type === 'P') {
                $pdf_data['customer'][$i]['voucher'] = $payment_detail->voucher->amount.' %';
            } else {
                $pdf_data['customer'][$i]['voucher'] = $payment_detail->voucher->amount.' €';
            }
        } */
        $pdf_data['customer'][$i]['net_price'] = $payment_detail['net_price'];
        $pdf_data['customer'][$i]['vat_percentage'] = $payment_detail['vat_percentage'];
        $pdf_data['customer'][$i]['vat_amount'] = $payment_detail['vat_amount'];
        $pdf_data['customer'][$i]['vat_excluded_amount'] = $payment_detail['vat_excluded_amount'];
        $pdf_data['customer'][$i]['invoice_number'] = $payment_detail['invoice_number'];
        $pdf_data['customer'][$i]['invoice_date'] = $payment_detail['created_at'];

        $pdf_data['customer'][$i]['lunch_vat_amount'] = $payment_detail['lunch_vat_amount'];
        $pdf_data['customer'][$i]['lunch_vat_excluded_amount'] = $payment_detail['lunch_vat_excluded_amount'];
        $pdf_data['customer'][$i]['is_include_lunch'] = $payment_detail['is_include_lunch'];
        $pdf_data['customer'][$i]['include_lunch_price'] = $payment_detail['include_lunch_price'];
        $pdf_data['customer'][$i]['lunch_vat_percentage'] = $payment_detail['lunch_vat_percentage'];
        $pdf_data['customer'][$i]['payment_status'] = $payment_detail['status'];

        /**Add settelement amount details in invoice */
        $pdf_data['customer'][$i]['settlement_amount'] = $payment_detail['settlement_amount'] ? $payment_detail['settlement_amount'] : null;
        $pdf_data['customer'][$i]['settlement_description'] = $payment_detail['settlement_description'];
        /**End */
        $pdf_data['customer'][$i]['outstanding_amount'] = $payment_detail['outstanding_amount'];
        $pdf_data['customer'][$i]['is_reverse_charge'] = $payment_detail['is_reverse_charge'];
        $pdf_data['customer'][$i]['course_name'] = ($course ? $course->name : null);

        $pdf = PDF::loadView('bookingProcess.customer_invoice', $pdf_data);
        $url = 'Invoice/' . $contact->first_name . '_invoice' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        $booking_processes_payment_details = BookingProcessPaymentDetails::where('id', $id);

        $update_data['invoice_link'] = $url;
        $update = $booking_processes_payment_details->update($update_data);
        // $i++;
        // }
        return $url;
    }
    /**For send push notification and email for the instructor and customer for any changes */
    public function sendUpdatesInstructorCustomer($booking_processes_id, $new_instructor_id = null, $update)
    {
        try {
            $email_data = array();
            $booking_processes = BookingProcesses::find($booking_processes_id);
            $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes_id)->first();
            $course_id = $booking_processes_course_datail->course_id;

            $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_id)->get()->toArray();
            $update_at = date('H:i:s');

            foreach ($booking_processes_customer_details as $key => $customer_detail) {
                $contact = Contact::find($customer_detail['customer_id']);
                $email_data['booking_number'] = $booking_processes->booking_number;
                //$email_data['start_date'] = date_format($start_date[0], 'Y/m/d');
                $email_data['course_type'] = $booking_processes_course_datail->course_type;
                $email_data['meeting_point'] = $booking_processes_course_datail->meeting_point;
                $email_data['start_date'] = $customer_detail['start_date'];
                $email_data['start_time'] = $customer_detail['start_time'];
                $email_data['end_date'] = $customer_detail['end_date'];
                $email_data['end_time'] = $customer_detail['end_time'];
                // $email_data['salutation'] = $contact->salutation;
                $email_data['first_name'] = $contact->first_name;
                // $email_data['last_name'] = $contact->last_name;
                $email_data['app_link'] = 'E3App';

                // $contact->notify(new UpdateBooking($email_data, $booking_processes->booking_number, $update_at));
                $contact->notify((new UpdateBooking($email_data, $booking_processes->booking_number, $update_at))->locale($contact->language_locale));
            }

            //For send email and notification for course update
            $booking_processes_instructor_details = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes_id)->get()->toArray();

            foreach ($booking_processes_instructor_details as $key => $instructor_detail) {
                // foreach ($booking_processes_customer_details as $key => $customer_detail) {
                if (($instructor_detail['contact_id'] == $new_instructor_id) && ($new_instructor_id != null) && ($update == false)) {
                    /**For instructor push notification and update email */
                    $res = $this->sendInstructoreUpdates($new_instructor_id, $booking_processes_id);
                    continue;
                    /**End instructor push notification/email */
                }
                $contact = Contact::find($instructor_detail['contact_id']);
                $email_data['booking_number'] = $booking_processes->booking_number;
                //$email_data['start_date'] = date_format($start_date[0], 'Y/m/d');
                $email_data['course_type'] = $booking_processes_course_datail->course_type;
                $email_data['meeting_point'] = $booking_processes_course_datail->meeting_point;
                $email_data['start_date'] = $booking_processes_course_datail->start_date;
                $email_data['start_time'] = $booking_processes_course_datail->start_time;
                $email_data['end_date'] = $booking_processes_course_datail->end_date;
                $email_data['end_time'] = $booking_processes_course_datail->end_time;
                // $email_data['salutation'] = $contact->salutation;
                $email_data['first_name'] = $contact->first_name;
                // $email_data['last_name'] = $contact->last_name;
                $email_data['app_link'] = 'E3App';

                // $contact->notify(new UpdateBooking($email_data, $booking_processes->booking_number, $update_at));
                $contact->notify((new UpdateBooking($email_data, $booking_processes->booking_number, $update_at))->locale($contact->language_locale));

                //for send push notification
                $user_detail = User::where('contact_id', $instructor_detail['contact_id'])->first();
                if ($user_detail) {
                    if ($user_detail->is_notification) {
                        $data = ['booking_processes_id' => $booking_processes_id];
                        $data = ['course_id' => $course_id];
                        $type = 2;
                        $course = Course::find($course_id);
                        $title = "Your Course is Updated";
                        $body = "Your Course: " . $course->name . " is Updated";
                        /* $title = "Your Booking is Updated";
                        $body =  "Your Booking has been updated"; */
                        SendPushNotification::dispatch($user_detail['device_token'], $user_detail['device_type'], $title, $body, $type, $data);
                    }
                }
            }
            return 1;
        } catch (\Exception $e) {
            Log::info("Exception accured in booking id: " . $booking_processes_id . " for send updates for instructor/customer");
        }
    }

    /**For instructor push notification and update email */
    public function sendInstructoreUpdates($contact_id, $booking_process_id)
    {
        try {
            $user_detail = User::where('contact_id', $contact_id)->first();
            if ($user_detail) {
                $receiver_id = $contact_id;
                $course_id = BookingProcessCourseDetails::where('booking_process_id', $booking_process_id)->first()->course_id;
                $data = ['booking_processes_id' => $booking_process_id];
                $data = ['course_id' => $course_id];
                $course = Course::find($course_id);

                $course_name = $course->name;
                $course_type = $course->type;
                $type = 25;
                $title = "A " . $course_type . " course " . $course_name . " has been assigned to you, kindly confirm it!";
                $body =  "A " . $course_type . " course " . $course_name . " has been assigned to you, kindly confirm it!";

                $notification = Notification::create(['sender_id' => auth()->user()->id, "receiver_id" => $receiver_id, "type" => $type, 'message' => $body, 'booking_process_id' => $booking_process_id]);

                if ($user_detail->is_notification) {
                    SendPushNotification::dispatch($user_detail['device_token'], $user_detail['device_type'], $title, $body, $type, $data);
                }
            }
            $instructor = Contact::find($contact_id);
            if ($instructor) {
                $booking_processes = BookingProcesses::find($booking_process_id);
                $booking_course_details = BookingProcessCourseDetails::where('booking_process_id', $booking_process_id)->first();
                $email_data['booking_number'] = $booking_processes->booking_number;
                $email_data['meeting_point'] = $booking_course_details->meeting_point;
                $email_data['start_date'] = $booking_course_details->start_date;
                $email_data['start_time'] = $booking_course_details->start_time;

                $email_data['end_date'] = $booking_course_details->end_date;
                $email_data['end_time'] = $booking_course_details->end_time;
                $email_data['salutation'] = $instructor->salutation;
                $email_data['first_name'] = $instructor->first_name;
                $email_data['last_name'] = $instructor->last_name;
                $email_data['course_name'] = $course->name;

                // $instructor->notify(new BookingConfirmInstructor($email_data, $course_name, $booking_processes->booking_number));
                $instructor->notify((new BookingConfirmInstructor($email_data, $course_name, $booking_processes->booking_number))->locale($instructor->language_locale));
            }
            return 1;
        } catch (\Exception $e) {
            Log::info("Exception accured in booking id: " . $booking_process_id . " for send updates for instructor");
        }
    }

    public function callObonoApiTest()
    {
        $belegUuid = $this->generateReceiptObonoUuid(); // Generate V4 Uuid

        $total_data = [
            "total_amount" => 15,
            "total_discount" => 0,
            "total_net_amount" => 15,
            "vat_excluded_amount" => 10,
            "payee_name" => "Test Customer",
        ];

        $payment_number = 'PMT00121212';
        $invoice_numbers = ['INV12121212', 'INV13131331'];

        // $addReceipt=$this->addReceiptCashRegisterToObono($belegUuid, '', '', ''); //Add Receipt To Cash Register
        $addReceipt = $this->addReceiptCashRegisterToObono($belegUuid, $payment_number, $invoice_numbers, $total_data); //Add Receipt To Cash Register
        $getReceiptDetail = $this->getSingleReceiptsToObono('', $belegUuid); // Get Receipt Detail

        return $getReceiptDetail;

        //Login API
        $authData = $this->authToObono();
        $data = json_encode($authData);
        $data1 = json_decode($data, true);
        $status = $data1['status'];
        $accessToken = $data1['data']['accessToken'];
        $registrierkasseUuid = $data1['data']['registrierkasseUuid'];
        //echo $accessToken;
        //add receipts to obono

        //get receipts from obono
        if ($status) {
            $getReceipts = $this->getReceiptsToObono($accessToken, $registrierkasseUuid);
            //$data=json_encode($getReceipts);
            return $getReceipts;
        }

        //var_dump($result);
        // echo $data['data']['accessToken'];

        // $host = 'localhost';
        // $port = '9090';
        // // $plugin = 'demo.obono.at/api/v1/registrierkassen';
        // $plugin = 'demo.obono.at/api/v1';
        // $secret = 'Basic dGVzdDExMUB5b3BtYWlsLmNvbTpRS1ppZ251dHMyMDIw';
        // $useSSL = true;
        // // $params  = array();
        // // $client;
        // // $bcastRoles = array();
        // $useBasicAuth = false;
        // $basicUser = 'test111@yopmail.com';
        // $basicPwd = 'QKZignuts2020';

        // // $this->client = new Client();
        // $client = new \GuzzleHttp\Client();
        // $base = ($useSSL) ? "https" : "http";
        // // $url = 'https://demo.obono.at/api/v1/auth';
        // // $endpoint = '/23a6a93b-dbac-4a1c-8f86-9df241a44a93/belege?format=export&order=asc';
        // $endpoint = '/auth';
        // $url = $base . "://" .$plugin.$endpoint;
        // // dd($url);

        // if ($useBasicAuth)
        //     $auth = 'Basic ' . base64_encode($basicUser . ':' . $basicPwd);
        // else
        //     $auth = $secret;

        // $headers = array(
        //         'Accept' => 'application/json',
        //         'Authorization' => $auth,
        //         //'Authorization' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJya2lkcyI6WyIyM2E2YTkzYi1kYmFjLTRhMWMtOGY4Ni05ZGYyNDFhNDRhOTMiXSwiZGVtbyI6MSwiZXhwIjoxNTg1MjIzODU3LCJzdWIiOiI1ZTY5Y2RkY2JhZmE0MmUyYzM5YjI4NjYifQ.EDPXVxF7MXuOqtLQCRRb8HZCk0Nof7AdgjZ1YnIuQ1I',
        //          'Content-Type'=>'application/json'
        //     );
        // try {
        //     $result = $client->request('get', $url, compact('headers'));
        // } catch (\Exception $e) {
        //     return  ['status'=>false, 'data'=>['message'=>$e->getMessage()]];
        // }

        // // $response = $request->getBody()->getContents();
        // if ($result->getStatusCode() == 200 || $result->getStatusCode() == 201) {
        //     return array('status'=>true, 'data'=>json_decode($result->getBody()));
        // }
        // return array('status'=>false, 'data'=>json_decode($result->getBody()));
    }

    /**
     *
     * Obono cash register api functions
     *
     **/

    //Obono Rest API configuration
    public function obonoAPIConfig()
    {
        # Create the Obono Rest api object
        $api = new ObonoRestApi;

        # Set the required config parameters
        //$api->secret = '';
        //$api->host = '';
        //$api->port = '';

        return $api;
    }

    //Authentication Login to Obono and get token and registerkesseid
    public function authToObono()
    {
        if (Cache::has('obonoApiResult')) {
            return Cache::get('obonoApiResult');
        } else {
            $api = $this->obonoAPIConfig();
            $result = $api->obonoAuth();
            $expire_at = now()->addMinutes(15);
            Cache::put('obonoApiResult', $result, $expire_at);
            return $result;
        }
    }

    //Generate Receipt Uuid For Obono Cash Register
    public function generateReceiptObonoUuid()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    //get Receipts from obono api
    public function getReceiptsToObono($accessToken, $registrierkasseUuid)
    {
        $api = $this->obonoAPIConfig();
        //$registrierkasseUuid='23a6a93b-dbac-4a1c-8f86-9df241a44a93';
        $result = $api->getReceipts($accessToken, $registrierkasseUuid);
        return $result;
    }

    //Store Receipt to cash register using generate document/beleg Uuid
    public function addReceiptCashRegisterToObono($belegUuid, $payment_number, $invoice_numbers, $total_data)
    {

        //Get Access Token and Registerkasse Uuid Using Obono Auth Api
        $authData = $this->authToObono();
        $data = json_encode($authData);
        $data1 = json_decode($data, true);
        $status = $data1['status'];
        $accessToken = $data1['data']['accessToken'];
        $registrierkasseUuid = $data1['data']['registrierkasseUuid'];
        //$belegUuid=$this->generateReceiptObonoUuid();
        //$belegUuid='ba016943-ef5f-43c4-a7d1-d8dccc7b93e1';
        //return $belegUuid;

        $total_invoices = implode(", ", $invoice_numbers);
        /* print_r($total_data['total_net_amount']);
        print_r($total_invoices);
        print_r(count($invoice_numbers)); */
        //Post Data
        $postan = [
            (object) [
                'Bezeichnung' => 'Payment Number: ' . $payment_number, //Description Field For Ex. - Payment Number,Invoice Number Etc..
                'Satz' => 'NORMAL', //Reference to Tax Rate For Example NORMAL(20%), ERMAESSIGT1(10%), ERMAESSIGT2(13%), BESONDERS(19%), NULL(0%)
                'Menge' => 1, //Amount of items being sold (Quantity)
                'BruttoBetrag' => (int)ceil($total_data['total_net_amount'] * 100), //Total sum including tax in cents
                'NettoBetrag' => (int)ceil($total_data['vat_excluded_amount'] * 100), //Total sum without tax in cents
                //'Externer-Beleg-Belegkreis'=> $payment_number, //Accounting Area or Accounting Code of your receipts
                //'Externer-Beleg-Bezeichnung'=> $total_invoices, //Receipt identification, for instance the receipt number
                //'Externer-Beleg-Referenz'=> $total_invoices //A Reference to the receipt, like an URL
            ]
        ];

        //Payment Data
        $zahlungen = [
            (object) [
                //'Bezeichnung'=> 'Payment Number: '.$payment_number.' has this invoices '.$total_invoices, //Description Field For Ex. - Payment Number,Invoice Number Etc..
                'Bezeichnung' => 'Payment Number: ' . $payment_number, //Description Field For Ex. - Payment Number,Invoice Number Etc..
                'Betrag' => (int)ceil($total_data['total_net_amount'] * 100), //Total Amount
                'Referenz' => 'Invoice No: ' . $total_invoices //A reference to a payment, e.g. Transaction Number of credit card payment
            ]
        ];

        $rabatte = array();

        //Obono Api Data For Add Receipt to cash register

        $data = [
            'Posten' => $postan, //Post data
            'Zahlungen' => $zahlungen, //Payment data
            //    'Rabatte' => $total_data['total_discount'], //Discount Data
            'Rabatte' => $rabatte, //Discount Data
            'Unternehmen-Name' => 'Element3', //Company name
            'Unternehmen-ID' => 'Company ID Test', //Company Id
            'Unternehmen-ID-Typ' => 'steuernummer', //May be 'uid' (vat registration number) or 'steuernummer' (tax account number) for the receipt
            'Unternehmen-Adresse1' => 'Klostergasse 8, 6370 Kitzbühel', //Company Address 1
            'Unternehmen-Adresse2' => '', //Company Address 2
            'Unternehmen-PLZ' => '', //Company zip code
            'Unternehmen-Ort' => '    ', //Company city code
            'Unternehmen-Kopfzeile' => 'Element3', //Head line for the receipt
            'Unternehmen-Fusszeile' => 'Thanks You For Your Business!', //Footer line for the receipt
            'Notizen' => [], //Notes For Receipts
            'Training' => true, //Marks if a receipt is created as a training artefact, which has no fiscal relevance
            'Storno' => false, //Marks if a receipt is an annulation of another receipt
            'Storno-Beleg-UUID' => '', //UUID of the receipt being annulated
            'Storno-Text' => '', //Rationale for the annulation
            'Kunde' => $total_data['payee_name'], //Customer Data
            'Externer-Beleg-Belegkreis' => $payment_number, //External receipt identification
            'Externer-Beleg-Bezeichnung' => "Payee " . $total_data['payee_name'] . " has payed this invoices amount " . $total_invoices, //External receipt identifyer
            'Externer-Beleg-Referenz' => $total_invoices //External receipt reference
        ];

        $api = $this->obonoAPIConfig(); //Obono API Configuration Function
        $result = $api->addReceiptCashRegister($accessToken, $registrierkasseUuid, $belegUuid, $data); //API call for add receipt to Obono cash register
        return $result;
    }

    //get single receipt detail using documentUuid
    public function getSingleReceiptsToObono($accessToken, $belegUuid)
    {
        $api = $this->obonoAPIConfig();
        //$registrierkasseUuid='23a6a93b-dbac-4a1c-8f86-9df241a44a93';
        $result = $api->getSingleReceiptDetail($accessToken, $belegUuid);
        return $result;
    }

    /**
     * For check booking criteria are valid or not
     * Date : 05-08-2020
     */
    public function checkCustomerValidForCourse($course_id, $days, $hours, $minutes, $start_date, $end_date)
    {
        $course = Course::find($course_id);

        if (!$course) return false;

        $startDate = date('Y-m-d', strtotime($start_date));
        $endDate = date('Y-m-d', strtotime($end_date));

        $startTime = date('H:i', strtotime($start_date));
        $endTime = date('H:i', strtotime($end_date));
        /**If restricted_start_date and restricted_end_date exist */
        if ($course->restricted_start_date && $course->restricted_end_date) {
            if ((($course->restricted_start_date >= $startDate) && ($course->restricted_start_date <= $endDate)) ||
                (($course->restricted_end_date >= $startDate) && ($course->restricted_end_date <= $endDate))
            ) {
                $result = false;
                return $result;
            } else {
                $result = true;
            }
            /**If only restricted_no_of_days exist  */
        } elseif ($course->restricted_no_of_days) {
            if (in_array((int)$days, $course->restricted_no_of_days)) {
                $result = false;
                return $result;
            } else {
                $result = true;
            }
        } else {
            $result = true;
        }

        /**If restricted_start_time and restricted_end_time exist */
        if ($course->restricted_start_time && $course->restricted_end_time) {
            if ((($course->restricted_start_time >= $startTime) && ($course->restricted_start_time <= $endTime)) ||
                (($course->restricted_end_time >= $startTime) && ($course->restricted_end_time <= $endTime))
            ) {
                $result = false;
                return $result;
            } else {
                $result = true;
            }
            /**If only restricted_no_of_hours exist and minutes only valid zero */
        } elseif ($course->restricted_no_of_hours) {
            if (in_array((int)$hours, $course->restricted_no_of_hours) && $minutes == 0) {
                $result = false;
            } else {
                $result = true;
            }
        } else {
            $result = true;
        }
        return $result;
    }

    /**Export obono invoice */
    public function exportReceiptBybelegUuidToObono()
    {
        $api = $this->obonoAPIConfig(); //Obono API Configuration Function
        //Get Access Token and Registerkasse Uuid Using Obono Auth Api
        $authData = $this->authToObono();
        $data = json_encode($authData);
        $data1 = json_decode($data, true);
        $accessToken = $data1['data']['accessToken'];

        if (isset($_GET['beleg_uuid']) && (isset($_GET['is_pdf_export']) && $_GET['is_pdf_export'] != 0)) {
            /**Pdf formate export obono invoice */
            $result = $api->exportPdfReceiptBybelegUuid($accessToken, $_GET['beleg_uuid']);
        } elseif ($_GET['beleg_uuid']) {
            /**Thermal formate export obono invoice */
            $result = $api->exportThermalPrintBybelegUuid($accessToken, $_GET['beleg_uuid']);
        }
        return $result;
    }
    /**End */

    /*Function for update booking process invoice */
    public function generatePdf($id)
    {
        $booking_processes = BookingProcesses::find($id);

        if (!$booking_processes) {
            return $this->sendResponse(false, __('strings.booking_process_not_found'));
        }

        $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $id)
            ->get()
            /* ->groupBy('customer_id') */
            ->toArray();

        $pdf_data['booking_no'] = $booking_processes->booking_number;
        $i = 0;
        foreach ($booking_processes_payment_details as $payment_detail) {
            // dd($payment_detail);

            //foreach ($booking_processes_customer_details as $key => $customer_detail) {
            $contact_data = Contact::with('address.country_detail:id,name,code')->find($payment_detail['customer_id']);
            /**If sub child exist */
            if (isset($payment_detail['sub_child_id']) && $payment_detail['sub_child_id']) {
                $contact_data = SubChildContact::find($payment_detail['sub_child_id']);
                $pdf_data['customer'][$i]['customer_name'] = $contact_data->first_name . " " . $contact_data->last_name;
            } else {
                $pdf_data['customer'][$i]['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;
            }

            $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail['payi_id']);

            if (!$contact) {
                continue;
            }

            $course = DB::table('booking_process_course_details')->join('courses as c', 'c.id', '=', 'booking_process_course_details.course_id')
                ->where('booking_process_course_details.booking_process_id', $id)
                ->first();

            $contact_address = ContactAddress::with('country_detail')->where('contact_id', $payment_detail['payi_id'])->first();

            ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . ($contact_address->country_detail ? $contact_address->country_detail->name : '') . "." : $address = "";

            $pdf_data['customer'][$i]['payi_id'] = $payment_detail['payi_id'];
            $pdf_data['customer'][$i]['payi_name'] = $contact->salutation . "" . $contact->first_name . " " . $contact->last_name;
            $pdf_data['customer'][$i]['payi_address'] = $address;
            $pdf_data['customer'][$i]['payi_contact_no'] = $contact->mobile1;
            $pdf_data['customer'][$i]['payi_email'] = $contact->email;
            $pdf_data['customer'][$i]['no_of_days'] = $payment_detail['no_of_days'];
            $pdf_data['customer'][$i]['refund_payment'] = $payment_detail['refund_payment'];
            /* $pdf_data['customer'][$i]['StartDate_Time'] = $customer_detail->StartDate_Time;
            $pdf_data['customer'][$i]['EndDate_Time'] = $customer_detail->EndDate_Time; */
            $pdf_data['customer'][$i]['total_price'] = $payment_detail['total_price'];
            $pdf_data['customer'][$i]['extra_participant'] = $payment_detail['extra_participant'];
            $pdf_data['customer'][$i]['discount'] = $payment_detail['discount'];
            /* if($payment_detail['voucher']) {
                if($payment_detail->voucher->amount_type === 'P') {
                    $pdf_data['customer'][$i]['voucher'] = $payment_detail->voucher->amount.' %';
                } else {
                    $pdf_data['customer'][$i]['voucher'] = $payment_detail->voucher->amount.' €';
                }
            } */
            $pdf_data['customer'][$i]['net_price'] = $payment_detail['net_price'];
            $pdf_data['customer'][$i]['vat_percentage'] = $payment_detail['vat_percentage'];
            $pdf_data['customer'][$i]['vat_amount'] = $payment_detail['vat_amount'];
            $pdf_data['customer'][$i]['vat_excluded_amount'] = $payment_detail['vat_excluded_amount'];
            $pdf_data['customer'][$i]['invoice_number'] = $payment_detail['invoice_number'];
            $pdf_data['customer'][$i]['invoice_date'] = $payment_detail['created_at'];

            $pdf_data['customer'][$i]['lunch_vat_amount'] = $payment_detail['lunch_vat_amount'];
            $pdf_data['customer'][$i]['lunch_vat_excluded_amount'] = $payment_detail['lunch_vat_excluded_amount'];
            $pdf_data['customer'][$i]['is_include_lunch'] = $payment_detail['is_include_lunch'];
            $pdf_data['customer'][$i]['include_lunch_price'] = $payment_detail['include_lunch_price'];
            $pdf_data['customer'][$i]['lunch_vat_percentage'] = $payment_detail['lunch_vat_percentage'];
            $pdf_data['customer'][$i]['payment_status'] = $payment_detail['status'];

            /**Add settelement amount details in invoice */
            $pdf_data['customer'][$i]['settlement_amount'] = $payment_detail['settlement_amount'] ? $payment_detail['settlement_amount'] : null;
            $pdf_data['customer'][$i]['settlement_description'] = $payment_detail['settlement_description'];
            /**End */
            $pdf_data['customer'][$i]['outstanding_amount'] = $payment_detail['outstanding_amount'];

            $pdf_data['customer'][$i]['is_reverse_charge'] = $payment_detail['is_reverse_charge'];
            $pdf_data['customer'][$i]['course_name'] = ($course ? $course->name : null);

            $pdf = PDF::loadView('bookingProcess.customer_invoice', $pdf_data);
            $url = 'Invoice/' . $contact->first_name . '_invoice' . mt_rand(1000000000, time()) . '.pdf';
            Storage::disk('s3')->put($url, $pdf->output());
            $url = Storage::disk('s3')->url($url);

            $booking_processes_payment_details = BookingProcessPaymentDetails::where('id', $payment_detail['id']);

            $update_data['invoice_link'] = $url;
            $update = $booking_processes_payment_details->update($update_data);
            $i++;
        }

        return;
        // });
        // $url = $this->uploadFile('contacts',"test",$pdf->output(),"pdf");
        // dd($url);


        /* return $pdf->download('CustomerInvoice.pdf');

        //dd($booking_processes_payment_details);

        $i = 0;
        foreach ($booking_processes_payment_details as $key => $payment_detail) {
            $contact_data = Contact::with('address.country_detail:id,name,code')->find($payment_detail['customer_id']);
            $pdf_data['payi'][$i]['customer_name'] = $contact_data->salutation."".$contact_data->first_name." ".$contact_data->last_name;

            $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail['payi_id']);
            $pdf_data['payi'][$i]['payi_id'] = $payment_detail['payi_id'];
            $pdf_data['payi'][$i]['payi_name'] = $contact->salutation."".$contact->first_name." ".$contact->last_name;
            $pdf_data['payi'][$i]['payi_address'] = "dd";
            $pdf_data['payi'][$i]['payi_contact_no'] = $contact->mobile1;
            $pdf_data['payi'][$i]['payi_email'] = $contact->email;
            $pdf_data['payi'][$i]['no_of_days'] = $payment_detail['no_of_days'];
            $pdf_data['payi'][$i]['refund_payment'] = $payment_detail['refund_payment'];
            $pdf_data['payi'][$i]['total_price'] = $payment_detail['total_price'];
            $pdf_data['payi'][$i]['extra_participant'] = $payment_detail['extra_participant'];
            $pdf_data['payi'][$i]['discount'] = $payment_detail['discount'];
            $pdf_data['payi'][$i]['net_price'] = $payment_detail['net_price'];
            $pdf_data['payi'][$i]['vat_percentage'] = $payment_detail['vat_percentage'];
            $pdf_data['payi'][$i]['vat_amount'] = $payment_detail['vat_amount'];
            $pdf_data['payi'][$i]['vat_excluded_amount'] = $payment_detail['vat_excluded_amount'];
            $pdf_data['payi'][$i]['invoice_number'] = $payment_detail['invoice_number'];
            $pdf_data['payi'][$i]['invoice_date'] = $payment_detail['created_at'];

            $i++;
        }

        $pdf = PDF::loadView('bookingProcess.invoice', $pdf_data);

        return $pdf->download('invoice.pdf');
        */
        //return $this->sendResponse(true,__('strings.booking_process_created_success'));
    }

    /**Function for send admin alerts when user is added threw CRM */
    function sendAdminAlert($alert_details)
    {
        /**Get admins emails without logged in admin */
        $roles = ['13', '14']; //13 : Admin, 14: Sub admin
        $admin_emails = User::whereIn('role', $roles)
            ->where('id', '!=', auth()->user()->id)
            ->pluck('email');

        /**Send alert email to admins */
        foreach ($admin_emails as $email) {
            $user_detail = User::where('email', $email)->first();
            // $user_detail->notify(new AdminAlert($alert_details));
            $user_detail->notify((new AdminAlert($alert_details))->locale($user_detail->language_locale));
        }
    }

    /**Get vat value */
    public function getVatValue()
    {
        $vat =  SequenceMaster::where('code', 'VAT')->first();
        if ($vat) {
            return $vat->sequence;
        }
        return 20;
    }

    /**Check instructor block available */
    public function checkInstructorBlockExist($star_date_time, $start_time, $end_time, $instructor_id)
    {
        InstructorBlockMap::where('instructor_id', $instructor_id)
            ->where('start_date', $star_date_time)
            ->where(function ($q) use ($start_time, $end_time) {
                $q->where('start_time', '<=', $start_time);
                $q->OrWhere('start_time', '<=', $end_time);
            })
            ->where(function ($q) use ($start_time, $end_time) {
                $q->where('end_time', '>=', $start_time);
                $q->OrWhere('end_time', '>=', $end_time);
            })
            ->update(['is_release' => true]);

        return 1;
    }

    /**Generate generate invoice */
    public function generateConsolidatedInvoice($id, $invoice_numbers)
    {
        $cancelBookingDetails = ConsolidatedInvoice::find($id);

        $pdf_data['name'] = $cancelBookingDetails['name'];
        $pdf_data['address'] = $cancelBookingDetails['address'];
        $pdf_data['total_amount'] = $cancelBookingDetails['total_amount'];
        $pdf_data['grant_amount'] = $cancelBookingDetails['grant_amount'];
        $pdf_data['emails'] = implode(', ', $cancelBookingDetails['emails']);

        $pdf_data['invoice_numbers'] = $invoice_numbers;

        $pdf_data['vat_percentage'] = $cancelBookingDetails['vat_percentage'];
        $pdf_data['vat_amount'] = $cancelBookingDetails['vat_amount'];
        $pdf_data['vat_excluded_amount'] = $cancelBookingDetails['vat_excluded_amount'];
        $pdf_data['invoice_date'] = $cancelBookingDetails['created_at'];
        $pdf_data['settlement_amount'] = $cancelBookingDetails['settlement_amount'];
        $pdf_data['settlement_description'] = $cancelBookingDetails['settlement_description'];
        $pdf_data['is_reverse_charge'] = $cancelBookingDetails['is_reverse_charge'];

        /**Load consolidate product details */
        $product_details = ConsolidatedInvoiceProduct::where('consolidated_invoice_id', $id)->get();
        if ($product_details) {
            $pdf_data['product_details'] = $product_details;
        }
        /**End */
        $pdf = PDF::loadView('bookingProcess.cansolidated_invoice', $pdf_data);

        $url = 'Invoice/' . $pdf_data['name'] . '_consolidated_invoice' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        $cancelBookingDetails->consolidated_receipt = $url;
        $cancelBookingDetails->save();

        $data = [
            "name" => $pdf_data['name'],
            "pdf" => $pdf
        ];
        return $data;
    }

    /**Create instructor user */
    public function createInstructorUser($contact)
    {
        $input['email'] = $contact->email;
        $password = Str::random(6);
        $input['password'] = Hash::make($password);
        $input['contact_id'] = $contact->id;
        $input['is_app_user'] = 1;
        $input['is_verified'] = 1;
        $input['email_token'] = '';
        $input['device_token'] = '';
        $input['device_type'] = '';
        $name = ($contact->salutation ?: '') . ' ' . ($contact->first_name ?: '') . ' ' . ($contact->last_name ?: '');
        $input['name'] =  $name;
        $user = User::create($input);
        $user->notify(new SendPassword($password));
        return 1;
    }

    /**Save error exception in database table */
    public function saveException($exception, $header, $ip)
    {
        DB::beginTransaction();
        try {
            /**Input data */
            $input = [
                'message' => $exception->getMessage(),
                'stack_trace' => json_encode($exception->getTrace()),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'header_info' => json_encode($header),
                'ip' => $ip, 'created_by' => auth()->user()->id
            ];
            $ext = ExceptionManagement::create($input);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
        }

        if (config('app.debug') == false) {
            $error = "Error #" . $ext->id;
        } else {
            $error = $exception->getMessage();
        }
        throw new \Exception($error);
        // return response()->json(['error' => $error], 555);
    }

    /**Check instructor available with season schedular */
    public function getSeasonSchedularBaseIds($contact_ids, $dates)
    {
        $main_ids = array();

        foreach ($dates as $date_data) {
            $start = explode(" ", $date_data['StartDate_Time']);
            $end = explode(" ", $date_data['EndDate_Time']);

            $strt_date = $start[0];
            $end_date = $end[0];
            $strt_time = $start[1];
            $end_time = $end[1];

            foreach ($contact_ids as $id) {
                $range_exist = SeasonSchedular::where('contact_id', $id)->count();
                if ($range_exist) {
                    $date_ids = SeasonSchedular::where('contact_id', $id)
                        ->where(function ($q) use ($strt_date, $end_date) {
                            $q->where(function ($query) use ($strt_date) {
                                $query->where('start_date', '<=', $strt_date);
                                $query->where('end_date', '>=', $strt_date);
                            });
                            $q->orWhere(function ($query) use ($end_date) {
                                $query->where('start_date', '<=', $end_date);
                                $query->where('end_date', '>=', $end_date);
                            });
                        })
                        ->pluck('id');

                    if (count($date_ids)) {
                        $time_exist = SeasonSchedular::
                            // where('contact_id', $id)
                            whereIn('id', $date_ids)
                            ->where(function ($q) use ($strt_time, $end_time) {
                                $q->where(function ($query) use ($strt_time) {
                                    $query->where('start_time', '<=', $strt_time);
                                    $query->where('end_time', '>=', $strt_time);
                                });
                                $q->orWhere(function ($query) use ($end_time) {
                                    $query->where('start_time', '<=', $end_time);
                                    $query->where('end_time', '>=', $end_time);
                                });
                                $q->orWhere(function ($query) use ($strt_time, $end_time) {
                                    $query->where('start_time', '>=', $strt_time);
                                    $query->where('start_time', '<=', $end_time);
                                });
                                $q->orWhere(function ($query) use ($strt_time, $end_time) {
                                    $query->where('end_time', '<=', $strt_time);
                                    $query->where('end_time', '>=', $end_time);
                                });
                            })
                            ->count();
                        // dd($time_exist);
                        if ($time_exist) {
                            $main_ids[] = $id;
                            Log::info("id=" . $id);
                            Log::info("data=" . $strt_date . " " . $end_date . " " . $strt_time . " " . $end_time);
                        }
                    }
                } else {
                    $main_ids[] = $id;
                }
            }
        }
        return $main_ids;
    }

    public function getLunchEndTime($lunch_hour, $start_time)
    {
        $ex = explode('.', $lunch_hour);
        $hours = $ex[0] ?? 0;
        if (isset($ex[1]) && $ex[1] == 5) {
            $minutes = 30;
        } else {
            $minutes = 00;
        }
        $minutes = (int)$hours * 60 + (int)$minutes;
        $str = "+" . $minutes . " minutes";
        $end_time = date("H:i:s", strtotime($str, strtotime($start_time)));
        return $end_time;
    }
}
