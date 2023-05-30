<?php

namespace App\Http\Controllers\API;

use DB;
use PDF;
use Mail;
use Excel;
use App\User;
use DateTime;
use Carbon\Carbon;
use App\Models\Office;
use App\Models\Contact;
use App\Jobs\UpdateChatRoom;
use App\Models\ContactLeave;
use App\Models\Notification;
use Illuminate\Http\Request;
use App\Models\CustomerGroup;
use App\Models\ContactAddress;
use App\Models\ContactAllergy;
use App\Models\Courses\Course;
use App\Models\SequenceMaster;
use App\Models\ContactLanguage;
use App\Models\Finance\Voucher;
use App\Models\Feedback\Feedback;
use App\Jobs\SendPushNotification;
use App\Http\Controllers\Functions;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Courses\CourseDetail;
use App\Notifications\UpdateBooking;
use App\Exports\BookingProcessExport;
use App\Notifications\BookingConfirm;
//use Illuminate\Support\Facades\Notification;
use App\Exports\CustomerInvoiceExport;
use Illuminate\Support\Facades\Storage;
use App\Models\SubChild\SubChildContact;
use App\Models\BookingProcess\BookingPayment;
use App\Notifications\DraftPayedNotification;
use App\Models\BookingProcess\BookingEstimate;
use App\Models\BookingProcess\BookingProcesses;
use App\Models\BookingProcess\CancelledBooking;
use App\Notifications\BookingConfirmInstructor;
use App\Models\BookingProcess\ConsolidatedInvoice;
use App\Models\BookingProcess\BookingProcessSource;
use App\Models\BookingProcess\InvoicePaymentHistory;
use App\Models\InstructorActivity\InstructorActivity;
use App\Models\BookingProcess\BookingInstructorDetailMap;
use App\Models\BookingProcess\BookingProcessCourseDetails;
use App\Models\BookingProcess\BookingProcessPaymentDetails;
use App\Models\BookingProcess\BookingParticipantsAttendance;
use App\Models\BookingProcess\BookingProcessCustomerDetails;
use App\Models\BookingProcess\BookingProcessLanguageDetails;
use App\Models\InstructorActivity\InstructorActivityComment;
use App\Models\BookingProcess\BookingProcessExtraParticipant;
use App\Models\BookingProcess\BookingProcessInstructorDetails;
use App\Models\BookingProcess\BookingProcessRequestInstructor;
use App\Models\InstructorActivity\InstructorActivityTimesheet;

class BookingProcessController extends Controller
{
    use Functions;

    /* API for Creating Booking Process */
    public function createBookingProcess(Request $request)
    {
        if ($request->is_draft == 0) {
            $v = $this->checkValidation($request);
        } else {
            $v = $this->checkValidationDraft($request);
        }

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input['created_by'] = auth()->user()->id;
        $booking_number = SequenceMaster::where('code', 'BN')->first();

        if ($booking_number) {
            $input['booking_number'] = $booking_number->sequence;
            // $input['payi_id'] = $request->payi_id;
            $input['is_draft'] = $request->is_draft;
            $update_data['sequence'] = $input['booking_number'] + 1;
            $input['booking_number'] = "EL" . date("m") . "" . date("Y") . $booking_number->sequence;
            $booking_number->update(['sequence' => $booking_number->sequence + 1]);
        }

        $booking_processes = BookingProcesses::create($input);

        $nine_digit_random_number = mt_rand(100000000, 999999999);
        if ($booking_processes) {
            $update_data['QR_nine_digit_random_number'] = $nine_digit_random_number;
            $booking_processes->update(['QR_number' => $update_data['QR_nine_digit_random_number']]);
        }

        $course_type = 'Private';
        $course_detail = null;
        if ($request->course_detail) {
            $course_detail_inputs = $request->course_detail;
            $course_detail_inputs['created_by'] = $input['created_by'];
            $course_detail_inputs['booking_process_id'] = $booking_processes->id;
            $course_detail = BookingProcessCourseDetails::create($course_detail_inputs);
            $course_type = $request->course_detail['course_type'];
        }

        //This code commented for valid only customer id not group id
        //if($course_type == 'Private') {
        if ($request->customer_detail) {
            $i = 0;
            $tempStartDate = '';
            $tempEndDate = '';
            $is_include_lunch_hour = false; //For check include lunch hour

            $add_customer_detail1 = array();
            foreach ($request->customer_detail as $key => $customer_detail) {
                $customer_detail_inputs = $customer_detail;
                $tempStartDate1 = $customer_detail_inputs['StartDate_Time'];
                $tempEndDate1 = $customer_detail_inputs['EndDate_Time'];
                /* $customer_detail_inputs['created_by'] = $input['created_by'];
                $customer_detail_inputs['booking_process_id'] = $booking_processes->id; */
                $add_customer_detail1[$i]['customer_id'] = $customer_detail_inputs['customer_id'];

                if (isset($customer_detail_inputs['sub_child_id'])) {
                    $add_customer_detail1[$i]['sub_child_id'] = $customer_detail_inputs['sub_child_id'];
                }

                $add_customer_detail1[$i]['accommodation'] = $customer_detail_inputs['accommodation'];
                $add_customer_detail1[$i]['accommodation_other'] = $customer_detail_inputs['accommodation_other'];
                $add_customer_detail1[$i]['payi_id'] = $customer_detail_inputs['payi_id'];
                $add_customer_detail1[$i]['course_detail_id'] = $customer_detail_inputs['course_detail_id'];

                //explode the date and time
                $tempStrt = explode(" ", $tempStartDate1);
                $tempEnd = explode(" ", $tempEndDate1);
                //===============

                $add_customer_detail1[$i]['StartDate_Time'] = $customer_detail_inputs['StartDate_Time'];
                $add_customer_detail1[$i]['EndDate_Time'] = $customer_detail_inputs['EndDate_Time'];

                $add_customer_detail1[$i]['start_date'] = $tempStrt[0];
                $add_customer_detail1[$i]['end_date'] = $tempEnd[0];
                $add_customer_detail1[$i]['start_time'] = $tempStrt[1];
                $add_customer_detail1[$i]['end_time'] = $tempEnd[1];

                $add_customer_detail1[$i]['cal_payment_type'] = $customer_detail_inputs['cal_payment_type'];
                $add_customer_detail1[$i]['is_include_lunch'] = $customer_detail_inputs['is_include_lunch'];
                $add_customer_detail1[$i]['include_lunch_price'] = $customer_detail_inputs['include_lunch_price'];

                $add_customer_detail1[$i]['no_of_days'] = $customer_detail_inputs['no_of_days'];
                $add_customer_detail1[$i]['hours_per_day'] = $customer_detail_inputs['hours_per_day'];
                $add_customer_detail1[$i]['is_payi'] = $customer_detail_inputs['is_payi'];
                $add_customer_detail1[$i]['created_by'] = $input['created_by'];
                $add_customer_detail1[$i]['created_at'] = date("Y-m-d H:i:s");
                $add_customer_detail1[$i]['booking_process_id'] = $booking_processes->id;
                $add_customer_detail1[$i]['QR_number'] = $booking_processes->id . $customer_detail_inputs['customer_id'] . mt_rand(100000, 999999);
                if ($tempStartDate == '') {
                    $tempStartDate = $customer_detail_inputs['StartDate_Time'];
                } elseif ($customer_detail_inputs['StartDate_Time'] < $tempStartDate) {
                    $tempStartDate = $customer_detail_inputs['StartDate_Time'];
                }
                if ($tempEndDate == '') {
                    $tempEndDate = $customer_detail_inputs['EndDate_Time'];
                } elseif ($customer_detail_inputs['EndDate_Time'] > $tempStartDate) {
                    $tempEndDate = $customer_detail_inputs['EndDate_Time'];
                }
                $add_customer_detail1[$i]['activity'] = ($customer_detail_inputs['activity'] ?: null);
                $add_customer_detail1[$i]['is_include_lunch_hour'] = ($customer_detail_inputs['is_include_lunch_hour'] ?: false);

                if (isset($customer_detail_inputs['is_include_lunch_hour']) && $customer_detail_inputs['is_include_lunch_hour']) {
                    $is_include_lunch_hour = true;
                }
                //temp $customer_detail = BookingProcessCustomerDetails::create($customer_detail_inputs);
                $i++;
            }
            $add_customer_detail = BookingProcessCustomerDetails::insert($add_customer_detail1);
            $course_detail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id);
            $course_detail_update['StartDate_Time'] = $tempStartDate;
            $course_detail_update['EndDate_Time'] = $tempEndDate;

            $tempStrt1 = explode(" ", $tempStartDate);
            $tempEnd1 = explode(" ", $tempEndDate);

            $course_detail_update['start_date'] = $tempStrt1[0];
            $course_detail_update['end_date'] = $tempEnd1[0];
            $course_detail_update['start_time'] = $tempStrt1[1];
            $course_detail_update['end_time'] = $tempEnd1[1];

            /**Lunch hour related */
            $datetime1 = new DateTime($course_detail_update['StartDate_Time']);
            $datetime2 = new DateTime($course_detail_update['EndDate_Time']);

            $interval = $datetime2->diff($datetime1);
            $course_detail_update['total_days'] = $interval->format('%d') + 1; //Day -1 calucalte count so
            $course_detail_update['total_hours'] = $interval->format('%h');
            if ($is_include_lunch_hour && $course_type === 'Group') {
                $course_detail_update['lunch_hour'] = 1; //Lunch hour is fix 1 hour
            } else {
                if (isset($course_detail_inputs['lunch_hour']) && isset($course_detail_inputs['lunch_start_time'])) {
                    $lunch_hour = $course_detail_inputs['lunch_hour'];
                    $lunch_start_time = $course_detail_inputs['lunch_start_time'];
                    $lunch_end_time = $this->getLunchEndTime($lunch_hour, $lunch_start_time);
                    $course_detail_update['lunch_end_time'] = $lunch_end_time;
                    $course_detail_update['lunch_hour'] =  $course_detail_inputs['lunch_hour'];
                    $course_detail_update['lunch_start_time'] =  $course_detail_inputs['lunch_start_time'];
                }
            }
            /**End */
        //    dd($course_detail_update);
            //  $course_detail = BookingProcessCourseDetails::create($course_detail_inputs);
            $course_detail->update($course_detail_update);
        }
        //return $this->sendResponse(true,__('strings.booking_process_created_success'));

        /* } else {
            if($request->group_id) {
                $customer_detail_inputs['created_by'] = $input['created_by'];
                $customer_detail_inputs['booking_process_id'] = $booking_processes->id;
                $customer_detail_inputs['customer_id'] = $request->group_id;
                $customer_detail = BookingProcessCustomerDetails::create($customer_detail_inputs);
            }
        }   */

        /* Start New Booking Changes : booking dates selected based on instructor wise */
        if ($request->instructor_detail) {
            $instructor_detail_inputs['created_by'] = $input['created_by'];
            $instructor_detail_inputs['booking_process_id'] = $booking_processes->id;
            foreach ($request->instructor_detail as $key => $instructor_detail) {
                $contact_input_data['last_booking_date'] = date('Y-m-d');

                $contact = Contact::find($instructor_detail['contact_id']);
                $contact->update($contact_input_data);

                $instructor_detail_inputs['contact_id'] = $instructor_detail['contact_id'];
                $starttime = $instructor_detail['start_time'];
                $endtime = $instructor_detail['end_time'];
                $instructor_date = $instructor_detail['date'];
                $check_duplicate_insert = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes->id)->where('contact_id', $instructor_detail['contact_id'])->first();
                if (!$check_duplicate_insert) {
                    $check_instructor_add = BookingProcessInstructorDetails::create($instructor_detail_inputs);
                }

                $check_mpas_duplicate_insert = BookingInstructorDetailMap::where('booking_process_id', $booking_processes->id)->where('contact_id', $instructor_detail['contact_id'])
                    ->where('startdate_time', $instructor_date . " " . $starttime)
                    ->where('enddate_time', $instructor_date . " " . $endtime)
                    ->first();

                if (!$check_mpas_duplicate_insert) {
                    //Add record to instructor booking map table
                    $booking_instructor_map_inputs['contact_id'] = $instructor_detail['contact_id'];
                    $booking_instructor_map_inputs['booking_process_id'] = $booking_processes->id;
                    $booking_instructor_map_inputs['startdate_time'] = $instructor_date . " " . $starttime;
                    $booking_instructor_map_inputs['enddate_time'] = $instructor_date . " " . $endtime;

                    $booking_instructor_map_add = BookingInstructorDetailMap::create($booking_instructor_map_inputs);
                }

                //add instructor timesheet info
                if ($contact->user_detail) {
                    $course_detail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();

                    $start_date = $instructor_date;
                    $end_date = $instructor_date;
                    $start_time = $starttime;
                    $end_time = $endtime;
                    if ($start_date && $start_time && $end_time) {
                        $this->InstructorTimesheetCreate($contact->user_detail->id, $booking_processes->id, $start_date, $end_date, $start_time, $end_time);
                    }
                }

                /**For if instructor has block with booking dates then release the blocks */
                $this->checkInstructorBlockExist($instructor_date, $starttime, $endtime, $instructor_detail['contact_id']);

                // foreach ($instructor_detail['dates'] as $key => $instructor_date)
                // {
                //     $booking_instructor_map_inputs['contact_id']=$instructor_detail['contact_id'];
                //     $booking_instructor_map_inputs['booking_process_id']=$booking_processes->id;
                //     $booking_instructor_map_inputs['startdate_time']=$instructor_date." ".$starttime;
                //     $booking_instructor_map_inputs['enddate_time']=$instructor_date." ".$endtime;

                //     $booking_instructor_map_add = BookingInstructorDetailMap::create($booking_instructor_map_inputs);

                //     //add instructor timesheet info
                //     if ($contact->user_detail) {
                //         $course_detail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();

                //         $start_date=$instructor_date;
                //         $end_date=$instructor_date;
                //         $start_time = $starttime;
                //         $end_time = $endtime;
                //         $this->InstructorTimesheetCreate($contact->user_detail->id, $booking_processes->id, $start_date, $end_date, $start_time, $end_time);
                //     }

                // }
            }

            if ($check_instructor_add) {
                $instructor_ids = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes->id)->pluck('contact_id');
            }

            $instructor_data = Contact::whereIn('id', $instructor_ids)->select('salutation', 'first_name', 'last_name')->get()->toArray();

            $instructor = Contact::where('id', $instructor_ids[0])->first();
            $instructor_name = $instructor->first_name . " " . $instructor->last_name;

            if ($request->is_draft === 0) {
                if ($instructor_data) {
                    if ($request->course_detail) {
                        $course_detail_inputs = $request->course_detail;
                        $course_id = $course_detail_inputs['course_id'];
                    }
                    $total_instructor = count($instructor_data);
                    $data['course_id'] = $course_id;
                    $course = Course::find($data['course_id']);
                    $data['course_name'] = $course->name;
                    $data['booking_processes_id'] = $booking_processes->id;
                    //$data['instructor_data'] = $instructor_data;
                    $title = "Your Course have Assign New Instructor";
                    // $body = "Your Course: ".$course->name." have Assign ".$total_instructor." New Instructor";
                    // $body = "Your Course: ".$total_instructor." new instructors have been assigned to the course";
                    $body = 'Instructor ' . $instructor_name . ' has been assigned to your course.';
                    $this->setPushNotificationData($request->customer_detail, 4, $data, $title, $body, $booking_processes->id);
                }
            }
        }
        /* End New Booking Changes */

        /**For add booking request instructor details */
        if ($request->request_instructor_details) {
            $i = 0;
            foreach ($request->request_instructor_details as $instructor) {
                $request_instructor_details[$i]['created_by'] = $input['created_by'];
                $request_instructor_details[$i]['created_at'] = date("Y-m-d H:i:s");
                $request_instructor_details[$i]['booking_process_id'] = $booking_processes->id;
                $request_instructor_details[$i]['contact_id'] = $instructor;
                $i = $i + 1;
            }
            BookingProcessRequestInstructor::insert($request_instructor_details);
        }
        /**End */

        /* Old Instructor Logic */
        // if ($request->instructor_detail) {
        //     $instructor_detail_inputs['created_by'] = $input['created_by'];
        //     $instructor_detail_inputs['booking_process_id'] = $booking_processes->id;
        //     foreach ($request->instructor_detail as $key => $instructor_detail) {
        //         $contact_input_data['last_booking_date'] = date('Y-m-d');

        //         $contact = Contact::find($instructor_detail);
        //         $contact->update($contact_input_data);

        //         $instructor_detail_inputs['contact_id'] = $instructor_detail;
        //         $check_instructor_add = BookingProcessInstructorDetails::create($instructor_detail_inputs);

        //         //add instructor timesheet info
        //         if ($contact->user_detail) {
        //             $course_detail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();
        //             if ($course_detail) {
        //                 $startDateTime = explode(" ", $course_detail->StartDate_Time);
        //                 $endDateTime = explode(" ", $course_detail->EndDate_Time);
        //                 $start_date=$startDateTime[0];
        //                 $end_date=$endDateTime[0];
        //                 $start_time = $startDateTime[1];
        //                 $end_time = $endDateTime[1];
        //                 $this->InstructorTimesheetCreate($contact->user_detail->id, $booking_processes->id, $start_date, $end_date, $start_time, $end_time);
        //             }
        //         }
        //     }
        //     if ($check_instructor_add) {
        //         $instructor_ids = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes->id)->pluck('contact_id');
        //     }

        //     $instructor_data = Contact::whereIn('id', $instructor_ids)->select('salutation', 'first_name', 'last_name')->get()->toArray();

        //     $instructor = Contact::where('id', $instructor_ids[0])->first();
        //     $instructor_name = $instructor->first_name." ".$instructor->last_name;

        //     if ($request->is_draft===0) {
        //         if ($instructor_data) {
        //             if ($request->course_detail) {
        //                 $course_detail_inputs = $request->course_detail;
        //                 $course_id = $course_detail_inputs['course_id'];
        //             }
        //             $total_instructor = count($instructor_data);
        //             $data['course_id'] = $course_id;
        //             $course = Course::find($data['course_id']);
        //             $data['course_name'] = $course->name;
        //             $data['booking_processes_id'] = $booking_processes->id;
        //             //$data['instructor_data'] = $instructor_data;
        //             $title = "Your Course have Assign New Instructor";
        //             // $body = "Your Course: ".$course->name." have Assign ".$total_instructor." New Instructor";
        //             // $body = "Your Course: ".$total_instructor." new instructors have been assigned to the course";
        //             $body = 'Instructor '.$instructor_name.' has been assigned to your course.';
        //             $this->setPushNotificationData($request->customer_detail, 4, $data, $title, $body, $booking_processes->id);
        //         }
        //     }
        // }

        if ($request->language_detail) {
            $language_detail_inputs['created_by'] = $input['created_by'];
            $language_detail_inputs['booking_process_id'] = $booking_processes->id;
            foreach ($request->language_detail as $key => $language_detail) {
                $language_detail_inputs['language_id'] = $language_detail;
                $language_detail = BookingProcessLanguageDetails::create($language_detail_inputs);
            }
        }

        // if ($request->extra_participants_details) {
        //     foreach ($request->extra_participants_details as $key => $extra_participants_detail) {
        //         $extra_participants_inputs = $extra_participants_detail;
        //         $extra_participants_inputs['created_by'] = $input['created_by'];
        //         $extra_participants_inputs['booking_process_id'] = $booking_processes->id;
        //         $instructor_detail_inputs = BookingProcessExtraParticipant::create($extra_participants_inputs);
        //     }
        // }

        if ($request->additional_information) {
            $additional_information = BookingProcesses::find($booking_processes->id);
            $additional_information_input = $request->additional_information;
            $additional_information_input['updated_by'] = auth()->user()->id;
            $additional_information->update($additional_information_input);
        }

        if ($request->payment_detail) {
            foreach ($request->payment_detail as $key => $payment_detail) {
                $payment_detail_inputs = $payment_detail;
                $add_payment_detail['customer_id'] = $payment_detail_inputs['customer_id'];

                if (isset($payment_detail_inputs['sub_child_id'])) {
                    $add_payment_detail['sub_child_id'] = $payment_detail_inputs['sub_child_id'];
                }

                $add_payment_detail['created_by'] = $input['created_by'];
                $add_payment_detail['created_at'] = date("Y-m-d H:i:s");
                $add_payment_detail['payi_id'] = $payment_detail_inputs['payi_id'];
                $add_payment_detail['no_of_days'] = $payment_detail_inputs['no_of_days'];
                $add_payment_detail['hours_per_day'] = $payment_detail_inputs['hours_per_day'];
                $add_payment_detail['course_detail_id'] = $payment_detail_inputs['course_detail_id'];
                $add_payment_detail['booking_process_id'] = $booking_processes->id;
                $add_payment_detail['include_extra_price'] = $payment_detail_inputs['include_extra_price'];

                $invoice_number = SequenceMaster::where('code', 'INV')->first();

                if ($invoice_number) {
                    $input['invoice_number'] = $invoice_number->sequence;
                    $add_payment_detail['invoice_number'] = "INV" . date("m") . "" . date("Y") . $input['invoice_number'];
                    $invoice_number->update(['sequence' => $invoice_number->sequence + 1]);
                }

                $course_detail_inputs = $request->course_detail;

                $add_payment_detail['total_price'] = $payment_detail_inputs['price'];

                if ($course_detail_inputs['course_type'] === 'Private') {
                    /**Code comment for new requirement
                     * Date : 23-07-2020
                     */
                    // $extra_participant = BookingProcessExtraParticipant::where('booking_process_id', $booking_processes->id)->count();
                    $booking_course_data = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();

                    /**If extra participant available then assign charge */
                    $extra_person_charge = 0;
                    if ($booking_course_data->is_extra_participant) {
                        $extra_person_charge = $payment_detail_inputs['extra_person_charge'];
                    }
                    $add_payment_detail['extra_person_charge'] = $extra_person_charge;

                    /**If no of extra participant available */
                    $total_participant = 0;
                    if ($booking_course_data->no_of_extra_participant) {
                        $total_participant = $booking_course_data->no_of_extra_participant;
                    }
                    $add_payment_detail['extra_participant'] = $total_participant * $extra_person_charge;
                }

                $add_payment_detail['net_price'] = $payment_detail_inputs['net_price'];
                $add_payment_detail['payment_method_id'] = $payment_detail_inputs['payment_method_id'];
                $add_payment_detail['discount'] = $payment_detail_inputs['discount'];
                $add_payment_detail['price_per_day'] = $payment_detail_inputs['price_per_day'];

                $add_payment_detail['vat_percentage'] = $payment_detail_inputs['vat_percentage'];
                $add_payment_detail['vat_amount'] = $payment_detail_inputs['vat_amount'];
                $add_payment_detail['vat_excluded_amount'] = $payment_detail_inputs['vat_excluded_amount'];
                $add_payment_detail['status'] = "Pending";

                $add_payment_detail['cal_payment_type'] = $payment_detail_inputs['cal_payment_type'];
                $add_payment_detail['is_include_lunch'] = $payment_detail_inputs['is_include_lunch'];
                $add_payment_detail['include_lunch_price'] = $payment_detail_inputs['include_lunch_price'];

                $add_payment_detail['lunch_vat_amount'] = $payment_detail_inputs['lunch_vat_amount'];
                $add_payment_detail['lunch_vat_excluded_amount'] = $payment_detail_inputs['lunch_vat_excluded_amount'];
                $add_payment_detail['lunch_vat_percentage'] = $payment_detail_inputs['lunch_vat_percentage'];

                /**For settlement amount */
                $add_payment_detail['settlement_amount'] = $payment_detail_inputs['settlement_amount'];
                $add_payment_detail['settlement_description'] = $payment_detail_inputs['settlement_description'];
                /** */
                $add_payment_detail['credit_card_type'] = ($payment_detail_inputs['credit_card_type'] ?: null);

                $payment_detail = BookingProcessPaymentDetails::create($add_payment_detail);

                // if voucher is applied
                if ($payment_detail_inputs['voucher_id'] != null) {
                    $voucher = Voucher::find($payment_detail_inputs['voucher_id']);
                    if (!$voucher) {
                        return $this->sendResponse(false, __('strings.invalid_voucher'));
                    }

                    try {
                        $invoice = $voucher->apply($payment_detail->id);
                    } catch (\Exception $e) {
                        DB::rollback();
                        return $this->sendResponse(false, $e->getMessage());
                    }
                }

                // if payment is done
                $invoice_amount = $payment_detail_inputs['net_price'];
                /**For manage outstanding amount */
                BookingProcessPaymentDetails::where('id', $payment_detail->id)->update(['outstanding_amount' => $invoice_amount]);
                /** */

                if ($payment_detail_inputs['is_pay'] == 1) {
                    $payment_details = [
                        "qbon_number"               => $payment_detail_inputs['qbon_number'],
                        "contact_id"                => $payment_detail_inputs['payi_id'],
                        "payment_type"              => $payment_detail_inputs['payment_method_id'],
                        "is_office"                 => $payment_detail_inputs['is_office'],
                        "office_id"                 => $payment_detail_inputs['office_id'],
                        "amount_given_by_customer"  => $payment_detail_inputs['amount_given_by_customer'],
                        "amount_returned"           => $payment_detail_inputs['amount_returned'],
                        "total_amount"              => $payment_detail_inputs['price'],
                        "total_discount"            => $payment_detail_inputs['discount'] ?: 0,
                        "total_vat_amount"          => $payment_detail_inputs['vat_amount'],
                        "total_net_amount"          => $payment_detail_inputs['net_price'],
                        "credit_card_type"          => ($payment_detail_inputs['credit_card_type'] ?: null)
                    ];
                    $invoic_data = array();
                    $invoic_data[]['id'] = $payment_detail->id;

                    DB::beginTransaction();
                    try {
                        // call common function to create payment
                        $payment = $this->createPayment($payment_details, $invoic_data);
                        DB::commit();
                    } catch (\Exception $e) {
                        DB::rollback();
                        /**For save exception */
                        $this->saveException($e, $request->header(), $request->ip());
                    }
                }
                $i++;
            }
        }

        if ($request->is_draft === 0) {
            if ($request->course_detail) {
                $course_detail_inputs = $request->course_detail;
            }

            $data['course_id'] = $course_detail_inputs['course_id'];
            $course = Course::find($data['course_id']);
            $data['course_name'] = $course->name;
            $data['booking_processes_id'] = $booking_processes->id;
            // $title = "Add New Course";
            $title = "Enroll New Course";
            // $body = "You must have to enroll: ".$course->name."for access course";
            $body = "You must have to enroll in the: " . $course->name . " to access the course";
            // $body = "You have been aded to a new course: ".$course->name;
            $this->setPushNotificationData($request->customer_detail, 23, $data, $title, $body, $booking_processes->id);
            //Log::info("customer booking confirm");

            //for send push notification for instructor
            $instructors = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes->id)->get()->toArray();

            if ($instructors) {
                foreach ($instructors as $key => $instructor_detail) {
                    $user_detail = User::where('contact_id', $instructor_detail['contact_id'])->first();
                    if ($user_detail) {
                        $receiver_id = $instructor_detail['contact_id'];

                        $data = ['booking_processes_id' => $booking_processes->id];
                        $data = ['course_id' => $course_detail_inputs['course_id']];
                        $course = Course::find($course_detail_inputs['course_id']);
                        $course_name = $course->name;
                        $course_type = $course->type;
                        $type = 25;
                        $title = "A " . $course_type . " course " . $course_name . " has been assigned to you, kindly confirm it!";
                        $body =  "A " . $course_type . " course " . $course_name . " has been assigned to you, kindly confirm it!";

                        $notification = Notification::create(['sender_id' => auth()->user()->id, "receiver_id" => $receiver_id, "type" => $type, 'message' => $body, 'booking_process_id' => $booking_processes->id]);

                        if ($user_detail->is_notification) {
                            SendPushNotification::dispatch($user_detail['device_token'], $user_detail['device_type'], $title, $body, $type, $data);
                        }
                    }
                    $instructor = Contact::find($instructor_detail['contact_id']);
                    if ($instructor) {
                        $booking_processes = BookingProcesses::find($booking_processes->id);
                        $booking_course_details = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();
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
                }
            }

            $this->customer_booking_confirm($booking_processes->id);
        } else {
            $this->sendDraftPayedEmail($booking_processes->id);
        }
        $this->generatePdf($booking_processes->id);

        //Update chatroom for openfire
        UpdateChatRoom::dispatch(true, $booking_processes->id);

        /**Add crm user action trail */
        if ($booking_processes) {
            $action_id = $booking_processes->id; //Booking Process id
            $action_type = 'A'; //A = Add
            $module_id = 16; //module id base module table
            $module_name = "Bookings"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        if ($request->is_draft_duplicate === 1) {
            $data = [
                'booking_id' => $booking_processes->id
            ];
            return $this->sendResponse(true, __('strings.booking_process_created_success'), $data);
        } else {
            return $this->sendResponse(true, __('strings.booking_process_created_success'));
        }

        return $this->sendResponse(true, __('strings.booking_process_created_success'));
    }

    /* API for View Booking Process Invoice*/
    public function viewPdf(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $id = $request->booking_process_id;
        $booking_processes = BookingProcesses::find($id);

        if (!$booking_processes) {
            return $this->sendResponse(false, __('strings.booking_not_found'));
        }

        $course = DB::table('booking_process_course_details')->join('courses as c', 'c.id', '=', 'booking_process_course_details.course_id')
            ->where('booking_process_course_details.booking_process_id', $id)
            ->first();

        $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $id)
            ->get()
            /* ->groupBy('customer_id') */
            ->toArray();

        $pdf_data['booking_no'] = $booking_processes->booking_number;

        if ($request->customer_id) {
            $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $id)
                ->where('customer_id', $request->customer_id)
                ->with('voucher')
                ->orderBy('id', 'desc')->get();

            $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $id)
                ->where('customer_id', $request->customer_id)
                ->orderBy('id', 'desc')->get();
            //dd($booking_processes_customer_details);

            $i = 0;
            foreach ($booking_processes_payment_details as $key => $payment_detail) {
                //foreach ($booking_processes_customer_details as $key => $customer_detail) {
                $contact_data = Contact::with('address.country_detail:id,name,code')->find($request->customer_id);

                /**If sub child exist */
                if (isset($payment_detail['sub_child_id']) && $payment_detail['sub_child_id']) {
                    $contact_data = SubChildContact::find($payment_detail['sub_child_id']);
                    $pdf_data['customer'][$i]['customer_name'] = $contact_data->first_name . " " . $contact_data->last_name;
                } else {
                    $pdf_data['customer'][$i]['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;
                }

                $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail->payi_id);

                $contact_address = ContactAddress::with('country_detail')->where('contact_id', $payment_detail->payi_id)->first();

                ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . $contact_address->country_detail->name . "." : $address = "";

                $pdf_data['customer'][$i]['payi_id'] = $payment_detail->payi_id;
                $pdf_data['customer'][$i]['payi_name'] = $contact->salutation . "" . $contact->first_name . " " . $contact->last_name;
                $pdf_data['customer'][$i]['payi_address'] = $address;
                $pdf_data['customer'][$i]['payi_contact_no'] = $contact->mobile1;
                $pdf_data['customer'][$i]['payi_email'] = $contact->email;
                $pdf_data['customer'][$i]['no_of_days'] = $payment_detail->no_of_days;
                $pdf_data['customer'][$i]['refund_payment'] = $payment_detail->refund_payment;
                /* $pdf_data['customer'][$i]['StartDate_Time'] = $customer_detail->StartDate_Time;
                $pdf_data['customer'][$i]['EndDate_Time'] = $customer_detail->EndDate_Time; */
                $pdf_data['customer'][$i]['total_price'] = $payment_detail->total_price;
                $pdf_data['customer'][$i]['extra_participant'] = $payment_detail->extra_participant;
                $pdf_data['customer'][$i]['discount'] = $payment_detail->discount;
                if ($payment_detail->voucher) {
                    if ($payment_detail->voucher->amount_type === 'P') {
                        $pdf_data['customer'][$i]['voucher'] = $payment_detail->voucher->amount . ' %';
                    } else {
                        $pdf_data['customer'][$i]['voucher'] = $payment_detail->voucher->amount . ' €';
                    }
                }
                $pdf_data['customer'][$i]['net_price'] = $payment_detail->net_price;
                $pdf_data['customer'][$i]['vat_percentage'] = $payment_detail->vat_percentage;
                $pdf_data['customer'][$i]['vat_amount'] = $payment_detail->vat_amount;
                $pdf_data['customer'][$i]['vat_excluded_amount'] = $payment_detail->vat_excluded_amount;
                $pdf_data['customer'][$i]['invoice_number'] = $payment_detail->invoice_number;
                $pdf_data['customer'][$i]['invoice_date'] = $payment_detail->created_at;

                $pdf_data['customer'][$i]['lunch_vat_amount'] = $payment_detail->lunch_vat_amount;
                $pdf_data['customer'][$i]['lunch_vat_excluded_amount'] = $payment_detail->lunch_vat_excluded_amount;
                $pdf_data['customer'][$i]['is_include_lunch'] = $payment_detail->is_include_lunch;
                $pdf_data['customer'][$i]['include_lunch_price'] = $payment_detail->include_lunch_price;
                $pdf_data['customer'][$i]['lunch_vat_percentage'] = $payment_detail->lunch_vat_percentage;
                $pdf_data['customer'][$i]['payment_status'] = $payment_detail->status;
                $pdf_data['customer'][$i]['payment_method_id'] = $payment_detail->payment_method_id;

                /**Add settelement amount details in invoice */
                $pdf_data['customer'][$i]['settlement_amount'] = $payment_detail->settlement_amount ? $payment_detail->settlement_amount : null;
                /**End */
                $pdf_data['customer'][$i]['outstanding_amount'] = $payment_detail->outstanding_amount;

                $pdf_data['customer'][$i]['is_reverse_charge'] = $payment_detail->is_reverse_charge;
                $pdf_data['customer'][$i]['invoice_date'] = $payment_detail->created_at;
                $pdf_data['customer'][$i]['course_name'] = ($course ? $course->name : null);
                //}
                $i++;
            }
            $pdf = PDF::loadView('bookingProcess.customer_invoice', $pdf_data);

            /* Route::get('/test-s3-public-put', function()
            { */
            /* Storage::disk('s3')->put('Invoice/customer_invoice.pdf',$pdf->output());
            $url = Storage::disk('s3')->url('Invoice/customer_invoice.pdf');

            $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $id)
            ->where('customer_id', $request->customer_id)
            ;
            // dd($booking_processes_payment_details);

            $update_data['invoice_link'] = $url;
            $update = $booking_processes_payment_details->update($update_data); */
            // dd($booking_processes_payment_details);

            // });
            // $url = $this->uploadFile('contacts',"test",$pdf->output(),"pdf");
            // dd($url);

            return $pdf->download($pdf_data['customer'][0]['payi_name'] . 'CustomerInvoice.pdf');
        }
        //dd($booking_processes_payment_details);

        $i = 0;
        foreach ($booking_processes_payment_details as $key => $payment_detail) {
            $contact_data = Contact::with('address.country_detail:id,name,code')->find($payment_detail['customer_id']);

            /**If sub child exist */
            if (isset($payment_detail['sub_child_id']) && $payment_detail['sub_child_id']) {
                $contact_data = SubChildContact::find($payment_detail['sub_child_id']);
                $pdf_data['payi'][$i]['customer_name'] = $contact_data->first_name . " " . $contact_data->last_name;
            } else {
                $pdf_data['payi'][$i]['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;
            }

            $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail['payi_id']);

            $contact_address = ContactAddress::with('country_detail')->where('contact_id', $payment_detail['payi_id'])->first();

            ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . $contact_address->country_detail->name . "." : $address = "";

            $pdf_data['payi'][$i]['payi_id'] = $payment_detail['payi_id'];
            $pdf_data['payi'][$i]['payi_name'] = $contact->salutation . "" . $contact->first_name . " " . $contact->last_name;
            $pdf_data['payi'][$i]['course_name'] = ($course ? $course->name : null);
            $pdf_data['payi'][$i]['payment_method_id'] = $payment_detail['payment_method_id'];
            $pdf_data['payi'][$i]['payi_address'] = $address;
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

            $pdf_data['payi'][$i]['lunch_vat_amount'] = $payment_detail['lunch_vat_amount'];
            $pdf_data['payi'][$i]['lunch_vat_excluded_amount'] = $payment_detail['lunch_vat_excluded_amount'];
            $pdf_data['payi'][$i]['is_include_lunch'] = $payment_detail['is_include_lunch'];
            $pdf_data['payi'][$i]['include_lunch_price'] = $payment_detail['include_lunch_price'];
            $pdf_data['payi'][$i]['lunch_vat_percentage'] = $payment_detail['lunch_vat_percentage'];
            $pdf_data['payi'][$i]['payment_status'] = $payment_detail['status'];
            $pdf_data['payi'][$i]['outstanding_amount'] = $payment_detail['outstanding_amount'];

            /**Add settelement amount details in invoice */
            $pdf_data['payi'][$i]['settlement_amount'] = $payment_detail['settlement_amount'] ? $payment_detail['settlement_amount'] : null;
            /**End */
            $pdf_data['payi'][$i]['is_reverse_charge'] = $payment_detail['is_reverse_charge'];
            $i++;
        }

        $pdf = PDF::loadView('bookingProcess.invoice', $pdf_data);

        return $pdf->download($pdf_data['booking_no'] . 'BookingInvoice.pdf');

        //return $this->sendResponse(true,__('strings.booking_process_created_success'));
    }

    /* API for View Booking Process Invoice For Test Purpose of Design */
    public function viewPdfEmailTest(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $id = $request->booking_process_id;
        $booking_processes = BookingProcesses::find($id);

        $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $id)
            ->get()
            /* ->groupBy('customer_id') */
            ->toArray();

        $pdf_data['booking_no'] = $booking_processes->booking_number;

        $course = DB::table('booking_process_course_details')->join('courses as c', 'c.id', '=', 'booking_process_course_details.course_id')
            ->where('booking_process_course_details.booking_process_id', $id)
            ->first();

        if ($request->customer_id) {
            $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $id)
                ->where('customer_id', $request->customer_id)
                ->with('voucher')
                ->orderBy('id', 'desc')->get();

            $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $id)
                ->where('customer_id', $request->customer_id)
                ->orderBy('id', 'desc')->get();
            //dd($booking_processes_payment_details);

            $i = 0;
            foreach ($booking_processes_payment_details as $key => $payment_detail) {
                //foreach ($booking_processes_customer_details as $key => $customer_detail) {
                $contact_data = Contact::with('address.country_detail:id,name,code')->find($request->customer_id);
                $pdf_data['customer'][$i]['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;
                $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail->payi_id);

                $contact_address = ContactAddress::with('country_detail')->where('contact_id', $payment_detail->payi_id)->first();

                ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . $contact_address->country_detail->name . "." : $address = "";

                $pdf_data['customer'][$i]['payi_id'] = $payment_detail->payi_id;
                $pdf_data['customer'][$i]['payi_name'] = $contact->salutation . "" . $contact->first_name . " " . $contact->last_name;
                $pdf_data['customer'][$i]['payi_address'] = $address;
                $pdf_data['customer'][$i]['payi_contact_no'] = $contact->mobile1;
                $pdf_data['customer'][$i]['payi_email'] = $contact->email;
                $pdf_data['customer'][$i]['no_of_days'] = $payment_detail->no_of_days;
                $pdf_data['customer'][$i]['refund_payment'] = $payment_detail->refund_payment;
                /* $pdf_data['customer'][$i]['StartDate_Time'] = $customer_detail->StartDate_Time;
                $pdf_data['customer'][$i]['EndDate_Time'] = $customer_detail->EndDate_Time; */
                $pdf_data['customer'][$i]['total_price'] = $payment_detail->total_price;
                $pdf_data['customer'][$i]['extra_participant'] = $payment_detail->extra_participant;
                $pdf_data['customer'][$i]['discount'] = $payment_detail->discount;
                if ($payment_detail->voucher) {
                    if ($payment_detail->voucher->amount_type === 'P') {
                        $pdf_data['customer'][$i]['voucher'] = $payment_detail->voucher->amount . ' %';
                    } else {
                        $pdf_data['customer'][$i]['voucher'] = $payment_detail->voucher->amount . ' €';
                    }
                }
                $pdf_data['customer'][$i]['net_price'] = $payment_detail->net_price;
                $pdf_data['customer'][$i]['vat_percentage'] = $payment_detail->vat_percentage;
                $pdf_data['customer'][$i]['vat_amount'] = $payment_detail->vat_amount;
                $pdf_data['customer'][$i]['vat_excluded_amount'] = $payment_detail->vat_excluded_amount;
                $pdf_data['customer'][$i]['invoice_number'] = $payment_detail->invoice_number;
                $pdf_data['customer'][$i]['invoice_date'] = $payment_detail->created_at;

                $pdf_data['customer'][$i]['lunch_vat_amount'] = $payment_detail->lunch_vat_amount;
                $pdf_data['customer'][$i]['lunch_vat_excluded_amount'] = $payment_detail->lunch_vat_excluded_amount;
                $pdf_data['customer'][$i]['is_include_lunch'] = $payment_detail->is_include_lunch;
                $pdf_data['customer'][$i]['include_lunch_price'] = $payment_detail->include_lunch_price;
                $pdf_data['customer'][$i]['lunch_vat_percentage'] = $payment_detail->lunch_vat_percentage;
                $pdf_data['customer'][$i]['payment_status'] = $payment_detail->status;
                $pdf_data['customer'][$i]['outstanding_amount'] = $payment_detail->outstanding_amount;
                $pdf_data['customer'][$i]['is_reverse_charge'] = $payment_detail->is_reverse_charge;
                $pdf_data['customer'][$i]['course_name'] = ($course ? $course->name : null);
                //}
                $i++;
            }
            //dd($pdf_data);
            $pdf = PDF::loadView('bookingProcess.customer_invoice', $pdf_data);

            return $pdf->download('CustomerInvoice.pdf');
        }
        //dd($booking_processes_payment_details);

        $i = 0;
        foreach ($booking_processes_payment_details as $key => $payment_detail) {
            $contact_data = Contact::with('address.country_detail:id,name,code')->find($payment_detail['customer_id']);
            $pdf_data['payi'][$i]['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;

            $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail['payi_id']);

            $contact_address = ContactAddress::with('country_detail')->where('contact_id', $payment_detail['payi_id'])->first();

            ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . $contact_address->country_detail->name . "." : $address = "";

            $pdf_data['payi'][$i]['payi_id'] = $payment_detail['payi_id'];
            $pdf_data['payi'][$i]['payi_name'] = $contact->salutation . "" . $contact->first_name . " " . $contact->last_name;
            $pdf_data['payi'][$i]['course_name'] = ($course ? $course->name : null);
            $pdf_data['payi'][$i]['payi_address'] = $address;
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
            $pdf_data['payi'][$i]['lunch_vat_amount'] = $payment_detail['lunch_vat_amount'];
            $pdf_data['payi'][$i]['lunch_vat_excluded_amount'] = $payment_detail['lunch_vat_excluded_amount'];
            $pdf_data['payi'][$i]['is_include_lunch'] = $payment_detail['is_include_lunch'];
            $pdf_data['payi'][$i]['include_lunch_price'] = $payment_detail['include_lunch_price'];
            $pdf_data['payi'][$i]['lunch_vat_percentage'] = $payment_detail['lunch_vat_percentage'];

            $pdf_data['payi'][$i]['lunch_vat_amount'] = $payment_detail['lunch_vat_amount'];
            $pdf_data['payi'][$i]['lunch_vat_excluded_amount'] = $payment_detail['lunch_vat_excluded_amount'];
            $pdf_data['payi'][$i]['is_include_lunch'] = $payment_detail['is_include_lunch'];
            $pdf_data['payi'][$i]['include_lunch_price'] = $payment_detail['include_lunch_price'];
            $pdf_data['payi'][$i]['lunch_vat_percentage'] = $payment_detail['lunch_vat_percentage'];
            $pdf_data['payi'][$i]['payment_status'] = $payment_detail['status'];
            $pdf_data['payi'][$i]['settlement_amount'] = $payment_detail['settlement_amount'] ? $payment_detail['settlement_amount'] : null;
            $pdf_data['payi'][$i]['outstanding_amount'] = $payment_detail['outstanding_amount'];
            $pdf_data['payi'][$i]['is_reverse_charge'] = $payment_detail['is_reverse_charge'];
            $i++;
        }

        $pdf = PDF::loadView('bookingProcess.invoice', $pdf_data);

        return view('bookingProcess.invoice', $pdf_data);
        //return $pdf->download('invoice.pdf');

        //return $this->sendResponse(true,__('strings.booking_process_created_success'));
    }
    /* API for Make Booking Process Invoice */
    public function makeBookingProcessInvoice()
    {
        /* Mail::raw('Text to e-mail', function ($message) {
        $message->to('test@example.com');
        }); */


        $pdf_data['booking_no'] = 1212;
        $pdf_data['payi_id'] = 15;
        $pdf_data['payi_name'] = "Mr.Test";
        $pdf_data['payi_address'] = "abcsfsgf";
        $pdf_data['payi_contact_no'] = 45454545;
        $pdf_data['payi_email'] = "aaba@gmail.com";
        $pdf_data['total_price'] = 120;
        $pdf_data['extra_participant'] = 25;
        $pdf_data['discount'] = 10;
        $pdf_data['net_price'] = 100;

        //dd($pdf_data);

        /* Mail::send(['text'=>'mail'], $pdf_data,function($message) use($pdf_data){
                $pdf = PDF::loadView('bookingProcess.invoice', $pdf_data);
               // $pdf = PDF::loadView('pdf.invoice');
                $message->to('zignutstest@gmail.com','test test1')->subject('Send Mail from Laravel');

                $message->from('from@gmail.com','The Sender');
                $message->attachData($pdf->output(),'filename.pdf');

            });
        echo 'Email was sent!'; */


        $pdf = PDF::loadView('bookingProcess.invoice', $pdf_data);

        return $pdf->download('invoice.pdf');
    }



    /* API for Updating Booking Process */
    public function updateBookingProcess(Request $request, $id)
    {
        if ($request->is_draft == 0) {
            $v = $this->checkValidation($request);
        } else {
            $v = $this->checkValidationDraft($request);
        }

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_processes = BookingProcesses::find($id);
        //print_r($booking_processes);exit;

        if (!$booking_processes) {
            return $this->sendResponse(false, __('strings.booking_process_not_found'));
        }

        $update['updated_by'] = auth()->user()->id;
        //$update['payi_id'] = $request->payi_id;
        $update['is_draft'] = $request->is_draft;
        $booking_processes->update($update);

        $course_type = 'Private';
        if ($request->course_detail) {
            $course_detail = BookingProcessCourseDetails::where('booking_process_id', $id);
            $course_detail_update = $request->course_detail;


            if ($request->customer_detail && $request->payment_detail) {
                $customer_detail = $request->customer_detail;
                $payment_detail = $request->payment_detail;

                $actioncount = 0;
                for ($i = 0; $i < count($customer_detail); $i++) {
                    if ($customer_detail[$i]['is_action'] === 3) {
                        $actioncount = $actioncount + 1;
                    }
                }

                if (count($customer_detail) == 0 && count($payment_detail) == 0) {
                    return $this->sendResponse(false, __('strings.booking_process_update_faild'));
                } elseif (count($customer_detail) == $actioncount) {
                    return $this->sendResponse(false, __('strings.booking_process_update_faild'));
                }
            }

            // if (isset($customer_detail_update['is_include_lunch_hour']) && $customer_detail_inputs['is_include_lunch_hour']) {
            //     $is_include_lunch_hour = true;
            // }

            $course_detail_inputs['course_id'] = $course_detail_update['course_id'];
            //$course_detail_inputs['course_detail_id'] = $course_detail_update['course_detail_id'];
            $course_detail_inputs['course_type'] = $course_detail_update['course_type'];
            /* $course_detail_inputs['StartDate_Time'] = $course_detail_update['StartDate_Time'];
            $course_detail_inputs['EndDate_Time'] = $course_detail_update['EndDate_Time']; */
            $course_detail_inputs['lead'] = $course_detail_update['lead'];
            $course_detail_inputs['contact_id'] = $course_detail_update['contact_id'];
            $course_detail_inputs['source_id'] = $course_detail_update['source_id'];
            $course_detail_inputs['no_of_participant'] = $course_detail_update['no_of_participant'];
            $course_detail_inputs['meeting_point_id'] = $course_detail_update['meeting_point_id'];
            $course_detail_inputs['meeting_point'] = $course_detail_update['meeting_point'];
            $course_detail_inputs['meeting_point_lat'] = $course_detail_update['meeting_point_lat'];
            $course_detail_inputs['meeting_point_long'] = $course_detail_update['meeting_point_long'];
            $course_detail_inputs['is_extra_participant'] = $course_detail_update['is_extra_participant'];
            $course_detail_inputs['no_of_extra_participant'] = $course_detail_update['no_of_extra_participant'];
            $course_detail_inputs['updated_by'] = $update['updated_by'];
            $course_detail_inputs['difficulty_level'] = $course_detail_update['difficulty_level'];
            if (isset($course_detail_update['lunch_hour'])) {
                $course_detail_inputs['lunch_hour'] = $course_detail_update['lunch_hour'];
            }
            // dd($course_detail_inputs);
            $course_detail_inputs['booking_process_id'] = $booking_processes->id;
            $course_detail = $course_detail->update($course_detail_inputs);
            $course_type = $request->course_detail['course_type'];
        }

        //This code commented for valid only customer id not group id
        //if($course_type == 'Private') {
        $i = 0;
        if ($request->customer_detail) {
            $add_customer_detail1 = array();
            $delete_customer_detail_ids = array();
            $update_customer_detail_detalis = array();
            $course_detail1 = array();
            $new_added_customers = array();
            $is_include_lunch_hour = false; //For check include lunch hour

            foreach ($request->customer_detail as $key => $customer_detail) {
                $customer_detail_update = $customer_detail;
                if ($customer_detail_update['is_action'] == 1) {
                    $add_customer_detail1[$i]['customer_id'] = $customer_detail_update['customer_id'];

                    if (isset($customer_detail_update['sub_child_id'])) {
                        $add_customer_detail1[$i]['sub_child_id'] = $customer_detail_update['sub_child_id'];
                    }
                    $add_customer_detail1[$i]['accommodation'] = $customer_detail_update['accommodation'];
                    $add_customer_detail1[$i]['payi_id'] = $customer_detail_update['payi_id'];
                    $add_customer_detail1[$i]['course_detail_id'] = $customer_detail_update['course_detail_id'];
                    $add_customer_detail1[$i]['StartDate_Time'] = $customer_detail_update['StartDate_Time'];
                    $add_customer_detail1[$i]['EndDate_Time'] = $customer_detail_update['EndDate_Time'];

                    //explode the date and time
                    $tempStrt = explode(" ", $customer_detail_update['StartDate_Time']);
                    $tempEnd = explode(" ", $customer_detail_update['EndDate_Time']);
                    //===============
                    $add_customer_detail1[$i]['start_date'] = $tempStrt[0];
                    $add_customer_detail1[$i]['end_date'] = $tempEnd[0];
                    $add_customer_detail1[$i]['start_time'] = $tempStrt[1];
                    $add_customer_detail1[$i]['end_time'] = $tempEnd[1];

                    $add_customer_detail1[$i]['cal_payment_type'] = $customer_detail_update['cal_payment_type'];
                    $add_customer_detail1[$i]['is_include_lunch'] = $customer_detail_update['is_include_lunch'];
                    $add_customer_detail1[$i]['include_lunch_price'] = $customer_detail_update['include_lunch_price'];

                    $add_customer_detail1[$i]['no_of_days'] = $customer_detail_update['no_of_days'];
                    $add_customer_detail1[$i]['hours_per_day'] = $customer_detail_update['hours_per_day'];
                    $add_customer_detail1[$i]['is_payi'] = $customer_detail_update['is_payi'];
                    $add_customer_detail1[$i]['created_by'] = $update['updated_by'];
                    $add_customer_detail1[$i]['created_at'] = date("Y-m-d H:i:s");
                    $add_customer_detail1[$i]['booking_process_id'] = $booking_processes->id;
                    $add_customer_detail1[$i]['QR_number'] = $booking_processes->id . $customer_detail_update['customer_id'] . mt_rand(100000, 999999);
                    $add_customer_detail1[$i]['is_updated'] = 1;
                    $add_customer_detail1[$i]['activity'] = ($customer_detail_update['activity'] ?: null);
                    $add_customer_detail1[$i]['is_include_lunch_hour'] = ($customer_detail_update['is_include_lunch_hour'] ?: false);

                    if ($booking_processes->is_draft === 0) {
                        $new_added_customers[] = $customer_detail_update['customer_id'];
                    }
                } elseif ($customer_detail_update['is_action'] == 2) {
                    $update_customer_detail_detalis[$i]['customer_id'] = $customer_detail_update['customer_id'];
                    $update_customer_detail_detalis[$i]['accommodation'] = $customer_detail_update['accommodation']??null;
                    $update_customer_detail_detalis[$i]['accommodation_other'] = $customer_detail_update['accommodation_other']??null;
                    $update_customer_detail_detalis[$i]['payi_id'] = $customer_detail_update['payi_id'];

                    if (isset($customer_detail_update['sub_child_id'])) {
                        $update_customer_detail_detalis[$i]['sub_child_id'] = $customer_detail_update['sub_child_id'];
                    }

                    $update_customer_detail_detalis[$i]['course_detail_id'] = $customer_detail_update['course_detail_id'];
                    $update_customer_detail_detalis[$i]['StartDate_Time'] = $customer_detail_update['StartDate_Time'];
                    $update_customer_detail_detalis[$i]['EndDate_Time'] = $customer_detail_update['EndDate_Time'];

                    //explode the date and time
                    $tempStrt = explode(" ", $customer_detail_update['StartDate_Time']);
                    $tempEnd = explode(" ", $customer_detail_update['EndDate_Time']);
                    //===============
                    $update_customer_detail_detalis[$i]['start_date'] = $tempStrt[0];
                    $update_customer_detail_detalis[$i]['end_date'] = $tempEnd[0];
                    $update_customer_detail_detalis[$i]['start_time'] = $tempStrt[1];
                    $update_customer_detail_detalis[$i]['end_time'] = $tempEnd[1];

                    $update_customer_detail_detalis[$i]['cal_payment_type'] = $customer_detail_update['cal_payment_type'];
                    $update_customer_detail_detalis[$i]['is_include_lunch'] = $customer_detail_update['is_include_lunch'];
                    $update_customer_detail_detalis[$i]['include_lunch_price'] = $customer_detail_update['include_lunch_price'];

                    $update_customer_detail_detalis[$i]['no_of_days'] = $customer_detail_update['no_of_days'];
                    $update_customer_detail_detalis[$i]['hours_per_day'] = $customer_detail_update['hours_per_day'];
                    $update_customer_detail_detalis[$i]['is_payi'] = $customer_detail_update['is_payi'];
                    $update_customer_detail_detalis[$i]['updated_by'] = $update['updated_by'];
                    $update_customer_detail_detalis[$i]['updated_at'] = date("Y-m-d H:i:s");
                    $update_customer_detail_detalis[$i]['booking_process_id'] = $booking_processes->id;
                    $update_customer_detail_detalis[$i]['is_updated'] = 1;
                    $update_customer_detail_detalis[$i]['activity'] = ($customer_detail_update['activity'] ?: null);
                    $update_customer_detail_detalis[$i]['is_include_lunch_hour'] = ($customer_detail_update['is_include_lunch_hour'] ?: false);

                    $update_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes->id)->where('customer_id', $customer_detail_update['customer_id'])
                        ->where('id', $customer_detail_update['booking_customer_id'])
                        ->update($update_customer_detail_detalis[$i]);
                } elseif ($customer_detail_update['is_action'] == 3) {
                    // $delete_customer_detail_ids[$i] = $customer_detail_update['customer_id'];
                    $delete_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes->id)->where('customer_id', $customer_detail_update['customer_id'])
                        ->where('id', $customer_detail_update['booking_customer_id'])
                        ->delete();
                }

                if ($customer_detail_update['is_action'] == 1 || $customer_detail_update['is_action'] == 2) {
                    $course_detail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();
                    if ($customer_detail_update['StartDate_Time'] < $course_detail->StartDate_Time) {
                        $course_detail1['StartDate_Time'] = $customer_detail_update['StartDate_Time'];
                    }
                    if ($customer_detail_update['EndDate_Time'] > $course_detail->EndDate_Time) {
                        $course_detail1['EndDate_Time'] = $customer_detail_update['EndDate_Time'];
                    }
                }
                if (isset($customer_detail_update['is_include_lunch_hour']) && $customer_detail_update['is_include_lunch_hour']) {
                    $is_include_lunch_hour = true;
                }

                $i = $i + 1;
            }

            $add_customer_detail = BookingProcessCustomerDetails::insert($add_customer_detail1);
            /* $delete_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes->id)->whereIn('customer_id', $delete_customer_detail_ids)->delete(); */

            if (!empty($course_detail1['StartDate_Time'])) {
                $tempStrt1 = explode(" ", $course_detail1['StartDate_Time']);
                $course_detail1['start_date'] = $tempStrt1[0];
                $course_detail1['start_time'] = $tempStrt1[1];
            }
            if (!empty($course_detail1['EndDate_Time'])) {
                $tempEnd1 = explode(" ", $course_detail1['EndDate_Time']);
                $course_detail1['end_date'] = $tempEnd1[0];
                $course_detail1['end_time'] = $tempEnd1[1];
            }

            /**Lunch hour related */
            if (!empty($course_detail1['StartDate_Time']) && !empty($course_detail1['EndDate_Time'])) {
                $datetime1 = new DateTime($course_detail1['StartDate_Time']);
                $datetime2 = new DateTime($course_detail1['EndDate_Time']);

                $interval = $datetime2->diff($datetime1);
                $course_detail1['total_days'] = $interval->format('%d') + 1; //Day -1 calucalte count so
                $course_detail1['total_hours'] = $interval->format('%h');
                if($is_include_lunch_hour){
                    $course_detail1['lunch_hour'] = 1;//Lunch hour is fix 1 hour
                }
            }
            /**End */
            if ($is_include_lunch_hour && $course_type === 'Group') {
                $course_detail1['lunch_hour'] = 1; //Lunch hour is fix 1 hour
            } else {
                if (isset($course_detail_update['lunch_hour']) && isset($course_detail_update['lunch_start_time'])) {
                    $lunch_hour = $course_detail_update['lunch_hour'];
                    $lunch_start_time = $course_detail_update['lunch_start_time'];
                    $lunch_end_time = $this->getLunchEndTime($lunch_hour, $lunch_start_time);
                    $course_detail1['lunch_end_time'] = $lunch_end_time;
                    $course_detail1['lunch_hour'] =  $course_detail_update['lunch_hour'];
                    $course_detail1['lunch_start_time'] =  $course_detail_update['lunch_start_time'];
                }
            }

            $course_detail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id);
            $course_detail->update($course_detail1);
        }
        //return $this->sendResponse(true,__('strings.booking_process_updated_success'));
        /*  } else {
             BookingProcessCustomerDetails::where('booking_process_id',$id)->delete();
             if($request->group_id) {
                 $customer_detail_inputs['created_by'] = $update['updated_by'];
                 $customer_detail_inputs['updated_by'] = $update['updated_by'];
                 $customer_detail_inputs['booking_process_id'] = $booking_processes->id;
                 $customer_detail_inputs['customer_id'] = $request->group_id;
                 $customer_detail = BookingProcessCustomerDetails::create($customer_detail_inputs);
             }
         }    */

        BookingProcessInstructorDetails::where('booking_process_id', $id)->delete(); //Remove Booking Instructor Detail
        BookingInstructorDetailMap::where('booking_process_id', $id)->delete(); //Remove Instructor Booking Map Detail
        InstructorActivityTimesheet::where('booking_id', $id)->delete(); //Remove InstructorActivityTimesheet
        if ($request->instructor_detail) {
            //BookingProcessInstructorDetails::where('booking_process_id',$id)->delete();
            $instructor_detail_inputs['created_by'] = $update['updated_by'];
            $instructor_detail_inputs['updated_by'] = $update['updated_by'];
            $instructor_detail_inputs['booking_process_id'] = $booking_processes->id;
            foreach ($request->instructor_detail as $key => $instructor_detail) {
                $instructor_detail_inputs['contact_id'] = $instructor_detail['contact_id'];
                $starttime = $instructor_detail['start_time'];
                $endtime = $instructor_detail['end_time'];
                $instructor_date = $instructor_detail['date'];

                $check_duplicate_insert = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes->id)->where('contact_id', $instructor_detail['contact_id'])->first();
                if (!$check_duplicate_insert) {
                    $check_instructor_update = BookingProcessInstructorDetails::create($instructor_detail_inputs);
                }

                $check_mpas_duplicate_insert = BookingInstructorDetailMap::where('booking_process_id', $booking_processes->id)->where('contact_id', $instructor_detail['contact_id'])
                    ->where('startdate_time', $instructor_date . " " . $starttime)
                    ->where('enddate_time', $instructor_date . " " . $endtime)
                    ->first();

                if (!$check_mpas_duplicate_insert) {
                    //Add record to instructor booking map table
                    $booking_instructor_map_inputs['contact_id'] = $instructor_detail['contact_id'];
                    $booking_instructor_map_inputs['booking_process_id'] = $booking_processes->id;
                    $booking_instructor_map_inputs['startdate_time'] = $instructor_date . " " . $starttime;
                    $booking_instructor_map_inputs['enddate_time'] = $instructor_date . " " . $endtime;

                    $booking_instructor_map_add = BookingInstructorDetailMap::create($booking_instructor_map_inputs);
                }

                //add instructor timesheet info
                $contact = Contact::find($instructor_detail['contact_id']);
                if ($contact && $contact->user_detail) {
                    $start_date = $instructor_date;
                    $end_date = $instructor_date;
                    $start_time = $starttime;
                    $end_time = $endtime;
                    if ($start_date && $start_time && $end_time) {
                        $this->InstructorTimesheetCreate($contact->user_detail->id, $booking_processes->id, $start_date, $end_date, $start_time, $end_time);
                    }

                    // $course_detail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();
                    // if ($course_detail) {
                    //     $startDateTime = explode(" ", $course_detail->StartDate_Time);
                    //     $endDateTime = explode(" ", $course_detail->EndDate_Time);
                    //     $start_date=$startDateTime[0];
                    //     $end_date=$endDateTime[0];
                    //     $start_time = $startDateTime[1];
                    //     $end_time = $endDateTime[1];
                    //     $this->InstructorTimesheetCreate($contact->user_detail->id, $booking_processes->id, $start_date, $end_date, $start_time, $end_time);
                    // }
                }

                /**For if instructor has block with booking dates then release the blocks */
                $this->checkInstructorBlockExist($instructor_date, $starttime, $endtime, $instructor_detail['contact_id']);
            }
            if ($check_instructor_update) {
                $instructor_ids = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes->id)->pluck('contact_id');
            }
            $instructor_data = Contact::whereIn('id', $instructor_ids)->select('salutation', 'first_name', 'last_name')->get()->toArray();
            if ($instructor_data) {
                if ($request->course_detail) {
                    $course_detail_inputs = $request->course_detail;
                    $course_id = $course_detail_inputs['course_id'];
                }
                $total_instructor = count($instructor_data);
                $data['course_id'] = $course_id;
                $course = Course::find($data['course_id']);

                $instructor = Contact::where('id', $instructor_ids[0])->first();
                $instructor_name = $instructor->first_name . " " . $instructor->last_name;

                $data['course_name'] = $course->name;
                $data['booking_processes_id'] = $booking_processes->id;
                //$data['instructor_data'] = $instructor_data;
                $title = "Your Course Instructor's are Changed";
                // $body = "Your Course: ".$course->name." has ".$total_instructor." Instructor's are Changed";

                $body = 'Instructor ' . $instructor_name . ' has been new assigned to your course.';

                $this->setPushNotificationData($request->customer_detail, 4, $data, $title, $body, $booking_processes->id);
            }
        }

        /**For add/ delete booking request instructor details */
        if ($request->request_instructor_details) {
            BookingProcessRequestInstructor::where('booking_process_id', $id)->delete();
            $i = 0;
            foreach ($request->request_instructor_details as $instructor) {
                $request_instructor_details[$i]['created_by'] = $update['updated_by'];
                $request_instructor_details[$i]['created_at'] = date("Y-m-d H:i:s");
                $request_instructor_details[$i]['booking_process_id'] = $id;
                $request_instructor_details[$i]['contact_id'] = $instructor;
                $i = $i + 1;
            }
            BookingProcessRequestInstructor::insert($request_instructor_details);

            /**In feature use this way if multiple request instructors are select */
            /**$i = 0;
            foreach ($request->request_instructor_details as $instructor) {
                if($instructor['action'] == 'add'){
                    $request_instructor_details[$i]['created_by'] = $update['updated_by'];
                    $request_instructor_details[$i]['created_at'] = date("Y-m-d H:i:s");
                    $request_instructor_details[$i]['booking_process_id'] = $id;
                    $request_instructor_details[$i]['contact_id'] = $instructor['contact_id'];
                }elseif($instructor['action'] == 'delete'){
                    $delete_request_instructor[] =  $instructor['id'];
                }
                $i = $i + 1;
            }
            if(isset($request_instructor_details)){
                BookingProcessRequestInstructor::insert($request_instructor_details);
            }

            if(isset($delete_request_instructor)){
                BookingProcessRequestInstructor::destroy($delete_request_instructor);
            }*/
        }
        /**End */

        if ($request->language_detail) {
            BookingProcessLanguageDetails::where('booking_process_id', $id)->delete();
            $language_detail_inputs['created_by'] = $update['updated_by'];
            $language_detail_inputs['updated_by'] = $update['updated_by'];
            $language_detail_inputs['booking_process_id'] = $booking_processes->id;
            foreach ($request->language_detail as $key => $language_detail) {
                $language_detail_inputs['language_id'] = $language_detail;
                $language_detail = BookingProcessLanguageDetails::create($language_detail_inputs);
            }
        }

        // BookingProcessExtraParticipant::where('booking_process_id', $id)->delete();
        // if ($request->extra_participants_details) {
        //     //BookingProcessExtraParticipant::where('booking_process_id', $id)->delete();
        //     foreach ($request->extra_participants_details as $key => $extra_participants_detail) {
        //         $extra_participants_inputs = $extra_participants_detail;
        //         $extra_participants_inputs['created_by'] = $update['updated_by'];
        //         $extra_participants_inputs['updated_by'] = $update['updated_by'];
        //         $extra_participants_inputs['booking_process_id'] = $booking_processes->id;
        //         $instructor_detail_inputs = BookingProcessExtraParticipant::create($extra_participants_inputs);
        //     }
        // }

        if ($request->additional_information) {
            $additional_information = BookingProcesses::find($id);
            $additional_information_input = $request->additional_information;
            $additional_information_input['updated_by'] = auth()->user()->id;
            $additional_information->update($additional_information_input);
        }

        if ($request->payment_detail) {
            $i = 0;
            $delete_payment_detail_ids = array();
            $update_payment_detail_detalis = array();


            $payment_detail = BookingProcessPaymentDetails::where('booking_process_id', $id);

            foreach ($request->payment_detail as $key => $payment_detail) {
                $payment_detail_update = $payment_detail;
                if ($payment_detail_update['is_action'] == 1) {
                    $add_payment_detail1['created_by'] = $update['updated_by'];
                    $add_payment_detail1['created_at'] = date("Y-m-d H:i:s");
                    $add_payment_detail1['booking_process_id'] = $id;

                    $add_payment_detail1['payi_id'] = $payment_detail_update['payi_id'];

                    if (isset($payment_detail_update['sub_child_id'])) {
                        $add_payment_detail1['sub_child_id'] = $payment_detail_update['sub_child_id'];
                    }

                    $add_payment_detail1['include_extra_price'] = $payment_detail_update['include_extra_price'];
                    $add_payment_detail1['no_of_days'] = $payment_detail_update['no_of_days'];
                    // $add_payment_detail1['hours_per_day'] = $payment_detail_update['hours_per_day'];
                    $add_payment_detail1['course_detail_id'] = $payment_detail_update['course_detail_id'];

                    $invoice_number = SequenceMaster::where('code', 'INV')->first();

                    if ($invoice_number) {
                        $input['invoice_number'] = $invoice_number->sequence;
                        $add_payment_detail1['invoice_number'] = "INV" . date("m") . "" . date("Y") . $input['invoice_number'];
                        $invoice_number->update(['sequence' => $invoice_number->sequence + 1]);
                    }

                    $course_detail_inputs = $request->course_detail;

                    $add_payment_detail1['total_price'] = $payment_detail_update['price'];
                    $add_payment_detail1['customer_id'] = $payment_detail_update['customer_id'];
                    $add_payment_detail1['payment_method_id'] = $payment_detail_update['payment_method_id'];


                    if ($course_detail_inputs['course_type'] === 'Private') {
                        /**Code comment for new requirement
                         * Date : 23-07-2020
                         */
                        // $extra_participant = BookingProcessExtraParticipant::where('booking_process_id', $booking_processes->id)->count();
                        $booking_course_data = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();

                        $extra_person_charge = 0;
                        if ($booking_course_data->is_extra_participant) {
                            $extra_person_charge = $payment_detail_update['extra_person_charge'];
                        }

                        /**If no of extra participant available */
                        $total_participant = 0;
                        if ($booking_course_data->no_of_extra_participant) {
                            $total_participant = $booking_course_data->no_of_extra_participant;
                        }

                        $add_payment_detail1['extra_person_charge'] = $extra_person_charge;
                        $add_payment_detail1['extra_participant'] = $total_participant * $extra_person_charge;
                    }

                    $add_payment_detail1['net_price'] = $payment_detail_update['net_price'];
                    $add_payment_detail1['price_per_day'] = $payment_detail_update['price_per_day'];

                    $add_payment_detail1['vat_percentage'] = $payment_detail_update['vat_percentage'];
                    $add_payment_detail1['vat_amount'] = $payment_detail_update['vat_amount'];
                    $add_payment_detail1['vat_excluded_amount'] = $payment_detail_update['vat_excluded_amount'];

                    $add_payment_detail1['cal_payment_type'] = $payment_detail_update['cal_payment_type'];
                    $add_payment_detail1['is_include_lunch'] = $payment_detail_update['is_include_lunch'];
                    $add_payment_detail1['include_lunch_price'] = $payment_detail_update['include_lunch_price'];

                    $add_payment_detail1['lunch_vat_amount'] = $payment_detail_update['lunch_vat_amount'];
                    $add_payment_detail1['lunch_vat_excluded_amount'] = $payment_detail_update['lunch_vat_excluded_amount'];
                    $add_payment_detail1['lunch_vat_percentage'] = $payment_detail_update['lunch_vat_percentage'];

                    /**For settlement amount */
                    $add_payment_detail['settlement_amount'] = $payment_detail_update['settlement_amount'];
                    $add_payment_detail['settlement_description'] = $payment_detail_update['settlement_description'];
                    /** */
                    $add_payment_detail['credit_card_type'] = ($payment_detail_update['credit_card_type'] ?: null);

                    /*
                    if (($payment_detail_update['payment_method_id'] == 3 || $payment_detail_update['payment_method_id'] == 4) && ($payment_detail_update['is_pay'] == 1)) {
                        $add_payment_detail1['status'] = "Success";
                    } else {
                        $add_payment_detail1['status'] = "Pending";
                    } */


                    // dd($add_payment_detail1);

                    $add_payment_detail = BookingProcessPaymentDetails::create($add_payment_detail1);

                    if ($add_payment_detail) {
                        $invoice_link = $this->updateInvoiceLink($add_payment_detail->id);
                    }

                    // if voucher is applied

                    if ($payment_detail_update['voucher_id'] != null) {
                        $voucher = Voucher::find($payment_detail_update['voucher_id']);
                        if (!$voucher) {
                            return $this->sendResponse(false, __('strings.invalid_voucher'));
                        }

                        try {
                            $invoice = $voucher->apply($add_payment_detail->id);
                        } catch (\Exception $e) {
                            DB::rollback();
                            return $this->sendResponse(false, $e->getMessage());
                        }
                    }

                    // if payment is done
                    $invoice_amount = $payment_detail_update['net_price'];
                    /**For manage outstanding amount */
                    BookingProcessPaymentDetails::where('id', $add_payment_detail->id)->update(['outstanding_amount' => $invoice_amount]);
                    /** */
                    if ($payment_detail_update['is_pay'] == 1) {
                        $payment_details = [
                            "qbon_number"               => $payment_detail_update['qbon_number'],
                            "contact_id"                => $payment_detail_update['payi_id'],
                            "payment_type"              => $payment_detail_update['payment_method_id'],
                            "is_office"                 => $payment_detail_update['is_office'],
                            "office_id"                 => $payment_detail_update['office_id'],
                            "amount_given_by_customer"  => $payment_detail_update['amount_given_by_customer'],
                            "amount_returned"           => $payment_detail_update['amount_returned'],
                            "total_amount"              => $payment_detail_update['price'],
                            "total_discount"            => $payment_detail_update['discount'] ?: 0,
                            "total_vat_amount"          => $payment_detail_update['vat_amount'],
                            "total_net_amount"          => $payment_detail_update['net_price'],
                            "credit_card_type"          => ($payment_detail_update['credit_card_type'] ?: null)
                        ];
                        $invoic_data = array();
                        $invoic_data[]['id'] = $add_payment_detail->id;

                        DB::beginTransaction();
                        try {
                            // call common function to create payment
                            $payment = $this->createPayment($payment_details, $invoic_data);
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollback();
                            /**For save exception */
                            $this->saveException($e, $request->header(), $request->ip());
                        }
                    }
                } elseif ($payment_detail_update['is_action'] == 2) {
                    $update_payment_detail_detalis[$i]['updated_by'] = $update['updated_by'];
                    $update_payment_detail_detalis[$i]['booking_process_id'] = $booking_processes->id;
                    $update_payment_detail_detalis[$i]['payi_id'] = $payment_detail_update['payi_id'];

                    if (isset($payment_detail_update['sub_child_id'])) {
                        $update_payment_detail_detalis[$i]['sub_child_id'] = $payment_detail_update['sub_child_id'];
                    }

                    $update_payment_detail_detalis[$i]['no_of_days'] = $payment_detail_update['no_of_days'];
                    $update_payment_detail_detalis[$i]['hours_per_day'] = $payment_detail_update['hours_per_day'];
                    $update_payment_detail_detalis[$i]['course_detail_id'] = $payment_detail_update['course_detail_id'];
                    $update_payment_detail_detalis[$i]['include_extra_price'] = $payment_detail_update['include_extra_price'];
                    $update_payment_detail_detalis[$i]['payment_method_id'] = $payment_detail_update['payment_method_id'];

                    $course_detail_inputs = $request->course_detail;

                    $update_payment_detail_detalis[$i]['total_price'] = $payment_detail_update['price'];
                    $update_payment_detail_detalis[$i]['price_per_day'] = $payment_detail_update['price_per_day'];

                    $update_payment_detail_detalis[$i]['net_price'] = $payment_detail_update['net_price'];
                    $update_payment_detail_detalis[$i]['vat_percentage'] = $payment_detail_update['vat_percentage'];
                    $update_payment_detail_detalis[$i]['vat_excluded_amount'] = $payment_detail_update['vat_excluded_amount'];
                    $update_payment_detail_detalis[$i]['vat_amount'] = $payment_detail_update['vat_amount'];
                    $update_payment_detail_detalis[$i]['extra_person_charge'] = $payment_detail_update['extra_person_charge'];
                    $update_payment_detail_detalis[$i]['voucher_id'] = $payment_detail_update['voucher_id'];
                    $update_payment_detail_detalis[$i]['discount'] = $payment_detail_update['discount'];

                    $update_payment_detail_detalis[$i]['cal_payment_type'] = $payment_detail_update['cal_payment_type'];
                    $update_payment_detail_detalis[$i]['is_include_lunch'] = $payment_detail_update['is_include_lunch'];
                    $update_payment_detail_detalis[$i]['include_lunch_price'] = $payment_detail_update['include_lunch_price'];

                    $update_payment_detail_detalis[$i]['lunch_vat_amount'] = $payment_detail_update['lunch_vat_amount'];
                    $update_payment_detail_detalis[$i]['lunch_vat_excluded_amount'] = $payment_detail_update['lunch_vat_excluded_amount'];
                    $update_payment_detail_detalis[$i]['lunch_vat_percentage'] = $payment_detail_update['lunch_vat_percentage'];

                    if ($course_detail_inputs['course_type'] === 'Private') {
                        /**Code comment for new requirement
                         * Date : 23-07-2020
                         */
                        // $extra_participant = BookingProcessExtraParticipant::where('booking_process_id', $booking_processes->id)->count();
                        $booking_course_data = BookingProcessCourseDetails::where('booking_process_id', $booking_processes->id)->first();

                        $extra_person_charge = 0;
                        if ($booking_course_data->is_extra_participant) {
                            $extra_person_charge = $payment_detail_update['extra_person_charge'];
                        }

                        /**If no of extra participant available */
                        $total_participant = 0;
                        if ($booking_course_data->no_of_extra_participant) {
                            $total_participant = $booking_course_data->no_of_extra_participant;
                        }
                        $update_payment_detail_detalis[$i]['extra_participant'] = $total_participant * $extra_person_charge;
                        // $netPrise = $total_price1 - ($total_price1*($payment_detail_update['discount']/100));
                    } /* else {
                        $netPrise = $total_price - ($total_price*($payment_detail_update['discount']/100));
                    } */

                    // $update_payment_detail_detalis[$i]['net_price'] = $netPrise;
                    // $update_payment_detail_detalis[$i]['discount'] = $payment_detail_update['discount'];

                    /* if (($payment_detail_update['payment_method_id'] == 3 || $payment_detail_update['payment_method_id'] == 4) && ($payment_detail_update['is_pay'] == 1)) {
                        $update_payment_detail_detalis[$i]['status'] = "Success";
                    } else {
                        $update_payment_detail_detalis[$i]['status'] = "Pending";
                    } */

                    /**For settlement amount */
                    $update_payment_detail_detalis[$i]['settlement_amount'] = $payment_detail_update['settlement_amount'];
                    $update_payment_detail_detalis[$i]['settlement_description'] = $payment_detail_update['settlement_description'];
                    /** */

                    $update_payment_detail_detalis[$i]['credit_card_type'] = ($payment_detail_update['credit_card_type'] ?: null);

                    $update_payment_detail = BookingProcessPaymentDetails::where('booking_process_id', $booking_processes->id)->where('customer_id', $payment_detail_update['customer_id'])
                        ->where('id', $payment_detail_update['payment_detail_id'])
                        ->update($update_payment_detail_detalis[$i]);
                    $invoice_link = $this->updateInvoiceLink($payment_detail_update['payment_detail_id']);

                    $update_payment_detail_data = BookingProcessPaymentDetails::where('booking_process_id', $booking_processes->id)->where('customer_id', $payment_detail_update['customer_id'])
                        ->where('id', $payment_detail_update['payment_detail_id'])->first();

                    // if voucher is applied
                    if ($payment_detail_update['voucher_id'] && $update_payment_detail_data->voucher_id == null) {
                        $voucher = Voucher::find($payment_detail_update['voucher_id']);
                        if (!$voucher) {
                            return $this->sendResponse(false, __('strings.invalid_voucher'));
                        }

                        try {
                            $invoice = $voucher->apply($payment_detail_update['payment_detail_id']);
                        } catch (\Exception $e) {
                            DB::rollback();
                            return $this->sendResponse(false, $e->getMessage());
                        }
                    }
                    $payment_detail_data = BookingProcessPaymentDetails::where('id', $payment_detail_update['payment_detail_id'])->first();
                    // if payment is done
                    $invoice_amount = $payment_detail_update['net_price'];
                    /**For manage outstanding amount */
                    BookingProcessPaymentDetails::where('id', $payment_detail_update['payment_detail_id'])->update(['outstanding_amount' => $invoice_amount]);
                    /** */
                    if ($payment_detail_update['is_pay'] == 1 && $payment_detail_data['status'] == 'Pending') {
                        $payment_details = [
                            "qbon_number"               => $payment_detail_update['qbon_number'],
                            "contact_id"                => $payment_detail_update['payi_id'],
                            "payment_type"              => $payment_detail_update['payment_method_id'],
                            "is_office"                 => $payment_detail_update['is_office'],
                            "office_id"                 => $payment_detail_update['office_id'],
                            "amount_given_by_customer"  => $payment_detail_update['amount_given_by_customer'],
                            "amount_returned"           => $payment_detail_update['amount_returned'],
                            "total_amount"              => $payment_detail_update['price'],
                            "total_discount"            => $payment_detail_update['discount'] ?: 0,
                            "total_vat_amount"          => $payment_detail_update['vat_amount'],
                            "total_net_amount"          => $payment_detail_update['net_price'],
                            "credit_card_type"          => ($payment_detail_update['credit_card_type'] ?: null)
                        ];
                        $invoic_data = array();
                        $invoic_data[]['id'] = $payment_detail_update['payment_detail_id'];
                        DB::beginTransaction();
                        try {
                            // call common function to create payment
                            $payment = $this->createPayment($payment_details, $invoic_data);
                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollback();
                            /**For save exception */
                            $this->saveException($e, $request->header(), $request->ip());
                        }
                    }
                } elseif ($payment_detail_update['is_action'] == 3) {
                    // $delete_payment_detail_ids[$i] = $payment_detail_update['customer_id'];
                    $delete_payment_detail = BookingProcessPaymentDetails::where('booking_process_id', $booking_processes->id)->where('booking_process_id', $booking_processes->id)->where('customer_id', $payment_detail_update['customer_id'])->where('id', $payment_detail_update['payment_detail_id'])->delete();
                }
                $i++;
            }
            // $add_payment_detail = BookingProcessPaymentDetails::insert($add_payment_detail1);
            // $delete_payment_detail = BookingProcessPaymentDetails::where('booking_process_id', $booking_processes->id)->whereIn('customer_id', $delete_payment_detail_ids)->delete();
            //return $this->sendResponse(true,__('strings.booking_process_updated_success'));
            //$payment_detail->update($payment_detail_inputs);
        }

        if ($request->course_detail) {
            $course_detail_inputs = $request->course_detail;
        }

        $data['course_id'] = $course_detail_inputs['course_id'];
        $course = Course::find($data['course_id']);
        $data['course_name'] = $course->name;
        $data['booking_processes_id'] = $booking_processes->id;
        $title = "Updated Your Course";
        // $body = "Your Course: ".$course->name." is Updated";
        $body = "Your Course: " . $course->name . " is Updated";
        $this->setPushNotificationData($request->customer_detail, 2, $data, $title, $body, $booking_processes->id);
        //temp comment

        if (count($new_added_customers)) {
            /**Send notification for newly added customers */
            $title = "Enroll New Course";
            $body = "You must have to enroll in the: " . $course->name . " to access the course";
            $this->setPushNotificationData($request->customer_detail, 23, $data, $title, $body, $booking_processes->id);

            /**Send boooking confiramation email for newly added customers */
            $this->customer_booking_confirm($booking_processes->id, $new_added_customers);
        }

        //$emails_sent = $this->customer_update_booking($booking_processes->id);

        //Update chatroom for openfire
        UpdateChatRoom::dispatch(false, $booking_processes->id);

        /**Add crm user action trail */
        if ($booking_processes) {
            $action_id = $booking_processes->id; //Booking Process id
            $action_type = 'U'; //U = Updated
            $module_id = 16; //module id base module table
            $module_name = "Bookings"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        if ($request->is_draft == 1) {
            // $this->sendDraftPayedEmail($booking_processes->id);
        }

        return $this->sendResponse(true, __('strings.booking_process_updated_success'));
    }

    /* API for Deleting Booking Process*/
    public function deleteBookingProcess($id)
    {
        $booking_processes = BookingProcesses::find($id);

        if (!$booking_processes) {
            return $this->sendResponse(false, __('strings.booking_process_not_found'));
        }
        /**Check booking invoices are assigned in consolidated invoice or not */
        $assigned = $this->checkInvoiceAssignedConsolidated($id);
        if ($assigned) {
            return $this->sendResponse(false, __('strings.booking_invoice_already_added'));
        }
        /**End */

        /**Add crm user action trail */
        if ($booking_processes) {
            $action_id = $booking_processes->id; //Booking Process id
            $action_type = 'D'; //D = Deleted
            $module_id = 16; //module id base module table
            $module_name = "Bookings"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        $delete_booking_processes = $booking_processes->delete();

        if ($delete_booking_processes) {
            $booking_processes_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $id)->pluck('customer_id');
            if ($booking_processes_customer_detail->count() > 0) {
                $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $id)->first();
                $course = Course::find($booking_processes_course_datail->course_id);
                $data['course_id'] = $booking_processes_course_datail->course_id;
                $data['course_name'] = $course->name;
                $data['booking_processes_id'] = $id;
                $title = "Course Deleted";
                // $body = "Your Course: ".$course->name." is Deleted";
                $body = "Your Course:  " . $course->name . " is Deleted";
                $this->setPushNotificationData($booking_processes_customer_detail, 3, $data, $title, $body, $id);
            }
        }

        $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $id)->delete();
        $booking_processes_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $id)->delete();
        $booking_processes_instructor_detail = BookingProcessInstructorDetails::where('booking_process_id', $id)->delete();
        $booking_processes_payment_detail = BookingProcessPaymentDetails::where('booking_process_id', $id)->delete();
        $booking_processes_extra_participant_detail = BookingProcessExtraParticipant::where('booking_process_id', $id)->delete();
        $booking_processes_customer_detail = BookingProcessLanguageDetails::where('booking_process_id', $id)->delete();
        $booking_processes_instructor_activity = InstructorActivity::where('booking_id', $id)->delete();
        $booking_processes_instructor_activity_comment = InstructorActivityComment::where('booking_id', $id)->delete();
        $booking_processes_instructor_activity_timesheet = InstructorActivityTimesheet::where('booking_id', $id)->delete();

        BookingInstructorDetailMap::where('booking_process_id', $id)->delete();

        return $this->sendResponse(true, __('strings.booking_process_deleted_success'));
    }

    /* API for Booking Process List */
    public function bookingProcessList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
        $booking_processes_ids = BookingProcesses::query();

        if ($request->is_third_party && $request->is_third_party === 'Y') {
            $booking_processes_ids = $booking_processes_ids->where('is_third_party', true);
        }
        if ($request->is_third_party && $request->is_third_party === 'N') {
            $booking_processes_ids = $booking_processes_ids->where('is_third_party', false);
        }

        $booking_processes_ids = $booking_processes_ids->where('is_trash', $request->is_trash)->orderBy('id', 'desc');
        $booking_processes_count = $booking_processes_ids->count();
        //$booking_processes_ids = $booking_processes_ids->skip($perPage*($page-1))->take($perPage);
        $booking_processes_ids1 = $booking_processes_ids->pluck('id');

        if ($request->type) {
            $booking_processes_ids1 = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids1)
                ->where('course_type', $request->type)->pluck('booking_process_id');
            $booking_processes_count = count($booking_processes_ids1->toArray());
            $booking_processes_ids = BookingProcesses::whereIn('id', $booking_processes_ids1)->orderBy('id', 'desc');
        }

        if ($request->search) {
            $search = $request->search;

            /**For course base search */
            $courses_ids = Course::where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
                $query->orWhere('name_en', 'like', "%$search%");
            })->pluck('id');

            /**For instructor base search */
            $contact_ids = Contact::where(function ($query) use ($search) {
                $query->where('salutation', 'like', "%$search%");
                $query->orWhere('first_name', 'like', "%$search%");
                $query->orWhere('middle_name', 'like', "%$search%");
                $query->orWhere('last_name', 'like', "%$search%");
                $query->orWhere('email', 'like', "%$search%");
            })->pluck('id');

            /**For sub conatct base search */
            $sub_contact_ids = SubChildContact::where(function ($query) use ($search) {
                $query->orWhere('first_name', 'like', "%$search%");
                $query->orWhere('last_name', 'like', "%$search%");
            })->pluck('id');

            $booking_processes_courses_ids = [];
            $instructor_booking_ids = [];
            $customer_booking_ids = [];

            if (count($courses_ids)) {
                $booking_processes_courses_ids = BookingProcessCourseDetails::whereIn('course_id', $courses_ids)->pluck('booking_process_id');
            }

            if (count($contact_ids)) {
                $instructor_booking_ids = BookingProcessInstructorDetails::whereIn('contact_id', $contact_ids)->pluck('booking_process_id');
            }

            if (!count($instructor_booking_ids)) {
                $customer_booking_ids = BookingProcessCustomerDetails::whereIn('customer_id', $contact_ids)->pluck('booking_process_id');
            }

            if (!count($customer_booking_ids)) {
                $customer_booking_ids = BookingProcessCustomerDetails::whereIn('sub_child_id', $sub_contact_ids)->pluck('booking_process_id');
            }

            if (count($courses_ids) == 0 && count($contact_ids) == 0 && count($sub_contact_ids) == 0) {
                /**for booking number base search */
                $booking_processes_ids = $booking_processes_ids->where(function ($query) use ($search) {
                    $query->where('booking_number', 'like', "%$search%");
                });
                $booking_processes_count = $booking_processes_ids->count();
            }

            if (!empty($booking_processes_courses_ids)) {
                $booking_processes_courses_ids = $booking_processes_courses_ids->toArray();
            }

            if (!empty($instructor_booking_ids)) {
                $instructor_booking_ids = $instructor_booking_ids->toArray();
            }

            if (!empty($customer_booking_ids)) {
                $customer_booking_ids = $customer_booking_ids->toArray();
            }

            /**If course base and instructor base search both data are available then merge booking ids */
            $merged_bookings = array_merge(
                array_intersect($booking_processes_courses_ids, $instructor_booking_ids),
                array_diff($booking_processes_courses_ids, $instructor_booking_ids),
                array_diff($instructor_booking_ids, $booking_processes_courses_ids)
            );

            if (!$merged_bookings) {
                /**If course base and customer base search both data are available then merge booking ids */
                $merged_bookings = array_merge(
                    array_intersect($booking_processes_courses_ids, $customer_booking_ids),
                    array_diff($booking_processes_courses_ids, $customer_booking_ids),
                    array_diff($customer_booking_ids, $booking_processes_courses_ids)
                );
            }

            if ($merged_bookings) {
                $booking_processes_ids = $booking_processes_ids->whereIn('id', $merged_bookings);
                $booking_processes_count = $booking_processes_ids->count();
            }

            if ($booking_processes_ids->count() == 0) {
                $booking_processes_ids = array();
                $booking_processes_count = 0;
            }
        }

        /**Booking start date and end date base search */
        if ($request->start_date && $request->end_date) {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $date_base_booking_ids = BookingProcessCourseDetails::where(function ($query) use ($start_date, $end_date) {
                $query->whereBetween('start_date', [$start_date, $end_date]);
                $query->orWhereBetween('end_date', [$start_date, $end_date]);
            })->pluck('booking_process_id');
            $booking_processes_ids = $booking_processes_ids->whereIn('id', $date_base_booking_ids);
            $booking_processes_count = $booking_processes_ids->count();
        }

        /**Booking payment amount base search */
        if ($request->min_amount || $request->max_amount) {
            if ($request->min_amount) {
                $min_amount = $request->min_amount;
                $amount_base_booking_ids = BookingProcessPaymentDetails::where('net_price', '>=', $min_amount)->pluck('booking_process_id');
            }
            if ($request->max_amount) {
                $max_amount = $request->max_amount;
                $amount_base_booking_ids = BookingProcessPaymentDetails::where('net_price', '<=', $max_amount)->pluck('booking_process_id');
            } elseif ($request->min_amount && $request->max_amount) {
                $amount_base_booking_ids = BookingProcessPaymentDetails::where(function ($query) use ($min_amount, $max_amount) {
                    $query->whereBetween('net_price', [$min_amount, $max_amount]);
                })->pluck('booking_process_id');
            }

            $booking_processes_ids = $booking_processes_ids->whereIn('id', $amount_base_booking_ids);
            $booking_processes_count = $booking_processes_ids->count();
        }

        /**Search booking status(confirm/ draft) base */
        if (isset($request->booking_status)) {
            $booking_ids = BookingProcesses::where('is_draft', $request->booking_status)
                ->pluck('id');
            $booking_processes_ids = $booking_processes_ids->whereIn('id', $booking_ids);
            $booking_processes_count = $booking_processes_ids->count();
        }

        /**Search booking date and time base */
        if ($request->date_status) {
            $current_date = date('Y-m-d H:i:s');
            if ($request->date_status == 'Upcoming') {
                $date_status_ids = BookingProcessCourseDetails::where('StartDate_Time', '>', $current_date)
                    ->pluck('booking_process_id');
            }
            if ($request->date_status == 'Past') {
                $date_status_ids = BookingProcessCourseDetails::where('EndDate_Time', '<', $current_date)
                    ->pluck('booking_process_id');
            }
            if ($request->date_status == 'Ongoing') {
                $date_status_ids = BookingProcessCourseDetails::where('StartDate_Time', '<=', $current_date)
                    ->where('EndDate_Time', '>=', $current_date)
                    ->pluck('booking_process_id');
            }
            $booking_processes_ids = $booking_processes_ids->whereIn('id', $date_status_ids);
            $booking_processes_count = $booking_processes_ids->count();
        }

        if ($booking_processes_ids) {
            $booking_processes_ids = $booking_processes_ids->skip($perPage * ($page - 1))->take($perPage);
            $booking_processes_ids = $booking_processes_ids->pluck('id');
        } else {
            $booking_processes_ids = array();
        }

        /**Accommodation base filter */
        if($request->accommodation){
            $acc_booking_ids = BookingProcessCustomerDetails::whereIn('accommodation', $request->accommodation)->pluck('booking_process_id');
            if(!is_array($booking_processes_ids)){
                $booking_processes_ids = $booking_processes_ids->toArray();
            }
            $booking_processes_ids = array_intersect($booking_processes_ids, $acc_booking_ids->toArray());
        }
        if($request->accommodation_other){
            $acc_other_booking_ids = BookingProcessCustomerDetails::where('accommodation_other', 'like', "%$request->accommodation_other%")->pluck('booking_process_id');
            if(!is_array($booking_processes_ids)){
                $booking_processes_ids = $booking_processes_ids->toArray();
            }
            $booking_processes_ids = array_intersect($booking_processes_ids, $acc_other_booking_ids->toArray());
        }
        /** */
        $booking_processes_count = count($booking_processes_ids);
        $booking_processes = $this->bookingProcessList1($booking_processes_ids, $request->is_trash);

        $data = [
            'booking_processes' => $booking_processes,
            'count' => $booking_processes_count
        ];
        if ($request->is_export) {
            return Excel::download(new BookingProcessExport($booking_processes->toArray()), 'BookingProcesses.csv');
        }

        return $this->sendResponse(true, 'success', $data);
    }

    /* API for Booking Process List For Calender Which Instructor is not defined */
    public function bookingProcessCalenderList(Request $request)
    {
        $booking_processes_ids = BookingProcesses::query();
        $booking_processes_ids = $booking_processes_ids->orderBy('id', 'desc');
        $booking_processes_count = $booking_processes_ids->count();

        if ($request->contact_id) {
            $contact_language_ids = ContactLanguage::where('contact_id', $request->contact_id)->pluck('language_id');
            $language_id_base_booking_ids = BookingProcessLanguageDetails::whereIn('language_id', $contact_language_ids)->pluck('booking_process_id');
            $booking_processes_ids = $booking_processes_ids->whereIn('id', $language_id_base_booking_ids);
        }

        if ($booking_processes_ids) {
            $assigned_instructor_booking_ids = BookingProcessInstructorDetails::query()->pluck('booking_process_id');
            if ($assigned_instructor_booking_ids) {
                $booking_processes_ids = $booking_processes_ids->whereNotIn('id', $assigned_instructor_booking_ids);
            }
        }

        if ($booking_processes_ids) {
            $booking_processes_ids = $booking_processes_ids->pluck('id');
        } else {
            $booking_processes_ids = array();
        }

        $booking_processes = $this->bookingProcessList1($booking_processes_ids, $request->is_trash);
        $booking_processes_count = $booking_processes->count();

        $data = [
            'booking_processes' => $booking_processes,
            'count' => $booking_processes_count
        ];

        return $this->sendResponse(true, 'success', $data);
    }

    /* API for Booking Process List For Calender Based on Instructor Contact Id */
    public function bookingProcessCalenderListInstructorWise(Request $request)
    {
        $booking_processes_ids = BookingProcessInstructorDetails::query();
        $booking_processes_ids = $booking_processes_ids->orderBy('id', 'desc');
        $booking_processes_count = $booking_processes_ids->count();

        if ($request->contact_id) {
            $booking_processes_ids = $booking_processes_ids->where('contact_id', $request->contact_id);
        }

        if ($request->course_ids) {
            $course_ids = $request->course_ids;
            if (count($course_ids) > 0) {
                $bookingCourseIds = BookingProcessCourseDetails::whereIn('course_id', $course_ids)->pluck('booking_process_id');
                $booking_processes_ids = $booking_processes_ids->whereIn('booking_process_id', $bookingCourseIds);
            }
        }

        /* if ($booking_processes_ids) {
            $booking_processes_ids = $booking_processes_ids->pluck('booking_process_id');
        } else {
            $booking_processes_ids = array();
        } */
        $temp_booking_process_ids = array();

        if ($request->is_second_calender == 'true') {
            if ($request->type === 'day') {
                if ($request->date) {
                    $reques_date = $request->date;
                    //$temp_booking_process_ids = BookingProcessCourseDetails::where('start_date', $request->date)->pluck('booking_process_id');
                    $temp_booking_process_ids = BookingProcessCourseDetails::where(function ($query) use ($reques_date) {
                        $query->where('start_date', '<=', $reques_date);
                        $query->where('end_date', '>=', $reques_date);
                    })->pluck('booking_process_id');
                }
            } elseif ($request->type === 'week') {
                if ($request->date) {
                    $date = strtotime("+6 day", strtotime($request->date));
                    $start_date = $request->date;
                    $end_date = date('Y-m-d', $date);

                    $temp_booking_process_ids = BookingProcessCourseDetails::where(function ($query) use ($start_date, $end_date) {
                        //$query->where('start_date', '>=', $start_date);
                        //$query->where('end_date', '<=', $end_date);
                        $query->whereBetween('start_date', [$start_date, $end_date]);
                        $query->orWhereBetween('end_date', [$start_date, $end_date]);
                    })
                        ->pluck('booking_process_id');
                }
            }
        }

        if (!empty($temp_booking_process_ids)) {
            $booking_processes_ids = $booking_processes_ids->whereIn('booking_process_id', $temp_booking_process_ids)->pluck('booking_process_id');
        } else {
            $booking_processes_ids = $booking_processes_ids->pluck('booking_process_id');
        }

        //$booking_processes = $this->bookingProcessList1($booking_processes_ids, $request->is_trash); //Main Final Booking Code

        $booking_processes_ids = BookingProcesses::whereIn('id', $booking_processes_ids)->where('is_trash', $request->is_trash)->pluck('id');
        //New Booking Change
        $booking_processes = BookingInstructorDetailMap::whereIn('booking_process_id', $booking_processes_ids)
            ->with(['bookings.course_detail.course_data'])
            ->with([
                'bookings.customer_detail.customer',
                'bookings.customer_detail.sub_child_detail.allergies.allergy',
                'bookings.customer_detail.sub_child_detail.languages.language'
            ])
            ->with(['bookings.extra_participant_detail'])
            ->with(['bookings.instructor_detail.contact'])
            ->with([
                'bookings.payment_detail.customer' => function ($query) {
                    $query->select('id', 'salutation', 'first_name', 'last_name');
                }, 'bookings.payment_detail.payi_detail' => function ($query) {
                    $query->select('id', 'salutation', 'first_name', 'last_name');
                }, 'bookings.payment_detail.sub_child_detail.allergies.allergy',
                'bookings.payment_detail.sub_child_detail.languages.language'
            ])
            ->with(['bookings.language_detail.language'])
            ->with(['bookings.request_instructor_detail.contact'])
            ->orderBy('booking_process_id', 'desc')
            ->get();

        $booking_processes_count = $booking_processes->count();

        $data = [
            'booking_processes' => $booking_processes,
            'count' => $booking_processes_count
        ];

        return $this->sendResponse(true, 'success', $data);
    }

    /* Assign Instructor For Booking */
    public function assignInstructorBooking(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer',
            'contact_id' => 'required|integer',
        ]);

        $input['created_by'] = auth()->user()->id;

        if ($request->contact_id && $request->booking_process_id) {
            $dates_data = array();
            $booking_processes_ids_main = array();
            $leave_contact_ids_main = array();
            //CHECK AVAILIBILITY OF INSTRUCTOR
            $bookings = BookingProcessCourseDetails::Where('booking_process_id', $request->booking_process_id)->first();
            $start_date = $bookings['StartDate_Time'];
            $end_date = $bookings['EndDate_Time'];

            /* $strt_date = $bookings['start_date'];
            $ed_date = $bookings['end_date'];
            $strt_time = $bookings['start_time'];
            $ed_time = $bookings['end_time'];

            $start_date1 = explode(" ", $start_date);
            $end_date1 = explode(" ", $end_date);

            $leave_contact_ids = ContactLeave::where('start_date', ">=", $start_date1[0])
            ->where('end_date', '<=', $end_date1[0])
            ->pluck('contact_id');
            $leave_contact_ids =$leave_contact_ids->toArray(); */

            /* $booking_processes_ids = BookingProcessCourseDetails::
            Where(function($query) use($start_date,$end_date){
                $query->where('StartDate_Time', '<=', $start_date);
                $query->where('EndDate_Time', '>=', $end_date);
            })->pluck('booking_process_id'); */

            $dates_data[0]['StartDate_Time'] = $start_date;
            $dates_data[0]['EndDate_Time'] = $end_date;

            $data = $this->getAvailableInstructorListNew($dates_data);
            $booking_processes_ids_main = array_unique($data['booking_processes_ids_main']);
            $leave_contact_ids_main = array_unique($data['leave_contact_ids_main']);
            // $contacts = $contacts->whereNotIn('id',$leave_contact_ids_main);

            // $contact_ids = BookingProcessInstructorDetails::whereIn('booking_process_id',$booking_processes_ids_main)->pluck('contact_id');

            // $contacts = $contacts->whereNotIn('id',$contact_ids);
            // $booking_processes_ids = $this->getAvailableInstructorList($strt_date, $ed_date, $strt_time, $ed_time);

            if (count($booking_processes_ids_main) > 0) {
                $assigned_instructor_ids = BookingProcessInstructorDetails::whereIn('booking_process_id', $booking_processes_ids_main)->pluck('contact_id');
                $assigned_instructor_ids = $assigned_instructor_ids->toArray();
                if (in_array($request->contact_id, $assigned_instructor_ids) || in_array($request->contact_id, $leave_contact_ids_main)) {
                    return $this->sendResponse(false, __('strings.instructor_not_available_booking'));
                } else {

                    //Assign Instructor for this booking
                    $instructor_detail_inputs['created_by'] = $input['created_by'];
                    $instructor_detail_inputs['booking_process_id'] = $request->booking_process_id;
                    $instructor_detail_inputs['contact_id'] = $request->contact_id;

                    $contact_input_data['last_booking_date'] = date('Y-m-d');
                    $contact = Contact::find($request->contact_id);
                    $contact->update($contact_input_data);

                    $check_instructor_assign = BookingProcessInstructorDetails::create($instructor_detail_inputs);

                    $start_date = $bookings['start_date'];
                    $end_date = $bookings['end_date'];
                    $start_time = $bookings['start_time'];
                    $end_time = $bookings['end_time'];

                    //add in instructor booking map table
                    $booking_instructor_map['contact_id'] = $request->contact_id;
                    $booking_instructor_map['booking_process_id'] = $request->booking_process_id;
                    $this->BookingInstructorMapCreate($contact->id, $request->booking_process_id, $start_date, $end_date, $start_time, $end_time);

                    //add instructor timesheet info
                    if ($contact->user_detail) {
                        // $start_date=$bookings['start_date'];
                        // $end_date=$bookings['end_date'];
                        // $start_time = $bookings['start_time'];
                        // $end_time = $bookings['end_time'];

                        $this->InstructorTimesheetCreate($contact->user_detail->id, $request->booking_process_id, $start_date, $end_date, $start_time, $end_time);
                    }

                    /**For if instructor has block with booking dates then release the blocks */
                    $datetime1 = new DateTime($start_date);
                    $datetime2 = new DateTime($end_date);

                    $interval = $datetime1->diff($datetime2);
                    $days = $interval->format('%a');
                    for ($i = 0; $i <= $days; $i++) {
                        $this->checkInstructorBlockExist($start_date, $start_time, $end_time, $request->contact_id);
                        $start_date = date('Y-m-d', strtotime($start_date . ' + 1 days'));
                    }
                    /** */

                    if ($check_instructor_assign) {
                        $instructor_ids = BookingProcessInstructorDetails::where('booking_process_id', $request->booking_process_id)->pluck('contact_id');
                    }
                    $instructor_data = Contact::whereIn('id', $instructor_ids)->select('salutation', 'first_name', 'last_name')->get()->toArray();
                    if ($instructor_data) {
                        /**For Customer push notifications */
                        $course_id = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first()->course_id;
                        $booking_processes_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->pluck('customer_id');
                        $total_instructor = count($instructor_data);
                        $data['course_id'] = $course_id;
                        $course = Course::find($data['course_id']);
                        $data['course_name'] = $course->name;
                        $data['booking_processes_id'] = $request->booking_process_id;
                        //$data['instructor_data'] = $instructor_data;
                        $title = "Your Course have Assign New Instructor";
                        $body = "Your Course: " . $course->name . " have Assign " . $total_instructor . " New Instructor";
                        $this->setPushNotificationData($booking_processes_customer_detail, 4, $data, $title, $body, $request->booking_process_id);
                        /**End customer push_notification */

                        /**For instructor push notification and update email */
                        $res = $this->sendInstructoreUpdates($request->contact_id, $request->booking_process_id);
                        /**End instructor push notification/email */
                    }

                    //Update chatroom for openfire
                    UpdateChatRoom::dispatch(false, $request->booking_process_id);
                    return $this->sendResponse(true, __('strings.assign_instructor_success'));
                }
            }
        }
    }

    //APi for application
    public function bookingProcessListNew(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
        $userId = auth()->user()->contact_id;
        $my_courses = array();
        $feature_course = array();
        $booking_processes_ids = BookingProcessCustomerDetails::query();
        $booking_processes_ids = $booking_processes_ids->where('customer_id', $userId);
        $booking_processes_ids = $booking_processes_ids->orderBy('id', 'desc');
        $booking_processes_ids = $booking_processes_ids->skip($perPage * ($page - 1))->take($perPage);

        //$booking_processes_count = $booking_processes_ids->count();

        /*  $page = $request->page;
         $perPage = $request->perPage;
         $booking_processes_ids = BookingProcesses::query();
         $booking_processes_ids = $booking_processes_ids->orderBy('id','desc');
         $booking_processes_count = $booking_processes_ids->count();
         $booking_processes_ids = $booking_processes_ids->skip($perPage*($page-1))->take($perPage); */

        if ($booking_processes_ids) {
            $booking_processes_ids = $booking_processes_ids->pluck('booking_process_id');
        } else {
            $booking_processes_ids = array();
        }

        $booking_processes = BookingProcesses::whereIn('id', $booking_processes_ids)
            ->with(['course_detail.course_data'])
            ->with(['customer_detail.customer', 'customer_detail.sub_child_detail.allergies.allergy', 'sub_child_detail.languages.language'])
            ->with(['extra_participant_detail'])
            ->with(['instructor_detail.contact'])
            ->with(['payment_detail.sub_child_detail.allergies.allergy', 'payment_detail.sub_child_detail.languages.language'])
            ->with(['language_detail.language'])
            ->orderBy('id', 'desc')
            ->get()
            ->sortByDesc('course_detail.date_status_id')->values();;

        $data = [
            'booking_processes' => $booking_processes,
            //'count' => $booking_processes_count
        ];

        return $this->sendResponse(true, 'success', $data);
    }

    /* API for View Booking Process */
    public function viewBookingProcess($id)
    {
        $booking_processes = BookingProcesses::with(['course_detail.course_data', 'course_detail.contact_data.subcategory_detail.category_detail', 'course_detail.lead_data', 'course_detail.sourse_data', 'course_detail.difficulty_level_detail'])
            ->with(['customer_detail.payi_detail.category_detail', 'customer_detail.payi_detail.subcategory_detail', 'customer_detail.course_detail_data.course_data.teaching_material_detail.teaching_material_data.teaching_material_category_detail', 'customer_detail.customer.allergies.allergy', 'customer_detail.accommodation_data.category_detail', 'customer_detail.season_ticket_details', 'customer_detail.cancell_details', 'customer_detail.sub_child_detail.allergies.allergy', 'customer_detail.sub_child_detail.languages.language'])
            ->with(['extra_participant_detail'])
            ->with(['instructor_detail.contact.languages.language'])
            ->with(['request_instructor_detail.contact'])
            ->with(['booking_instructor_map_detail'])
            ->with([
                'payment_detail.payi_detail', 'payment_detail.payment_deta', 'payment_detail.customer.allergies.allergy', 'payment_detail.payment_detail.concardis_transaction', 'payment_detail.voucher', 'payment_detail.season_ticket_details', 'payment_detail.cancell_details', 'payment_detail.sub_child_detail.allergies.allergy',
                'payment_detail.sub_child_detail.languages.language'
            ])
            ->with(['language_detail.language'])
            ->with(['getRequest.contact'])
            ->find($id);

        if (!$booking_processes) {
            return $this->sendResponse(false, __('strings.booking_not_found'));
        }

        if ($booking_processes->QR_number) {
            $url = url('/') . '/bookingProcessQr/' . $booking_processes->QR_number;
            $qr_code = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $url . "&choe=UTF-8";
            $booking_processes->qr_code = $qr_code;
        }
        return $this->sendResponse(true, 'success', $booking_processes);
    }


    /* API for Booking Process Source List */
    public function bookingProcessSourceList(Request $request)
    {
        $source = BookingProcessSource::where('type', 'CRM')->get();
        return $this->sendResponse(true, 'success', $source);
    }

    /* API for Updating Contact information */
    public function updateContact(Request $request)
    {
        $v = validator($request->all(), [
            'contact_id' => 'required|integer|min:1',
            'contact_allergies' => 'array',
            'comment' => 'max:512',
            'cal_payment_type' => 'nullable|in:PH,PD,PIS',
            'is_include_lunch_hour' => 'nullable|boolean'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $checkContact = Contact::find($request->contact_id);
        if (!$checkContact) {
            return $this->sendResponse(false, __('strings.contact_not_found'));
        }
        if ($request->comment) {
            $checkContact->addition_comments = $request->comment;
            $checkContact->save();
        }
        ContactAllergy::where('contact_id', $request->contact_id)->delete();
        if ($request->contact_allergies) {
            foreach ($request->contact_allergies as $allergy) {
                ContactAllergy::create([
                    'contact_id' => $request->contact_id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        /**If booking id available then apply below code */
        if ($request->booking_process_id) {
            $checkCustomerStatus = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->where('customer_id', $request->contact_id)->orderBy('id', 'desc')->first();

            if ($checkCustomerStatus) {
                $is_new_invoice = $checkCustomerStatus->is_new_invoice;
            } else {
                $is_new_invoice = 0;
            }

            $checkPaymentStatus = BookingProcessPaymentDetails::where('booking_process_id', $request->booking_process_id)->where('customer_id', $request->contact_id)->where('is_new_invoice', $is_new_invoice)->orderBy('id', 'desc')->first();

            /**If course type Other */
            if ($request->cal_payment_type === 'PIS') {
                $this->updateOtherCourseDetails($request);
                return $this->sendResponse(true, __('strings.contact_updated_success'));
            }
            /**End */

            $other_customer_lunch = BookingProcessCustomerDetails::where('is_include_lunch_hour', true)
                ->where('booking_process_id', $request->booking_process_id)
                ->where('customer_id', '!=', $request->contact_id)
                ->count();

            if ($checkPaymentStatus->status == 'Pending') {
                $updateContact = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->where('customer_id', $request->contact_id)->orderBy('id', 'desc')->first();
                $updateContactDetail['StartDate_Time'] = $request->StartDate_Time;
                $updateContactDetail['EndDate_Time'] = $request->EndDate_Time;
                $tempStrt1 = explode(" ", $request->StartDate_Time);
                $tempEnd1 = explode(" ", $request->EndDate_Time);
                $updateContactDetail['start_date'] = $tempStrt1[0];
                $updateContactDetail['start_time'] = $tempStrt1[1];
                $updateContactDetail['end_date'] = $tempEnd1[0];
                $updateContactDetail['end_time'] = $tempEnd1[1];

                $updateContactDetail['no_of_days'] = $request->no_of_days;
                $updateContactDetail['hours_per_day'] = $request->hours_per_day;
                $updateContactDetail['accommodation'] = $request->accommodation;
                $updateContactDetail['is_updated'] = 1;

                $is_refund = 0;
                $refund_payment = 0;
                $updateCourseDetail = array();
                // if ($request->EndDate_Time<$updateContact->EndDate_Time) {
                //     $is_refund = 1;
                // }

                $courseDetails = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();

                // dd($request->StartDate_Time,$updateContact->StartDate_Time,$request->EndDate_Time,$updateContact->EndDate_Time);

                if ($request->StartDate_Time < $courseDetails->StartDate_Time) {
                    $updateCourseDetail['StartDate_Time'] = $request->StartDate_Time;
                    $tempStrt = explode(" ", $request->StartDate_Time);
                    $updateCourseDetail['start_date'] = $tempStrt[0];
                    $updateCourseDetail['start_time'] = $tempStrt[1];
                } else {
                    $checkStartDateExist = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->where('StartDate_Time', '<', $request->StartDate_Time)->orderBy('id', 'desc')
                        ->where('customer_id', '!=', $request->contact_id)->first();

                    if (!$checkStartDateExist) {
                        $updateCourseDetail['StartDate_Time'] = $request->StartDate_Time;
                        $tempStrt = explode(" ", $request->StartDate_Time);
                        $updateCourseDetail['start_date'] = $tempStrt[0];
                        $updateCourseDetail['start_time'] = $tempStrt[1];
                    }
                }
                // elseif ($request->EndDate_Time>$courseDetails->EndDate_Time) {
                $updateCourseDetail['EndDate_Time'] = $request->EndDate_Time;
                $tempEnd = explode(" ", $request->EndDate_Time);
                $updateCourseDetail['end_date'] = $tempEnd[0];
                $updateCourseDetail['end_time'] = $tempEnd[1];
                // }

                /**Lunch hour related */
                $datetime1 = new DateTime($updateCourseDetail['StartDate_Time']);
                $datetime2 = new DateTime($updateCourseDetail['EndDate_Time']);

                $interval = $datetime2->diff($datetime1);
                $updateCourseDetail['total_days'] = $interval->format('%d') + 1; //Day -1 calucalte count so
                $updateCourseDetail['total_hours'] = $interval->format('%h');
                if ($request->is_include_lunch_hour && $courseDetails->course_type === 'Group') {
                    //$updateCourseDetail['lunch_hour'] = $request->lunch_hour;
                    $updateCourseDetail['lunch_hour'] = 1;
                } else {
                    if (($request->lunch_hour) && ($request->lunch_start_time)) {
                        $lunch_hour =  $request->lunch_hour;
                        $lunch_start_time = $request->lunch_start_time;
                        $lunch_end_time = $this->getLunchEndTime($lunch_hour, $lunch_start_time);
                        $updateCourseDetail['lunch_end_time'] = $lunch_end_time;
                        $updateCourseDetail['lunch_hour'] =  $request->lunch_hour;
                        $updateCourseDetail['lunch_start_time'] = $request->lunch_start_time;
                    }
                }
                /**End */
                if (!empty($updateCourseDetail)) {
                    $courseDetails->update($updateCourseDetail);
                }

                $updatePayment = BookingProcessPaymentDetails::where('booking_process_id', $request->booking_process_id)->where('customer_id', $request->contact_id)->where('is_new_invoice', $is_new_invoice)->orderBy('id', 'desc')->first();
                $discount = $updatePayment->discount;

                $course_detail_data = CourseDetail::find($request->course_detail_id);
                $price_per_day = $course_detail_data['price_per_day'];
                $include_lunch_price = 0;
                $is_include_lunch = $request->is_include_lunch;

                $updateContactDetail['is_include_lunch'] = 0;
                $updateContactDetail['include_lunch_price'] = 0;

                $updateContactDetail['course_detail_id'] = $request->course_detail_id;
                if ($request->is_include_lunch) {
                    $updateContactDetail['is_include_lunch'] = $course_detail_data['is_include_lunch'];
                    $updateContactDetail['include_lunch_price'] = $course_detail_data['include_lunch_price'];
                    $include_lunch_price = $course_detail_data['include_lunch_price'];
                }
                if ($request->is_include_lunch_hour) {
                    $updateContactDetail['is_include_lunch_hour'] = true;
                } else {
                    $updateContactDetail['is_include_lunch_hour'] = false;
                }
                $updateSucess = $updateContact->update($updateContactDetail);

                $total_price =  $price_per_day;
                // $update_payment_detail['total_price'] = $total_price;

                /* $update_payment_detail['customer_id'] = $payment_detail_update['customer_id'];
                $update_payment_detail['payment_method_id'] = $payment_detail_update['payment_method_id']; */

                $course_detail = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();

                $start_date = strtotime($tempStrt1[0]);
                $end_date = strtotime($tempEnd1[0]);
                $datediff = $end_date - $start_date;
                $no_days = round($datediff / (60 * 60 * 24));
                $no_days = $no_days + 1;

                $datetime1 = new DateTime($request->StartDate_Time);
                $datetime2 = new DateTime($request->EndDate_Time);

                $interval = $datetime2->diff($datetime1);
                $hours_per_day = $interval->format('%h');
                if ($interval->format('%i') >= 45) {
                    $hours_per_day = $hours_per_day + 1;
                }

                $vat_percentage = $this->getVat();
                $lvat_percentage = $this->getLunchVat();

                // calculate days and hours wise total price
                $course_total_price = 0;
                if ($request->cal_payment_type === 'PH') {

                    /**For if included lunch hour */
                    if ($request->is_include_lunch_hour) {
                        $hours_per_day = $hours_per_day - 1;
                    }

                    $total_bookings_days = $no_days;
                    // $course_total_price = ($price_per_day * ($total_bookings_days * $hours_per_day));
                    $course_total_price = ($total_bookings_days * ($price_per_day * $hours_per_day));
                } else {
                    $total_bookings_days = $request->no_of_days;
                    // if ($total_bookings_days >= $request->no_of_days) {
                    $course_total_price = ($total_bookings_days * $price_per_day);
                    // } else {
                    // $course_total_price= $price_per_day;
                    // }
                }

                /**Add settlement amount in main price */
                if ($updatePayment->settlement_amount) {
                    $settlement_amount = $updatePayment->settlement_amount;
                    $course_total_price = $course_total_price + $settlement_amount;
                }
                /**End */
                $excluding_vat_amount = 0;
                if ($course_total_price && $vat_percentage) {
                    $excluding_vat_amount =
                        $course_total_price / ((100 + $vat_percentage) / 100);
                }

                //vat amount calculation
                $vat_amount = 0;
                if ($course_total_price && $excluding_vat_amount) {
                    $vat_amount = $course_total_price - $excluding_vat_amount;
                }

                //Calculate Lunch Total Price Based On Date
                $total_lunch_price = 0;

                //excluding lunch vat price
                $lunch_vat_excluded_amount = 0;
                if ($include_lunch_price && $request->is_include_lunch && $lvat_percentage) {
                    $total_lunch_price = $include_lunch_price * $total_bookings_days;
                    $lunch_vat_excluded_amount =
                        ($include_lunch_price * $total_bookings_days) / ((100 + $lvat_percentage) / 100);
                }

                //vat amount calculation lunch
                $lunch_vat_amount = 0;
                if ($include_lunch_price && $request->is_include_lunch && $lunch_vat_excluded_amount) {
                    $lunch_vat_amount = ($include_lunch_price * $total_bookings_days) - $lunch_vat_excluded_amount;
                }

                //Calculate Net Price
                $netPrise = $course_total_price;

                if ($course_detail->course_type === 'Private') {
                    /**Code comment for new requirement
                     * Date : 23-07-2020
                     */
                    // $extra_participant = BookingProcessExtraParticipant::where('booking_process_id', $request->booking_process_id)->count();

                    $booking_course_data = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();

                    $extra_person_charge = 0;
                    if ($booking_course_data->is_extra_participant) {
                        $extra_person_charge = $course_detail_data['extra_person_charge'];
                    }

                    // if ($extra_participant) {
                    //     $extra_person_charge = $course_detail_data['extra_person_charge'];
                    // }

                    /**If no of extra participant available */
                    $total_participant = 0;
                    if ($booking_course_data->no_of_extra_participant) {
                        $total_participant = $booking_course_data->no_of_extra_participant;
                    }

                    // $update_payment_detail['extra_participant'] = $extra_participant * $extra_person_charge;
                    $update_payment_detail['extra_participant'] = $total_participant * $extra_person_charge;

                    $total_price1 = $course_total_price + ($total_participant * $extra_person_charge);
                    $netPrise = $total_price1 - ($total_price1 * ($discount / 100));
                } else {
                    $netPrise = $course_total_price - ($total_price * ($discount / 100));
                }

                if ($request->is_include_lunch) {
                    $netPrise = $netPrise + $total_lunch_price;
                }

                $total_payed_amount = InvoicePaymentHistory::where('invoice_id', $updatePayment->id)->sum('amount');
                $outstanding_amount = $netPrise - $total_payed_amount;

                // $update_payment_detail['net_price'] = $netPrise;
                $update_payment_detail['no_of_days'] = $no_days;
                $update_payment_detail['hours_per_day'] = $request->hours_per_day;

                $update_payment_detail['total_price'] = $course_total_price;
                $update_payment_detail['net_price'] = $netPrise;
                $update_payment_detail['outstanding_amount'] = $outstanding_amount;
                $update_payment_detail['vat_excluded_amount'] = $excluding_vat_amount;
                $update_payment_detail['vat_amount'] = $vat_amount;
                $update_payment_detail['vat_percentage'] = $vat_percentage;
                $update_payment_detail['lunch_vat_excluded_amount'] = $lunch_vat_excluded_amount;
                $update_payment_detail['lunch_vat_amount'] = $lunch_vat_amount;
                $update_payment_detail['lunch_vat_percentage'] = $lvat_percentage;

                $update_payment_detail['is_include_lunch'] = 0;
                $update_payment_detail['include_lunch_price'] = 0;

                if ($request->is_include_lunch) {
                    $update_payment_detail['is_include_lunch'] = $is_include_lunch;
                    $update_payment_detail['include_lunch_price'] = $total_lunch_price;
                }

                // if ($is_refund==1) {
                //     $refund_payment = $updatePayment->net_price-$netPrise;
                // }

                // $update_payment_detail['refund_payment'] = $refund_payment;

                $updatePaymentSucess = $updatePayment->update($update_payment_detail);
                $invoice_link = $this->updateInvoiceLink($updatePayment->id);
            } elseif ($checkPaymentStatus->status == 'Success') {
                $input['created_by'] = auth()->user()->id;

                $updateContact = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->where('customer_id', $request->contact_id)->first();

                $course_data = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();

                $is_refund = 0;
                $refund_payment = 0;

                if ($request->EndDate_Time < $updateContact->EndDate_Time) {
                    $is_refund = 1;
                    $new_start_date = date("Y-m-d H:i:s", strtotime($updateContact->start_date));
                    $addContactDetail['StartDate_Time'] = $updateContact->StartDate_Time;
                } else {
                    $start_date = $updateContact->end_date . ' ' . $updateContact->start_time;
                    $start_date = strtotime("1 day", strtotime($start_date));
                    $new_start_date = date("Y-m-d H:i:s", $start_date);
                    $addContactDetail['StartDate_Time'] = $new_start_date;
                }

                $addContactDetail['booking_process_id'] = $request->booking_process_id;
                $addContactDetail['customer_id'] = $request->contact_id;
                $addContactDetail['is_payi'] = $updateContact->is_payi;
                $addContactDetail['payi_id'] = $updateContact->payi_id;
                $addContactDetail['EndDate_Time'] = $request->EndDate_Time;
                $addContactDetail['hours_per_day'] = $request->hours_per_day;
                $addContactDetail['accommodation'] = $request->accommodation;
                $addContactDetail['cal_payment_type'] = $request->cal_payment_type;
                $addContactDetail['is_new_invoice'] = 1;
                $addContactDetail['created_by'] = $input['created_by'];
                $addContactDetail['created_at'] = date("Y-m-d H:i:s");
                $addContactDetail['is_updated'] = 1;
                $addContactDetail['QR_number'] = ($updateContact->QR_number) ? $updateContact->QR_number : $request->booking_process_id . $request->contact_id . mt_rand(100000, 999999);

                $addContactDetail['is_include_lunch'] = $request->is_include_lunch;
                $tempStrt1 = explode(" ", $addContactDetail['StartDate_Time']);
                $tempEnd1 = explode(" ", $request->EndDate_Time);
                $addContactDetail['start_date'] = $tempStrt1[0];
                $addContactDetail['start_time'] = $tempStrt1[1];
                $addContactDetail['end_date'] = $tempEnd1[0];
                $addContactDetail['end_time'] = $tempEnd1[1];

                // if($tempStartDate==''){
                //$updateSucess = $updateContact->update($updateContactDetail);
                //dd($addContactDetail);
                $updatePayment = BookingProcessPaymentDetails::where('booking_process_id', $request->booking_process_id)->where('customer_id', $request->contact_id)->where('is_new_invoice', $is_new_invoice)->first();

                $discount = ($updatePayment) ? $updatePayment->discount : 0;

                $tempStrt1 = explode(" ", $new_start_date);
                $tempEnd1 = explode(" ", $request->EndDate_Time);
                $start_date = $tempStrt1[0];
                $start_time = $tempStrt1[1];
                $end_date = $tempEnd1[0];
                $end_time = $tempEnd1[1];

                /**For get season and daytime base startdate, enddate, startime, endtime */
                $data = $this->getSeasonDaytime($start_date, $end_date, $start_time, $end_time);

                $start_date = strtotime($start_date);
                $end_date = strtotime($end_date);

                $datediff = $end_date - $start_date;
                $no_days = round($datediff / (60 * 60 * 24));
                $no_days = $no_days + 1;
                $addContactDetail['no_of_days'] = $no_days;

                /* $course_detail_data = CourseDetail::where('cal_payment_type', $request->cal_payment_type)
                ->where('no_of_days', $no_days)
                ->where('course_id', $course_data->course_id)->first(); */

                if ($request->cal_payment_type === 'PD') {
                    $course_detail_data = CourseDetail::where('session', $data['season'])
                        ->where('time', $data['daytime'])
                        ->where('cal_payment_type', $request->cal_payment_type)
                        ->where('no_of_days', $no_days)
                        ->where('course_id', $course_data->course_id)
                        ->first();

                    if (empty($course_detail_data)) {
                        $course_detail_data = $this->checkCourseForDays($request->cal_payment_type, $no_days, $course_data->course_id, $data['season'], $data['daytime']);
                    }
                } elseif ($request->cal_payment_type === 'PH') {
                    $course_detail_data = CourseDetail::where('session', $data['season'])
                        ->where('time', $data['daytime'])
                        ->where('cal_payment_type', $request->cal_payment_type)
                        ->where('hours_per_day', $request->hours_per_day)
                        ->where('course_id', $course_data->course_id)->first();

                    if (empty($course_detail_data)) {
                        $course_detail_data = $this->checkCourseForHours($request->cal_payment_type, $request->hours_per_day, $course_data->course_id, $data['season'], $data['daytime']);
                    }
                }

                if (!$course_detail_data) {
                    return $this->sendResponse(false, __('strings.course_detail_data_not_found'));
                }

                // $course_detail_data = CourseDetail::find($request->course_detail_id);
                $price_per_day = $course_detail_data['price_per_day'];
                $is_include_lunch = $request->is_include_lunch;
                $include_lunch_price = 0;

                $addContactDetail['is_include_lunch'] = 0;
                $addContactDetail['include_lunch_price'] = 0;

                $addContactDetail['course_detail_id'] = $course_detail_data['id'];
                if ($request->is_include_lunch) {
                    $addContactDetail['is_include_lunch'] = $is_include_lunch;
                    $include_lunch_price = $course_detail_data['include_lunch_price'];
                    $addContactDetail['include_lunch_price'] = $include_lunch_price;
                }

                if ($request->is_include_lunch_hour) {
                    $addContactDetail['is_include_lunch_hour'] = true;
                } else {
                    $addContactDetail['is_include_lunch_hour'] = false;
                }

                $total_price =  $price_per_day;

                $add_payment_detail['booking_process_id'] = $request->booking_process_id;
                $add_payment_detail['total_price'] = $total_price;
                // $add_payment_detail['booking_process_customer_detail_id'] = $addCustomer->id;
                $add_payment_detail['course_detail_id'] = $request->course_detail_id;
                /* if ($request->is_pay==1) {
                    $add_payment_detail['status'] = "Success";
                } else {
                    $add_payment_detail['status'] = "Pending";
                } */
                $add_payment_detail['customer_id'] = $request->contact_id;

                /* $add_payment_detail['no_of_days'] = $request->no_of_days;
                $add_payment_detail['hours_per_day'] = $request->hours_per_day; */
                $add_payment_detail['accommodation'] = $request->accommodation;
                /* $update_payment_detail['customer_id'] = $payment_detail_update['customer_id'];
                $update_payment_detail['payment_method_id'] = $payment_detail_update['payment_method_id']; */

                $course_detail = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();

                /* $start_date = strtotime($course_detail->start_date);
                $end_date = strtotime($course_detail->end_date);
                $datediff = $end_date - $start_date;
                $no_days = round($datediff / (60 * 60 * 24)); */

                $vat_percentage = $this->getVat();
                $lvat_percentage = $this->getLunchVat();

                // calculate days and hours wise total price
                $total_bookings_days = $no_days;
                $course_total_price = 0;
                //$course_total_price= ($price_per_day * $total_bookings_days);

                if ($request->cal_payment_type === 'PH') {
                    /**For if included lunch hour */
                    $deducted_hours = $request->hours_per_day;
                    if ($request->is_include_lunch_hour) {
                        $deducted_hours = $request->hours_per_day - 1;
                    }
                    $course_total_price = ($total_bookings_days * ($price_per_day * $deducted_hours));
                } else {
                    $course_total_price = ($price_per_day * $total_bookings_days);
                }

                /**Add settlement amount in main price */
                if ($updatePayment->settlement_amount) {
                    $settlement_amount = $updatePayment->settlement_amount;
                    $course_total_price = $course_total_price + $settlement_amount;
                }

                $excluding_vat_amount = 0;
                if ($course_total_price && $vat_percentage) {
                    $excluding_vat_amount =
                        $course_total_price / ((100 + $vat_percentage) / 100);
                }

                //vat amount calculation
                $vat_amount = 0;
                if ($course_total_price && $excluding_vat_amount) {
                    $vat_amount = $course_total_price - $excluding_vat_amount;
                }

                //Calculate Lunch Total Price Based On Date
                $total_lunch_price = 0;
                //excluding lunch vat price
                $lunch_vat_excluded_amount = 0;
                if ($include_lunch_price && $request->is_include_lunch && $lvat_percentage) {
                    $total_lunch_price = $include_lunch_price * $total_bookings_days;
                    $lunch_vat_excluded_amount =
                        ($include_lunch_price * $total_bookings_days) / ((100 + $lvat_percentage) / 100);
                }

                //vat amount calculation lunch
                $lunch_vat_amount = 0;
                if ($include_lunch_price && $request->is_include_lunch && $lunch_vat_excluded_amount) {
                    $lunch_vat_amount = ($include_lunch_price * $total_bookings_days) - $lunch_vat_excluded_amount;
                }

                //Calculate Net Price
                $netPrise = $course_total_price;
                //$discount = $request->discount;

                if ($course_detail->course_type === 'Private') {
                    /**Code comment for new requirement
                     * Date : 23-07-2020
                     */
                    // $extra_participant = BookingProcessExtraParticipant::where('booking_process_id', $request->booking_process_id)->count();

                    $booking_course_data = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();

                    // if ($extra_participant) {
                    //     $extra_person_charge = $course_detail_data['extra_person_charge'];
                    // }

                    $extra_person_charge = 0;
                    if ($booking_course_data->is_extra_participant) {
                        $extra_person_charge = $course_detail_data['extra_person_charge'];
                    }

                    /**If no of extra participant available */
                    $total_participant = 0;
                    if ($booking_course_data->no_of_extra_participant) {
                        $total_participant = $booking_course_data->no_of_extra_participant;
                    }

                    $update_payment_detail['extra_participant'] = $total_participant * $extra_person_charge;

                    // $update_payment_detail['extra_participant'] = $extra_participant * $extra_person_charge;
                    $total_price1 = $course_total_price + ($total_participant * $extra_person_charge);
                    $netPrise = $total_price1 - ($total_price1 * ($discount / 100));
                } else {
                    $netPrise = $course_total_price - ($total_price * ($discount / 100));
                }

                if ($request->is_include_lunch) {
                    $netPrise = $netPrise + $total_lunch_price;
                }

                // $update_payment_detail['net_price'] = $netPrise;

                $add_payment_detail['total_price'] = $course_total_price;
                $add_payment_detail['vat_excluded_amount'] = $excluding_vat_amount;
                $add_payment_detail['vat_amount'] = $vat_amount;
                $add_payment_detail['vat_percentage'] = $vat_percentage;
                $add_payment_detail['lunch_vat_excluded_amount'] = $lunch_vat_excluded_amount;
                $add_payment_detail['lunch_vat_amount'] = $lunch_vat_amount;
                $add_payment_detail['lunch_vat_percentage'] = $lvat_percentage;

                if ($is_refund == 1) {
                    $refund_payment = $updatePayment->net_price - $netPrise;
                }

                if ($request->StartDate_Time < $course_detail->StartDate_Time) {
                    $updateCourseDetail['StartDate_Time'] = $request->StartDate_Time;
                } else {
                    $checkStartDateExist = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->where('StartDate_Time', '<', $request->StartDate_Time)->orderBy('id', 'desc')->first();

                    if (!$checkStartDateExist) {
                        $updateCourseDetail['StartDate_Time'] = $request->StartDate_Time;
                        $tempStrt = explode(" ", $request->StartDate_Time);
                        $updateCourseDetail['start_date'] = $tempStrt[0];
                        $updateCourseDetail['start_time'] = $tempStrt[1];
                    }
                }

                if ($request->EndDate_Time > $course_detail->EndDate_Time) {
                    $updateCourseDetail['EndDate_Time'] = $request->EndDate_Time;
                }

                /**Lunch hour related */
                $datetime1 = new DateTime($updateCourseDetail['StartDate_Time']);
                $datetime2 = new DateTime($updateCourseDetail['EndDate_Time']);

                $interval = $datetime2->diff($datetime1);
                $updateCourseDetail['total_days'] = $interval->format('%d') + 1; //Day -1 calucalte count so
                $updateCourseDetail['total_hours'] = $interval->format('%h');

                if (($request->is_include_lunch_hour) && ($course_detail->course_type === 'Group')) {
                    $updateCourseDetail['lunch_hour'] = 1; //Lunch hour is fix 1 hour
                } else {
                    if (($request->lunch_hour) && ($request->lunch_start_time)) {
                        $lunch_hour =  $request->lunch_hour;
                        $lunch_start_time = $request->lunch_start_time;
                        $lunch_end_time = $this->getLunchEndTime($lunch_hour, $lunch_start_time);
                        $updateCourseDetail['lunch_end_time'] = $lunch_end_time;
                        $updateCourseDetail['lunch_hour'] =   $request->lunch_hour;
                        $updateCourseDetail['lunch_start_time'] =  $request->lunch_start_time;
                    }
                }
                /**End */

                if (!empty($updateCourseDetail)) {
                    $courseDetails = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();
                    $courseDetails->update($updateCourseDetail);
                }

                $total_payed_amount = InvoicePaymentHistory::where('invoice_id', $updatePayment->id)->sum('amount');
                $outstanding_amount = $netPrise - $total_payed_amount;

                $add_payment_detail['refund_payment'] = $refund_payment;
                $add_payment_detail['price_per_day'] = $price_per_day;
                $add_payment_detail['net_price'] = $netPrise;
                $add_payment_detail['outstanding_amount	'] = $outstanding_amount;
                $add_payment_detail['no_of_days'] = $no_days;
                $add_payment_detail['discount'] = $discount;
                $add_payment_detail['payi_id'] = $updateContact->payi_id;
                $add_payment_detail['is_new_invoice'] = 1;
                $add_payment_detail['hours_per_day'] = $request->hours_per_day;

                $add_payment_detail['is_include_lunch'] = 0;
                $add_payment_detail['include_lunch_price'] = 0;

                if ($request->is_include_lunch) {
                    $add_payment_detail['is_include_lunch'] = $is_include_lunch;
                    $add_payment_detail['include_lunch_price'] = $total_lunch_price;
                }
                $add_payment_detail['created_by'] = $input['created_by'];

                $invoice_number = SequenceMaster::where('code', 'INV')->first();

                if ($invoice_number) {
                    $input['invoice_number'] = $invoice_number->sequence;
                    $add_payment_detail['invoice_number'] = "INV" . date("m") . "" . date("Y") . $input['invoice_number'];
                    $invoice_number->update(['sequence' => $invoice_number->sequence + 1]);
                }

                /* $vat_percentage = $this->getVat();
                $vat_excluded_amount = $netPrise/((100+$vat_percentage)/100);
                $vat_amount = $netPrise - $vat_excluded_amount;
                $add_payment_detail['vat_percentage'] = $vat_percentage;
                $add_payment_detail['vat_amount'] = $vat_amount;
                $add_payment_detail['vat_excluded_amount'] = $vat_excluded_amount; */

                if ($refund_payment) {
                    $updateContact = $updateContact->update($addContactDetail);
                    $updatePaymentSucess = $updatePayment->update($add_payment_detail);
                    $invoice_link = $this->updateInvoiceLink($updatePayment->id);
                } else {
                    BookingProcessCustomerDetails::create($addContactDetail);
                    $bookingPayment = BookingProcessPaymentDetails::create($add_payment_detail);
                    if ($bookingPayment) {
                        $invoice_link = $this->updateInvoiceLink($bookingPayment->id);
                    }
                }
            }
            if ($request->booking_process_id) {
                $emails_sent = $this->customer_update_booking($request->booking_process_id);

                BookingInstructorDetailMap::where('booking_process_id', $request->booking_process_id)->delete(); //Remove Instructor Booking Map Detail
                $booking_course = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();
                $instructor_details = BookingProcessInstructorDetails::where('booking_process_id', $request->booking_process_id)->get();

                foreach ($instructor_details as $instructor) {
                    $instructor_id = $instructor->contact_id;
                    $start_date = $booking_course->start_date;
                    $end_date = $booking_course->end_date;
                    $start_time = $booking_course->start_time;
                    $end_time = $booking_course->end_time;
                    $this->BookingInstructorMapCreate($instructor_id, $request->booking_process_id, $start_date, $end_date, $start_time, $end_time);
                }
            }

            //Update chatroom for openfire
            UpdateChatRoom::dispatch(false, $request->booking_process_id);
        }
        /**End */
        return $this->sendResponse(true, __('strings.contact_updated_success'));
    }


    /* API for Updating Contact Basic information During the Create Contact */
    public function updateContactDetail(Request $request, $id)
    {
        $v = validator($request->all(), [
            'mobile1' => 'max:25',
            'mobile2' => 'max:25',
            'dob' => 'nullable|date_format:Y-m-d',
            'email' => 'required|email',
            'skiing_level' => 'nullable|in:Kinderland,Unknown,Green,Blue,Red,Black',
            'gender' => 'in:M,F,O',
            'accomodation' => 'nullable',
            'allergies' => 'nullable|array',
            'allergies.*' => 'exists:allergies,id',
            'languages' => 'required|array',
            'languages.*' => 'exists:languages,id',
            'accommodation_id' => 'nullable|integer'
        ], [
            'dob.date_format' => __('validation.dob_invalid_formate'),
            'allergies.*.exists' =>  __('strings.exist_validation', ['name' => 'allergies']),
            'languages.*.exists' =>  __('strings.exist_validation', ['name' => 'languages'])
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        //Check Email Already Exits or not
        $checkEmail = Contact::where('id', '!=', $id)->where('email', $request->email)->count();
        if ($checkEmail) return $this->sendResponse(false, __('strings.email_already_taken'));

        $contact = Contact::find($id);
        if (!$contact) return $this->sendResponse(false, __('strings.contact_not_found'));

        //get all contact request detail
        $input = $request->all();
        $input['updated_by'] = auth()->user()->id;

        //update contact detail
        $contact->update($input);

        if ($request->allergies) {
            ContactAllergy::where('contact_id', $id)->delete();
            foreach ($request->allergies as $allergy) {
                ContactAllergy::create([
                    'contact_id' => $id,
                    'allergy_id' => $allergy
                ]);
            }
        }

        if ($request->languages) {
            ContactLanguage::where('contact_id', $id)->delete();
            foreach ($request->languages as $language) {
                ContactLanguage::create([
                    'contact_id' => $id,
                    'language_id' => $language
                ]);
            }
        }

        return $this->sendResponse(true, __('strings.contact_updated_success'));
    }


    /* API for Get Contact information */
    public function getContactList(Request $request)
    {
        $v = validator($request->all(), [
            'contact_ids' => 'required|array'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        if ($request->contact_ids) {
            $contacts =  Contact::select('id', 'first_name', 'salutation', 'last_name', 'middle_name', 'addition_comments')->whereIn('id', $request->contact_ids)->with(['allergies' => function ($q) {
                $q->with('allergy:id,name');
            }])->get();
        }
        return $this->sendResponse(true, 'success', $contacts);
    }

    /* API for Get Details from Booking  QR code */
    public function getDetailsFromQr($QR_nine_digit_number)
    {
        $booking_processes = BookingProcesses::with(['course_detail'])
            ->with(['customer_detail'])
            ->with(['instructor_detail'])
            ->with(['payment_detail'])
            ->where('QR_number', $QR_nine_digit_number)->get();

        dd($booking_processes);
        return view('customer/customer_details_from_QR', $booking_processes);
    }

    /* API Enrolled Booking Process Course from bookig process id*/
    public function enrolledBookingProcessCourse(Request $request)
    {
        $v = validator($request->all(), [
            'booking_number' => 'required'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $userId = auth()->user()->id;
        if ($request->course_name) {
            $course_datail = Course::where('name', $request->course_name)->first();
            if (!$course_datail) {
                return $this->sendResponse(false, __('strings.course_not_found'));
            }
        }
        $booking_processes_datail = BookingProcesses::where('booking_number', $request->booking_number)->first();
        if (!$booking_processes_datail) {
            return $this->sendResponse(false, __('strings.booking_process_not_found'));
        }
        $contact = User::where('id', $userId)->first();
        $check_customer = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_datail->id)
            ->where('customer_id', $contact->contact_id)
            ->first();
        if (!$check_customer) {
            return $this->sendResponse(false, __('strings.course_enroll_error'));
        }
        $booking_processes = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_datail->id)
            ->where('customer_id', $contact->contact_id)
            ->first();
        if ($booking_processes->is_customer_enrolled == 1) {
            return $this->sendResponse(false, __('strings.course_already_enroll'));
        }
        $booking_processes->is_customer_enrolled = 1;
        $booking_processes->save();
        return $this->sendResponse(true, __('strings.enrolled_course_success'));
    }

    /* List all customer courses deta with booking details */
    public function getCourseDetailsWithBookingId(Request $request)
    {
        $v = validator($request->all(), [
            'course_id' => 'required|integer'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $course_details = Course::with(['category_detail' => function ($query) {
            $query->select('id', 'name');
        }])
            ->with(['difficulty_level_detail' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with('teaching_material_detail.teaching_material_data.teaching_material_category_detail')
            ->find($request->course_id);

        if (!$course_details) {
            return $this->sendResponse(false, 'Course is not found');
        }
        $booking_processes_details = BookingProcesses::with(['course_detail'])
            ->with(['instructor_detail.contact.languages.language' => function ($query) {
                $query->select('id', 'name');
            }, 'instructor_detail.contact.allergies.allergy' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['language_detail.language' => function ($query) {
                $query->select('id', 'name');
            }])
            ->find($request->booking_process_id);


        if ($booking_processes_details) {
            $course_details['booking_processes_details'] = $booking_processes_details;
        } else {
            $course_details['booking_processes_details'] = new class
            {
            };
        }

        //Last activity
        $check_last_activity = InstructorActivity::where('instructor_id', auth()->user()->id)->where('booking_id', $request->booking_process_id)->where('activity_date', date('Y-m-d'))->latest()->first();
        $last_activity = $check_last_activity ?  $check_last_activity['activity_type'] : '';
        $course_details['last_activity'] = $last_activity;

        //Feedback
        $feedback = Feedback::select('id', 'customer_id', 'instructor_id', 'booking_id', 'average_rating')->where('booking_id', $request->booking_process_id);
        if (auth()->user()->type() === 'Customer') {
            $feedback = $feedback->where('customer_id', auth()->user()->id);
        }
        if (auth()->user()->type() === 'Instructor') {
            $feedback = $feedback->where('instructor_id', auth()->user()->id);
        }
        $feedback = $feedback->first();
        if ($feedback) {
            $course_details['feedback_id'] = $feedback->id;
            $course_details['average_rating'] = $feedback->average_rating;
        } else {
            $course_details['feedback_id'] = null;
            $course_details['average_rating'] = null;
        }

        //Customer booking qr code
        $course_details['booking_customer_qr'] = '';
        $bookingCustomer = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->where('customer_id', auth()->user()->contact_id)->first();
        if ($bookingCustomer['QR_number']) {
            $course_details['booking_customer_qr'] = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $bookingCustomer['QR_number'] . "&choe=UTF-8";
        }

        //course is completed or not
        $current_date = date('Y-m-d H:i:s');

        $completed_course = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->where('is_customer_enrolled', 1)->where('EndDate_Time', '<', $current_date)->count();

        if ($completed_course) {
            $course_details['is_completed_course'] = true;
        } else {
            $course_details['is_completed_course'] = false;
        }

        /* $completed_course = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->where('EndDate_Time', '<', $current_date)->count(); */

        return $this->sendResponse(true, 'success', $course_details);
    }

    /* List all courses Instructor deta with booking details */
    public function getInstructorDetailsWithBookingId(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer',
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1'
        ]);
        $page =  $request->page;
        $perPage =  $request->perPage;

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_processes = BookingProcesses::find($request->booking_process_id);

        if (!$booking_processes) {
            return $this->sendResponse(false, 'Booking Processes Id not found');
        }

        $booking_processes = BookingProcesses::query();

        $search = $request->search;
        $booking_processes_details = BookingProcesses::with([
            'instructor_detail.contact' =>
            function ($query) use ($search) {
                $query->where('salutation', 'like', "%$search%");
                $query->orWhere('first_name', 'like', "%$search%");
                $query->orWhere('middle_name', 'like', "%$search%");
                $query->orWhere('last_name', 'like', "%$search%");
            }, 'instructor_detail.contact.languages.language' => function ($query) {
                $query->select('id', 'name');
            }, 'instructor_detail.contact.allergies.allergy' => function ($query) {
                $query->select('id', 'name');
            }
        ])
            //->skip(20*($position-1))->take(10)->orderBy('id','desc')->get()
            ->skip($perPage * ($page - 1))->take($perPage)->orderBy('id', 'desc')
            ->find($request->booking_process_id);
        if ($booking_processes_details) {
            $course_details['booking_processes_details'] = $booking_processes_details;
        } else {
            $course_details['booking_processes_details'] = new class
            {
            };
        }
        return $this->sendResponse(true, 'success', $course_details);
    }

    /* API for change booking process trash status */
    public function changeBookingProcessTrashStatus(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer',
            'is_trash' => 'required|integer|max:1'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_processes = BookingProcesses::find($request->booking_process_id);
        if (!$booking_processes) {
            return $this->sendResponse(false, __('strings.booking_process_not_found'));
        }
        if ($booking_processes->is_trash == $request->is_trash) {
            return $this->sendResponse(false, 'This trash status is already exist in booking process');
        }

        if ($request->is_trash == 1) {
            $booking_processes->trash_at = date("Y-m-d H:i:s");
        } else {
            $booking_processes->trash_at = null;
        }
        $booking_processes->is_trash = $request->is_trash;
        $booking_processes->update();

        /**Add crm user action trail */
        if ($booking_processes) {
            $action_id = $booking_processes->id; //Booking Process id
            if ($request->is_trash) {
                $action_type = 'TB';
            } //TB = Trash Booking
            else {
                $action_type = 'RB';
            } //RB = Restore Booking
            $module_id = 16; //module id base module table
            $module_name = "Bookings"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.change_status_booking_process_success'));
    }

    /* API for change booking process draft status */
    public function changeBookingProcessDraftStatus($id)
    {
        $booking_processes = BookingProcesses::find($id);
        if (!$booking_processes) {
            return $this->sendResponse(false, __('strings.booking_process_not_found'));
        }
        $booking_processes->is_draft = 0;
        $booking_processes->update();
        $this->customer_booking_confirm($id);

        /**Add crm user action trail */
        if ($booking_processes) {
            $action_id = $booking_processes->id; //Booking Process id
            $action_type = 'CBS'; //CBS = Change Booking Status
            $module_id = 16; //module id base module table
            $module_name = "Bookings"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.change_draft_status_booking_process_success'));
    }

    /* API for change booking process draft status in bulk */
    public function changeBookingProcessDraftStatusBulk(Request $request)
    {
        $v = validator($request->all(), [
            'booking_ids' => 'required|array'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_ids = $request->booking_ids;

        foreach ($booking_ids as $id) {
            $booking_processes = BookingProcesses::find($id);
            if (!$booking_processes) {
                continue;
            }
            $booking_processes->is_draft = 0;
            $booking_processes->update();
            $this->customer_booking_confirm($id);

            /**Add crm user action trail */
            if ($booking_processes) {
                $action_id = $booking_processes->id; //Booking Process id
                $action_type = 'CBS'; //CBS = Change Booking Status
                $module_id = 16; //module id base module table
                $module_name = "Bookings"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
            /**End manage trail */
        }

        return $this->sendResponse(true, __('strings.change_draft_status_booking_process_success'));
    }

    /* API for clone booking process */
    public function cloneBookingProcess(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer',
            'StartDate_Time' => 'date',
            'EndDate_Time' => 'date'
        ]);
        $id = $request->booking_process_id;
        $booking_processes = BookingProcesses::find($id);
        unset($booking_processes['id'], $booking_processes['updated_by'], $booking_processes['created_at'], $booking_processes['updated_at'], $booking_processes['deleted_at'], $booking_processes['trash_at']);
        $created_by = auth()->user()->id;

        $booking_processes['created_by'] = $created_by;
        $booking_number = SequenceMaster::where('code', 'BN')->first();
        $booking_processes['QR_number'] = mt_rand(100000000, 999999999);
        $booking_processes['booking_number'] = "EL" . date("m") . "" . date("Y") . $booking_number->sequence;

        //clone booking is always as Draft for the client changes
        $booking_processes['is_draft'] = 1;
        //=====

        $booking_number->update(['sequence' => $booking_number->sequence + 1]);
        $booking_processes = $booking_processes->toArray();

        $clone_booking_processes = BookingProcesses::create($booking_processes);

        $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $id)->first();
        unset($booking_processes_course_datail['created_by'], $booking_processes_course_datail['updated_by'], $booking_processes_course_datail['created_at'], $booking_processes_course_datail['updated_at'], $booking_processes_course_datail['deleted_at'], $booking_processes_course_datail['id']);
        $booking_processes_course_datail['StartDate_Time'] = $request->StartDate_Time;
        $booking_processes_course_datail['EndDate_Time'] = $request->EndDate_Time;

        //explode the date and time
        $tempStrt = explode(" ", $request->StartDate_Time);
        $tempEnd = explode(" ", $request->EndDate_Time);
        //===============
        $booking_processes_course_datail['start_date'] = $tempStrt[0];
        $booking_processes_course_datail['end_date'] = $tempEnd[0];
        $booking_processes_course_datail['start_time'] = $tempStrt[1];
        $booking_processes_course_datail['end_time'] = $tempEnd[1];

        $booking_processes_course_datail['booking_process_id'] = $clone_booking_processes->id;
        $booking_processes_course_datail = $booking_processes_course_datail->toArray();
        $booking_processes_course_datail['created_by'] = $created_by;

        $booking_processes_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $id)->select('customer_id', 'accommodation', 'payi_id', 'course_detail_id', 'no_of_days', 'hours_per_day', 'is_payi', 'cal_payment_type', 'is_include_lunch', 'include_lunch_price', 'start_date', 'end_date', 'start_time', 'end_time', 'sub_child_id')->get();


        $booking_processes_customer_detail = $booking_processes_customer_detail->toArray();

        foreach ($booking_processes_customer_detail as $key => $customer_detail) {
            $customer_detail_inputs = $customer_detail;
            $customer_detail_inputs['created_by'] = $created_by;
            $customer_detail_inputs['booking_process_id'] = $clone_booking_processes->id;
            $customer_detail_inputs['StartDate_Time'] = $request->StartDate_Time;
            $customer_detail_inputs['EndDate_Time'] = $request->EndDate_Time;

            //explode the date and time
            $tempStrt = explode(" ", $request->StartDate_Time);
            $tempEnd = explode(" ", $request->EndDate_Time);
            //===============
            $customer_detail_inputs['start_date'] = $tempStrt[0];
            $customer_detail_inputs['end_date'] = $tempEnd[0];
            $customer_detail_inputs['start_time'] = $tempStrt[1];
            $customer_detail_inputs['end_time'] = $tempEnd[1];

            /**For get season and daytime base startdate, enddate, startime, endtime */
            $data = $this->getSeasonDaytime($tempStrt[0], $tempEnd[0], $tempStrt[1], $tempEnd[1]);
            // dd($data['season'],$data['daytime']);

            if ($customer_detail['cal_payment_type'] == 'PD') {
                $start_date = strtotime($customer_detail_inputs['start_date']);
                $end_date = strtotime($customer_detail_inputs['end_date']);

                $datediff = $end_date - $start_date;
                $no_days = round($datediff / (60 * 60 * 24));
                $no_days = $no_days + 1;
                $course_detail = CourseDetail::where('session', $data['season'])
                    ->where('time', $data['daytime'])
                    ->where('cal_payment_type', $customer_detail['cal_payment_type'])
                    ->where('no_of_days', $no_days)
                    ->where('course_id', $booking_processes_course_datail['course_id'])
                    ->first();

                if (empty($course_detail)) {
                    $course_detail = $this->checkCourseForDays($customer_detail['cal_payment_type'], $no_days, $booking_processes_course_datail['course_id'], $data['season'], $data['daytime']);
                }

                if (!$course_detail) {
                    return $this->sendResponse(false, __('strings.course_detail_data_not_found'));
                }

                $customer_detail_inputs['cal_payment_type'] = $course_detail->cal_payment_type;
                $cal_payment_type = $course_detail->cal_payment_type;
                $cal_payment_type = $course_detail->cal_payment_type;
                $customer_detail_inputs['no_of_days'] = $no_days;
                $no_days = $no_days;
                $customer_detail_inputs['hours_per_day'] = null;
                $hours_per_day =  0;
                $booking_processes_course_datail['course_detail_id'] = $course_detail->id;
                $customer_detail_inputs['course_detail_id'] = $course_detail->id;
                $customer_detail_inputs['is_customer_enrolled'] = 0;
                $price_per_day = $course_detail->price_per_day;
                $course_detail_id = $course_detail->id;
                // dd($no_days, $booking_processes_course_datail['course_id']);
            } elseif ($customer_detail['cal_payment_type'] == 'PH') {
                $start_date = strtotime($customer_detail_inputs['start_date']);
                $end_date = strtotime($customer_detail_inputs['end_date']);

                $datediff = $end_date - $start_date;
                $no_days = round($datediff / (60 * 60 * 24));
                $no_days = $no_days + 1;

                $datetime1 = new DateTime($customer_detail_inputs['start_time']);
                $datetime2 = new DateTime($customer_detail_inputs['end_time']);

                $interval = $datetime2->diff($datetime1);
                $hours_per_day = $interval->format('%h');
                if ($interval->format('%i') >= 45) {
                    $hours_per_day = $hours_per_day + 1;
                }

                $course_detail = CourseDetail::where('session', $data['season'])
                    ->where('time', $data['daytime'])
                    ->where('cal_payment_type', $customer_detail['cal_payment_type'])
                    ->where('hours_per_day', $hours_per_day)
                    ->where('course_id', $booking_processes_course_datail['course_id'])->first();

                if (empty($course_detail)) {
                    $course_detail = $this->checkCourseForHours($customer_detail['cal_payment_type'], $hours_per_day, $booking_processes_course_datail['course_id'], $data['season'], $data['daytime']);
                }

                if (!$course_detail) {
                    return $this->sendResponse(false, __('strings.course_detail_data_not_found'));
                }

                $customer_detail_inputs['cal_payment_type'] = $course_detail->cal_payment_type;
                $cal_payment_type = $course_detail->cal_payment_type;
                $customer_detail_inputs['no_of_days'] = $no_days;
                $customer_detail_inputs['hours_per_day'] = $hours_per_day;

                $hours_per_day =  $hours_per_day;
                $booking_processes_course_datail['course_detail_id'] = $course_detail->id;
                $customer_detail_inputs['is_customer_enrolled'] = 0;
                $customer_detail_inputs['course_detail_id'] = $course_detail->id;
                $course_detail_id = $course_detail->id;
                $price_per_day = $course_detail->price_per_day;
            }

            if ($course_detail->is_include_lunch) {
                $customer_detail_inputs['is_include_lunch'] = 1;
                $customer_detail_inputs['include_lunch_price'] = $course_detail->include_lunch_price;
            } else {
                $customer_detail_inputs['is_include_lunch'] = 0;
                $customer_detail_inputs['include_lunch_price'] = 0;
            }

            $clone_booking_processes_course = BookingProcessCourseDetails::create($booking_processes_course_datail);

            $customer_detail_inputs['QR_number'] = $clone_booking_processes->id . $customer_detail['customer_id'] . mt_rand(100000, 999999);
            $customer_detail = BookingProcessCustomerDetails::create($customer_detail_inputs);
        }

        //THIS CODE IS COMMENT BECAUSE BOOKING CLONE TIME NOT ASSIGNED INSTRUCTOR ,MANUALLY EDIT BOOKING TIME ASSIGN
        /* $booking_processes_instructor_detail = BookingProcessInstructorDetails::where('booking_process_id',$id)->select('contact_id')->get();
        $booking_processes_instructor_detail = $booking_processes_instructor_detail->toArray();
        $instructor_detail_inputs['created_by'] = $created_by;
        $instructor_detail_inputs['booking_process_id'] = $clone_booking_processes->id;
        foreach ($booking_processes_instructor_detail as $key => $instructor_detail) {
                $contact_input_data['last_booking_date'] = date('Y-m-d');
                $contact = Contact::findOrFail($instructor_detail)->first()->fill($contact_input_data)->save();

                $instructor_detail_inputs['contact_id'] = $instructor_detail['contact_id'];
                $instructor_detail = BookingProcessInstructorDetails::create($instructor_detail_inputs);

                //add instructor timesheet info
                if($contact['user_detail'] && $clone_booking_processes_course) {
                    $startDateTime = explode(" ",$clone_booking_processes_course->StartDate_Time);
                    $endDateTime = explode(" ",$clone_booking_processes_course->EndDate_Time);
                    $start_date=$startDateTime[0];
                    $end_date=$endDateTime[0];
                    $start_time = $startDateTime[1];
                    $end_time = $endDateTime[1];
                    $this->InstructorTimesheetCreate($contact->user_detail->id,$clone_booking_processes->id,$start_date,$end_date,$start_time,$end_time);
                }
            } */

        $booking_processes_payment_detail = BookingProcessPaymentDetails::where('booking_process_id', $id)->first();

        unset($booking_processes_payment_detail['created_by'], $booking_processes_payment_detail['updated_by'], $booking_processes_payment_detail['created_at'], $booking_processes_payment_detail['updated_at'], $booking_processes_payment_detail['deleted_at'], $booking_processes_payment_detail['id'], $booking_processes_payment_detail['booking_process_id']);

        // $booking_processes_payment_detail = $booking_processes_payment_detail->toArray();
        $invoice_number = SequenceMaster::where('code', 'INV')->first();

        if ($invoice_number) {
            $input['invoice_number'] = $invoice_number->sequence;
            $payment_detail['invoice_number'] = "INV" . date("m") . "" . date("Y") . $input['invoice_number'];
            $invoice_number->update(['sequence' => $invoice_number->sequence + 1]);
        }
        $payment_detail['created_by'] = $created_by;
        $payment_detail['booking_process_id'] = $clone_booking_processes->id;

        $discount = $booking_processes_payment_detail['discount'];
        $total_price =  $price_per_day;

        $payment_detail['booking_process_id'] = $clone_booking_processes->id;
        $payment_detail['total_price'] = $total_price;
        $payment_detail['course_detail_id'] = $course_detail_id;

        $vat_percentage = $this->getVat();
        $lvat_percentage = $this->getLunchVat();

        // calculate days and hours wise total price
        $total_bookings_days = $no_days;
        $course_total_price = 0;
        //$course_total_price= ($price_per_day * $total_bookings_days);

        if ($cal_payment_type === 'PH') {
            $course_total_price = ($price_per_day * ($total_bookings_days * $hours_per_day));
        } else {
            $course_total_price = ($price_per_day * $total_bookings_days);
        }

        /**Add settlement amount in main price */
        if ($booking_processes_payment_detail['settlement_amount']) {
            $settlement_amount = $booking_processes_payment_detail['settlement_amount'];

            if ($settlement_amount > 0 && isset($settlement_amount)) {
                $course_total_price = $course_total_price - $settlement_amount;
            } else {
                $course_total_price = $course_total_price + $settlement_amount;
            }
        }

        if ($booking_processes_course_datail['course_type'] === 'Private') {
            /**Code comment for new requirement
             * Date : 23-07-2020
             */
            // $extra_participant = BookingProcessExtraParticipant::where('booking_process_id', $request->booking_process_id)->count();

            $booking_course_data = BookingProcessCourseDetails::where('booking_process_id', $id)->first();

            $extra_person_charge = 0;
            if ($booking_course_data->is_extra_participant) {
                $extra_person_charge = $course_detail['extra_person_charge'];

                /**If no of extra participant available */
                $total_participant = 0;
                if ($booking_course_data->no_of_extra_participant) {
                    $total_participant = $booking_course_data->no_of_extra_participant;
                }

                $payment_detail['extra_person_charge'] = $total_participant * $extra_person_charge;
                $total_price = $course_total_price;
                $course_total_price = $course_total_price + ($total_participant * $extra_person_charge);
                $payment_detail['include_extra_price'] = $course_total_price;
            }

            // if ($extra_participant) {

            //     $extra_person_charge = $course_detail['extra_person_charge'];
            //     $payment_detail['extra_person_charge'] = $extra_participant * $extra_person_charge;
            //     $total_price = $course_total_price;
            //     $course_total_price = $course_total_price + ($extra_participant * $extra_person_charge);
            //     $payment_detail['include_extra_price'] = $course_total_price;
            // }
            $netPrise = $course_total_price - ($course_total_price * ($discount / 100));
        }/*  else {
                $netPrise = $course_total_price - ($total_price*($discount/100));
            } */

        $excluding_vat_amount = 0;
        if ($course_total_price && $vat_percentage) {
            $excluding_vat_amount =
                $course_total_price / ((100 + $vat_percentage) / 100);
        }

        //vat amount calculation
        $vat_amount = 0;
        if ($course_total_price && $excluding_vat_amount) {
            $vat_amount = $course_total_price - $excluding_vat_amount;
        }

        //Calculate Lunch Total Price Based On Date
        /* ($booking_processes_payment_detail['include_lunch_price'])?$include_lunch_price=$course_detail->include_lunch_price:$include_lunch_price=0; */

        if ($booking_processes_payment_detail['is_include_lunch']) {
            $is_include_lunch = $booking_processes_payment_detail['is_include_lunch'];
            $include_lunch_price = $course_detail->include_lunch_price;
        } else {
            $is_include_lunch = 0;
            $include_lunch_price = 0;
        }

        $total_lunch_price = $include_lunch_price * $total_bookings_days;

        //excluding lunch vat price
        $lunch_vat_excluded_amount = 0;
        if ($include_lunch_price && $is_include_lunch && $lvat_percentage) {
            $lunch_vat_excluded_amount =
                ($include_lunch_price * $total_bookings_days) / ((100 + $lvat_percentage) / 100);
        }

        //vat amount calculation lunch
        $lunch_vat_amount = 0;
        if ($include_lunch_price && $is_include_lunch && $lunch_vat_excluded_amount) {
            $lunch_vat_amount = ($include_lunch_price * $total_bookings_days) - $lunch_vat_excluded_amount;
        }

        //Calculate Net Price
        // $netPrise=$course_total_price;
        //$discount = $request->discount;


        if ($booking_processes_payment_detail['is_include_lunch']) {
            $netPrise = $netPrise + $total_lunch_price;
        }

        // $update_payment_detail['net_price'] = $netPrise;

        $payment_detail['total_price'] = $total_price;
        $payment_detail['vat_excluded_amount'] = $excluding_vat_amount;
        $payment_detail['vat_amount'] = $vat_amount;
        $payment_detail['vat_percentage'] = $vat_percentage;
        $payment_detail['lunch_vat_excluded_amount'] = $lunch_vat_excluded_amount;
        $payment_detail['lunch_vat_amount'] = $lunch_vat_amount;
        $payment_detail['lunch_vat_percentage'] = $lvat_percentage;

        $payment_detail['price_per_day'] = $price_per_day;
        $payment_detail['net_price'] = $netPrise;
        $payment_detail['no_of_days'] = $no_days;
        $payment_detail['discount'] = $discount;
        // $payment_detail['payi_id'] = $updateContact->payi_id;
        $payment_detail['hours_per_day'] = $hours_per_day;
        $payment_detail['created_by'] = $created_by;
        $payment_detail['cal_payment_type'] = $cal_payment_type;
        // $payment_detail['include_extra_price'] = $cal_payment_type;
        $payment_detail['payi_id'] = $booking_processes_payment_detail['payi_id'];
        $payment_detail['customer_id'] = $booking_processes_payment_detail['customer_id'];
        $payment_detail['sub_child_id'] = ($booking_processes_payment_detail['sub_child_id'] ?: null);
        $payment_detail['payment_method_id'] = $booking_processes_payment_detail['payment_method_id'];
        $payment_detail['cal_payment_type'] = $booking_processes_payment_detail['cal_payment_type'];

        if ($booking_processes_payment_detail['is_include_lunch']) {
            $payment_detail['is_include_lunch'] = $booking_processes_payment_detail['is_include_lunch'];
            $payment_detail['include_lunch_price'] = $total_lunch_price;
        }
        $payment_detail1 = BookingProcessPaymentDetails::create($payment_detail);
        if ($payment_detail1) {
            $invoice_link = $this->updateInvoiceLink($payment_detail1->id);
        }

        $booking_processes_extra_participant_detail = BookingProcessExtraParticipant::where('booking_process_id', $id)->select('name', 'age')->get();
        $booking_processes_extra_participant_detail = $booking_processes_extra_participant_detail->toArray();

        foreach ($booking_processes_extra_participant_detail as $key => $extra_participants_detail) {
            $extra_participants_inputs = $extra_participants_detail;
            $extra_participants_inputs['created_by'] = $created_by;
            $extra_participants_inputs['booking_process_id'] = $clone_booking_processes->id;
            $instructor_detail_inputs = BookingProcessExtraParticipant::create($extra_participants_inputs);
        }

        $booking_processes_language_detail = BookingProcessLanguageDetails::where('booking_process_id', $id)->select('language_id')->get();
        $booking_processes_language_detail = $booking_processes_language_detail->toArray();
        $language_detail_inputs['created_by'] = $created_by;
        $language_detail_inputs['booking_process_id'] = $clone_booking_processes->id;
        foreach ($booking_processes_language_detail as $key => $language_detail) {
            $language_detail_inputs['language_id'] = $language_detail['language_id'];
            $language_detail = BookingProcessLanguageDetails::create($language_detail_inputs);
        }

        //Update chatroom for openfire
        UpdateChatRoom::dispatch(true, $clone_booking_processes->id);

        /**Add crm user action trail */
        if ($booking_processes) {
            $action_id = $clone_booking_processes->id; //Clone Booking Process id
            $action_type = 'CB'; //CB = Clone Booking
            $module_id = 16; //module id base module table
            $module_name = "Bookings"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.clone_booking_process_success'));
    }

    /* Ongoing course details with booking details for application logged in customer user */
    public function getOngoingCourseDetailWithBookingDetail()
    {
        $contact_id = auth()->user()->contact_id;
        if (!$contact_id) {
            return $this->sendResponse(false, 'User not found');
        }

        $booking_processes_ids = BookingProcessCustomerDetails::where('customer_id', $contact_id)->pluck('booking_process_id');
        if ($booking_processes_ids->isEmpty()) {
            return $this->sendResponse(false, 'User have not a any Booking');
        }

        $current_date = date('Y-m-d H:i:s');
        $ongoing_booking_processes_detail = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_processes_ids)
            ->where('StartDate_Time', '<=', $current_date)
            ->where('EndDate_Time', '>=', $current_date)
            ->first();

        if (!$ongoing_booking_processes_detail) {
            return $this->sendResponse(false, 'User have not a any ongoing course');
        }

        $course = Course::find($ongoing_booking_processes_detail->course_id);
        if (!$course) {
            return $this->sendResponse(false, 'Course not found');
        }

        $booking_processes = BookingProcesses::find($ongoing_booking_processes_detail->booking_process_id);

        if (!$booking_processes) {
            return $this->sendResponse(false, 'Booking Process not found');
        }

        $course_details = Course::with(['category_detail' => function ($query) {
            $query->select('id', 'name');
        }])
            ->with(['difficulty_level_detail' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with('teaching_material_detail.teaching_material_data.teaching_material_category_detail')
            ->find($ongoing_booking_processes_detail->course_id);

        if (!$course_details) {
            return $this->sendResponse(false, 'Course not found');
        }

        $booking_processes_details = BookingProcesses::with(['course_detail'])
            ->with(['instructor_detail.contact.languages.language' => function ($query) {
                $query->select('id', 'name');
            }, 'instructor_detail.contact.allergies.allergy' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['language_detail.language' => function ($query) {
                $query->select('id', 'name');
            }])
            ->find($ongoing_booking_processes_detail->booking_process_id);

        if ($booking_processes_details) {
            $course_details['booking_processes_details'] = $booking_processes_details;
        } else {
            $course_details['booking_processes_details'] = new class
            {
            };
        }
        return $this->sendResponse(true, 'success', $course_details);
    }

    /*Send email to customer and payi for after booking process */
    public function customer_booking_confirm($booking_processes_id, $new_added_customers = [])
    {
        //$booking_processes_id = 302;
        $email_data = array();
        $booking_processes = BookingProcesses::find($booking_processes_id);
        $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes_id)->first();
        $course_id = $booking_processes_course_datail->course_id;

        $course = Course::where('id', $course_id)->first();
        $course_name = $course->name;

        /**If update booking time new customers was added then send confirmation email */
        if (count($new_added_customers)) {
            $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_id)
                ->whereIn('customer_id', $new_added_customers)
                ->get()->toArray();
        } else {
            $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_id)->get()->toArray();
        }

        foreach ($booking_processes_customer_details as $key => $customer_detail) {
            if (count($new_added_customers)) {
                $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $booking_processes_id)
                    // ->whereIn('customer_id', $new_added_customers)
                    ->where('customer_id', $customer_detail['customer_id'])
                    ->get()[$key];
            } else {
                $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $booking_processes_id)
                    // ->where('customer_id',$customer_detail['customer_id'])
                    ->get()[$key];
            }

            // dd($key,$customer_detail,$booking_processes_payment_details);

            $contact = Contact::find($customer_detail['customer_id']);
            $email_data['booking_number'] = $booking_processes->booking_number;
            $start_date = explode(" ", $customer_detail['StartDate_Time']);
            //$email_data['start_date'] = date_format($start_date[0], 'Y/m/d');
            $email_data['start_date'] = $start_date[0];
            $email_data['start_time'] = $start_date[1];
            $end_date = explode(" ", $customer_detail['EndDate_Time']);
            $email_data['end_date'] = $end_date[0];
            $email_data['end_time'] = $end_date[1];
            $url = url('/') . '/bookingProcessQr/' . $booking_processes->QR_number;
            $email_data['booking_qr'] = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $url . "&choe=UTF-8";
            $email_data['salutation'] = $contact->salutation;
            $email_data['first_name'] = $contact->first_name;
            $email_data['last_name'] = $contact->last_name;
            $email_data['deep_link'] = url('/d/customer/confirmation-booking?booking_process_id=' . $booking_processes_id . '&course_id=' . $course_id);

            $update_at = date('H:i:s');

            if ($customer_detail['is_payi'] == 'No') {
                $payi_id = $customer_detail['payi_id'];

                $payee = Contact::find($payi_id);
                $email_data['booking_number'] = $booking_processes->booking_number;
                $start_date = explode(" ", $customer_detail['StartDate_Time']);
                //$email_data['start_date'] = date_format($start_date[0], 'Y/m/d');
                $email_data['start_date'] = $start_date[0];
                $email_data['start_time'] = $start_date[1];
                $end_date = explode(" ", $customer_detail['EndDate_Time']);
                $email_data['end_date'] = $end_date[0];
                $email_data['end_time'] = $end_date[1];
                $url = url('/') . '/bookingProcessQr/' . $booking_processes->QR_number;
                $email_data['booking_qr'] = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $url . "&choe=UTF-8";
                $email_data['salutation'] = $payee->salutation;
                $email_data['first_name'] = $payee->first_name;
                $email_data['last_name'] = $payee->last_name;
                $email_data['deep_link'] = url('/d/customer/confirmation-booking?booking_process_id=' . $booking_processes_id . '&course_id=' . $course_id);

                if ($booking_processes_payment_details->status === 'Pending') {
                    $email_data['payment_link'] = $booking_processes_payment_details->payment_link;
                }
                // $payee->notify(new BookingConfirm($email_data, $course_name, $booking_processes->booking_number));
                $payee->notify((new BookingConfirm($email_data, $course_name, $booking_processes->booking_number))->locale($payee->language_locale));
            } else {
                if ($booking_processes_payment_details->status === 'Pending') {
                    $email_data['payment_link'] = $booking_processes_payment_details->payment_link;
                }
                // $contact->notify(new BookingConfirm($email_data, $course_name, $booking_processes->booking_number));
                $contact->notify((new BookingConfirm($email_data, $course_name, $booking_processes->booking_number))->locale($contact->language_locale));
            }
        }
        //dd($email_data);
        //$booking_processes->payi_id;
        /*  $contact = Contact::where('QR_number',$QR_nine_digit_number)->first();
             //dd($contact); */
        return view('email/customer/confirmation_of_booking', $email_data);
    }

    /*Send email to customer and payi for after booking process */
    public function customer_update_booking($booking_processes_id)
    {
        $email_data = array();
        $booking_processes = BookingProcesses::find($booking_processes_id);
        $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes_id)->first();
        $course_id = $booking_processes_course_datail->course_id;

        $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_id)->where('is_updated', 1)->get()->toArray();
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

            $update['is_updated'] = 0;
            $update_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_id)->where('customer_id', $customer_detail['customer_id'])
                ->update($update);
        }

        //For send email and notification for course update
        $booking_processes_instructor_details = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes_id)->get()->toArray();

        foreach ($booking_processes_instructor_details as $key => $instructor_detail) {
            // foreach ($booking_processes_customer_details as $key => $customer_detail) {
            //for send email
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

            // }
        }
        // return view('email/customer/confirmation_of_booking', $email_data);
        return 1;
    }

    /*This is a check Send email to customer and payi for after booking process */
    public function customer_update_booking1($booking_processes_id)
    {
        //$booking_processes_id = 302;
        $email_data = array();
        $booking_processes = BookingProcesses::find($booking_processes_id);
        $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes_id)->first();
        $course_id = $booking_processes_course_datail->course_id;

        $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_id)->get()->toArray();

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

            // $contact->notify(new UpdateBooking($email_data));
            $contact->notify((new UpdateBooking($email_data))->locale($contact->language_locale));

            $update['is_updated'] = 0;
            $update_customer_detail = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_id)->where('customer_id', $customer_detail['customer_id'])
                ->update($update);
        }

        //For send email and notification for course update
        $booking_processes_instructor_details = BookingProcessInstructorDetails::where('booking_process_id', $booking_processes_id)->get()->toArray();

        foreach ($booking_processes_instructor_details as $key => $instructor_detail) {
            foreach ($booking_processes_customer_details as $key => $customer_detail) {
                $contact = Contact::find($instructor_detail['contact_id']);
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

                // $contact->notify(new UpdateBooking($email_data));
                $contact->notify((new UpdateBooking($email_data))->locale($contact->language_locale));
            }
        }
        // return view('email/customer/confirmation_of_booking', $email_data);
        return 1;
    }

    //this function is temporary set for make design purpase
    public function customer_booking_confirm1($booking_processes_id)
    {
        //$booking_processes_id = 302;
        $email_data = array();
        $booking_processes = BookingProcesses::find($booking_processes_id);
        $booking_processes_course_datail = BookingProcessCourseDetails::where('booking_process_id', $booking_processes_id)->first();
        $course_id = $booking_processes_course_datail->course_id;

        $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_processes_id)->get()->toArray();

        foreach ($booking_processes_customer_details as $key => $customer_detail) {
            $contact = Contact::find($customer_detail['customer_id']);
            $email_data['data']['booking_number'] = $booking_processes->booking_number;
            $start_date = explode(" ", $customer_detail['StartDate_Time']);
            //$email_data['start_date'] = date_format($start_date[0], 'Y/m/d');
            $email_data['data']['start_date'] = $start_date[0];
            $email_data['data']['start_time'] = $start_date[1];
            $end_date = explode(" ", $customer_detail['EndDate_Time']);
            $email_data['data']['end_time'] = $end_date[1];
            $url = url('/') . '/bookingProcessQr/' . $booking_processes->QR_number;
            $email_data['data']['booking_qr'] = "https://chart.googleapis.com/chart?chs=300x300&cht=qr&chl=" . $url . "&choe=UTF-8";
            $email_data['data']['salutation'] = $contact->salutation;
            $email_data['data']['first_name'] = $contact->first_name;
            $email_data['data']['last_name'] = $contact->last_name;
            $email_data['data']['deep_link'] = url('/d/customer/confirmation-booking?booking_process_id=' . $booking_processes_id . '&course_id=' . $course_id);

            // $contact->notify(new BookingConfirm($email_data));
        }
        //dd($email_data);
        //$booking_processes->payi_id;
        /*  $contact = Contact::where('QR_number',$QR_nine_digit_number)->first();
             //dd($contact); */
        return view('email/customer/confirmation_of_booking', $email_data);
    }


    /*Get Booking process id base participate listing*/
    public function getParticipateListingBookingIdBase(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_processes_id = $request->booking_process_id;
        $booking_processes = BookingProcesses::find($booking_processes_id);
        if (!$booking_processes) {
            return $this->sendResponse(false, 'Booking Process not found');
        }

        $current_date = date('Y-m-d H:i:s');
        $ongoing_booking_customer_ids = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)
        ->where('season_ticket_number', '!=', null)
        // ->where('StartDate_Time', '>=', $current_date)
        ->where('EndDate_Time', '<=', $current_date)
        ->pluck('id');
        // if (!$ongoing_booking_customer_ids) {
        //     return $this->sendResponse(false, 'This Booking Process have Empty Participate List');
        // }

        $booking_processes_customer_details = BookingProcessCustomerDetails::with('customer')
            ->with('customer.allergies.allergy', 'customer.languages.language', 'customer.difficulty_level_detail')
            ->with('bookingProcessCourseDetails')
            ->with(
                'bookingPaymentDetails.sub_child_detail.allergies.allergy',
                'bookingPaymentDetails.sub_child_detail.languages.language'
            )
            ->whereNotIn('id',$ongoing_booking_customer_ids)
            ->with('sub_child_detail.allergies.allergy', 'sub_child_detail.languages.language')
            ->where('booking_process_id', $booking_processes_id)->get()->toArray();

        return $this->sendResponse(true, 'success', $booking_processes_customer_details);
    }

    /* API for Get Details from Customer booking  QR code */
    public function getBookingCustomerDetailFromQr($QR_number)
    {
        $bookingCustomer =  BookingProcessCustomerDetails::where('QR_number', $QR_number)->first();
        if (!$bookingCustomer) {
            return $this->sendResponse(false, __('Invalid qr number'));
        }
        $customer_detail = Contact::select('id', 'salutation', 'first_name', 'last_name', 'email')->find($bookingCustomer['customer_id']);

        return $this->sendResponse(true, 'Customer detail', $customer_detail);
    }

    /* API for Transfer booking(course) for customer by instructor */
    public function transferBooking(Request $request)
    {
        $v = validator($request->all(), [
            'qr_number' => 'required|integer',
            'new_booking_id' => 'required|integer'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_id = $request->new_booking_id;
        $qr_number = $request->qr_number;

        $oldBookingDetail =  BookingProcessCustomerDetails::where('QR_number', $qr_number)->get();

        if (!count($oldBookingDetail)) {
            return $this->sendResponse(false, __('strings.booking_not_found'));
        }
        $old_booking_process_id = $oldBookingDetail[0]->booking_process_id;

        $newBookingDetail =  BookingProcessCustomerDetails::where('booking_process_id', $booking_id)->first();
        $totalStrudents =  BookingProcessCustomerDetails::where('booking_process_id', $booking_id)->count();
        $newBookingCourse =  BookingProcessCourseDetails::where('booking_process_id', $booking_id)->first();

        $oldBookingPaymentDetail =  BookingProcessPaymentDetails::where('customer_id', $oldBookingDetail[0]->customer_id)
            ->where('booking_process_id', $oldBookingDetail[0]->booking_process_id)
            ->get();

        $oldBookingCourseDetail =  BookingProcessCourseDetails::where('booking_process_id', $oldBookingDetail[0]->booking_process_id)->first();

        $currentDateTime = date('Y-m-d H:i:s');
        $groupLimit = (int)config('constants.STUDENT_LIMIT');

        if (!$oldBookingCourseDetail || !$newBookingCourse || !$newBookingDetail) {
            return $this->sendResponse(false, __('strings.booking_not_found'));
        }

        $newBookingCustomer = BookingProcessCustomerDetails::where('booking_process_id', $booking_id)->pluck('customer_id')->toArray();

        if (in_array($oldBookingDetail[0]->customer_id, $newBookingCustomer)) {
            $newBookingSubChild = BookingProcessCustomerDetails::where('booking_process_id', $booking_id)
                ->where('customer_id', $oldBookingDetail[0]->customer_id)
                ->pluck('sub_child_id')
                ->toArray();

            if (in_array($oldBookingDetail[0]->sub_child_id, $newBookingSubChild)) {
                return $this->sendResponse(false, __('strings.participant_exist'));
            }
        }

        if ($totalStrudents >= $groupLimit) {
            return $this->sendResponse(false, __('strings.maximum_group_limit', ['limit' => $groupLimit]));
        }

        $oldBookingCourse = $oldBookingDetail[0]->bookingProcessCourseDetails;
        $oldCourseDetail = $oldBookingDetail[0]->course_detail_data;
        $newCourseDetail = $newBookingDetail->course_detail_data;
        if (!$oldBookingCourse || !$oldCourseDetail || !$newCourseDetail) {
            return $this->sendResponse(false, 'Booking course not found');
        }

        if ($oldBookingDetail[0]->booking_process_id === $newBookingDetail->booking_process_id) {
            return $this->sendResponse(false, __('strings.both_booking_not_same'));
        }
        if (($oldBookingDetail[0]->StartDate_Time > $currentDateTime) || ($newBookingCourse->StartDate_Time > $currentDateTime) || ($oldBookingDetail[0]->EndDate_Time < $currentDateTime) || ($newBookingCourse->EndDate_Time < $currentDateTime)) {
            return $this->sendResponse(false, __('strings.both_booking_ongoing'));
        }

        if (($newBookingCourse->course_type === 'Private') || ($oldBookingCourse->course_type === 'Private')) {
            return $this->sendResponse(false, __('strings.both_booking_not_private'));
        }

        /**Date : : 11-01-2021
         * Description : This is old logic for check course id must be same
         */
        // if ($newBookingCourse->course_id !== $oldBookingCourse->course_id) {
        //     return $this->sendResponse(false, __('strings.both_course_same'));
        // }

        /**Description : Now manage old and new booking course category must be same */
        $oldCourseCategory = $oldBookingDetail[0]->bookingProcessCourseDetails->course_data;
        $newCourseCategory = $newBookingDetail->bookingProcessCourseDetails->course_data;

        if ($oldCourseCategory->category_id !== $newCourseCategory->category_id) {
            return $this->sendResponse(false, __('strings.both_course_same_category'));
        }
        /** */

        if (($oldCourseDetail->session !== $newCourseDetail->session) || ($oldCourseDetail->time !== $newCourseDetail->time)) {
            return $this->sendResponse(false, __('strings.both_course_same'));
        }

        /** Old Booking Customer details update with new booking id */
        foreach ($oldBookingDetail as $booking) {
            $booking->booking_process_id = $booking_id;
            $booking->save();
        }

        /**Old Booking Payment details update with new booking id */
        foreach ($oldBookingPaymentDetail as $booking_payment) {
            $booking_payment->booking_process_id = $booking_id;
            $booking_payment->save();
        }

        $data = ['new_booking_id' => $booking_id];

        /**Get old customer startdate and enddate if customer booking extend so this logic is used */
        $strt_date = $oldBookingDetail[0]->start_date;
        $end_date = $oldBookingDetail[0]->end_date;

        foreach ($oldBookingDetail as $booking) {
            if ($booking->start_date < $strt_date) {
                $strt_date = $booking->start_date;
            }
            if ($booking->end_date > $end_date) {
                $end_date = $booking->end_date;
            }
            $strt_date_time = $strt_date . ' ' . $booking->start_time;
            $strt_date_time = strtotime($strt_date_time);
            $new_strt_date_time = date("Y-m-d H:i:s", $strt_date_time);

            $end_date_time = $end_date . ' ' . $booking->end_time;
            $end_date_time = strtotime($end_date_time);
            $new_end_date_time = date("Y-m-d H:i:s", $end_date_time);
        }
        /**End */

        if ($strt_date < $newBookingCourse->start_date) {
            $updateCourseDetail['start_date'] = $strt_date;
        } elseif ($end_date > $newBookingCourse->end_date) {
            $updateCourseDetail['end_date'] = $end_date;
        }

        /** New Booking update main general startdate, enddate , starttime, endtime, StartDate_Time , EndDate_Time fileds */
        if (!empty($updateCourseDetail['start_date'])) {
            $start_date1 = $updateCourseDetail['start_date'] . ' ' . $newBookingCourse->start_time;
            $start_date1 = strtotime($start_date1);
            $new_start_date = date("Y-m-d H:i:s", $start_date1);
            $newBookingCourse->StartDate_Time = $new_start_date;
            $newBookingCourse->start_date = $new_start_date;
            $tempStrt = explode(" ", $new_strt_date_time);
            if ($tempStrt[1] < $newBookingCourse->start_time) {
                $start_time = $tempStrt[1];
                $start_date1 = $updateCourseDetail['start_date'] . ' ' . $start_time;
                $start_date1 = strtotime($start_date1);
                $new_start_date_time = date("Y-m-d H:i:s", $start_date1);
                $newBookingCourse->StartDate_Time = $new_start_date_time;
                $newBookingCourse->start_time = $start_time;
            }
        }

        if (!empty($updateCourseDetail['end_date'])) {
            $end_date1 = $updateCourseDetail['end_date'] . ' ' . $newBookingCourse->end_time;
            $end_date1 = strtotime($end_date1);
            $new_end_date = date("Y-m-d H:i:s", $end_date1);
            $newBookingCourse->EndDate_Time = $new_end_date;
            $newBookingCourse->end_date = $new_end_date;
            $tempEnd = explode(" ", $new_end_date_time);
            if ($tempEnd[1] > $newBookingCourse->end_time) {
                $end_time = $tempEnd[1];
                $end_date1 = $updateCourseDetail['end_date'] . ' ' . $end_time;
                $end_date1 = strtotime($end_date1);
                $new_end_date_time = date("Y-m-d H:i:s", $end_date1);
                $newBookingCourse->EndDate_Time = $new_end_date_time;
                $newBookingCourse->end_time = $end_time;
            }
        }
        $newBookingCourse->save();
        /**End  */

        $oldBookingCustomerDetail =  BookingProcessCustomerDetails::where('booking_process_id', $old_booking_process_id)->get();

        if (count($oldBookingCustomerDetail)) {
            /**Update Old Booking general dates fields */
            $old_start_time = $oldBookingCustomerDetail[0]->StartDate_Time;
            $old_end_time = $oldBookingCustomerDetail[0]->EndDate_Time;

            foreach ($oldBookingCustomerDetail as $customer_detail) {
                if ($customer_detail->StartDate_Time < $old_start_time) {
                    $old_start_time = $customer_detail->StartDate_Time;
                }
                if ($customer_detail->EndDate_Time > $old_end_time) {
                    $old_end_time = $customer_detail->EndDate_Time;
                }
                $old_strt = explode(" ", $old_start_time);
                $old_end = explode(" ", $old_end_time);
            }
            $oldBookingCourseDetail->StartDate_Time = $old_start_time;
            $oldBookingCourseDetail->EndDate_Time = $old_end_time;
            $oldBookingCourseDetail->start_date = $old_strt[0];
            $oldBookingCourseDetail->end_date = $old_end[0];
            $oldBookingCourseDetail->start_time = $old_strt[1];
            $oldBookingCourseDetail->end_time = $old_end[1];
            $oldBookingCourseDetail->save();

            if ($oldBookingDetail[0]->customer && $oldBookingDetail[0]->customer->user_detail) {
                $user_detail = $oldBookingDetail[0]->customer->user_detail;
                $type = 11;
                $title = "Course Transfered!";
                $body =  "Congratulations! Your ongoing course is now transfered";
                SendPushNotification::dispatch($user_detail['device_token'], $user_detail['device_type'], $title, $body, $type, $data);
            }
            /**End */
        } else {
            /**Booking soft delete if last participate is transfered to another booking */
            $delete_booking = $this->deleteBookingProcess($old_booking_process_id);
        }

        return $this->sendResponse(true, __('strings.booking_transfer_success'), $data);
    }

    /* API for change  instructor */
    public function changeInstructor(Request $request)
    {
        $v = validator($request->all(), [
            'old_instructor_id' => 'required|integer',
            'new_instructor_id' => 'required|integer',
            'booking_id' => 'required|integer',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'required|date_format:H:i:s|after_or_equal:start_time'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $old_instructor_id = $request->old_instructor_id;
        $new_instructor_id = $request->new_instructor_id;
        $booking_id = $request->booking_id;
        $start_time = $request->start_time;
        $end_time = $request->end_time;
        //$checkMultipleInstructor = BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->count();
        $checkInstructor = BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->count();
        $checkCourse = BookingProcessCourseDetails::where('booking_process_id', $booking_id)->first();
        $bookingCustomers = BookingProcessCustomerDetails::where('booking_process_id', $booking_id)->get();

        if (!$checkCourse) {
            return $this->sendResponse(false, __('strings.booking_course_not_available'));
        }
        if ($checkInstructor === 0) {
            return $this->sendResponse(false, __('strings.instructor_not_found'));
        }
        // if ($checkMultipleInstructor > 1) {
        //     return $this->sendResponse(false, __('strings.booking_multiple_instructors'));
        // }
        if (count($bookingCustomers) === 0) {
            return $this->sendResponse(false, __('strings.customer_not_found'));
        }

        $sameStartEndDate = true;
        $startDateTime = $checkCourse['StartDate_Time'];
        $endDateTime = $checkCourse['EndDate_Time'];
        $start_date = $checkCourse['start_date'];
        $end_date = $checkCourse['end_date'];
        foreach ($bookingCustomers as $customer) {
            if (($customer['StartDate_Time'] != $startDateTime) || ($customer['EndDate_Time'] != $endDateTime)) {
                $sameStartEndDate = false;
                break;
            }
        }
        if (!$sameStartEndDate) {
            return $this->sendResponse(false, __('strings.all_customer_same_start_end_date'));
        }

        $bookingLanguages =  BookingProcessLanguageDetails::where('booking_process_id', $booking_id)->pluck('language_id')->toArray();
        $checkLanguage = ContactLanguage::where('contact_id', $new_instructor_id)->whereIn('language_id', $bookingLanguages)->count();
        if (!$checkLanguage) {
            return $this->sendResponse(false, __('strings.instructor_same_language'));
        }

        $contact = Contact::find($new_instructor_id);
        /**If new instrctor user not exist then create as user */
        if (!$contact->user_detail) {
            $this->createInstructorUser($contact);
        }
        $old_contact = Contact::find($old_instructor_id);
        if (!$old_contact->user_detail) {
            $this->createInstructorUser($old_contact);
        }
        /**End */

        $dates_data = [];
        $dates_data[] = [
            'StartDate_Time' => $start_date . ' ' . $start_time,
            'EndDate_Time' => $end_date . ' ' . $end_time
        ];
        $checkAvailability = $this->getAvailableInstructorListNew($dates_data, $booking_id);
        /*  $bookingIds = BookingProcessCustomerDetails::where('booking_process_id', '!=', $booking_id)
             ->where(function($query) use($start_date,$end_date){
                $query->whereBetween('start_date', [$start_date,$end_date])->orWhereBetween('end_date', [$start_date,$end_date])
             })
             //->where('start_date', '<=', $start_date)->where('end_date', '>=', $end_date)
             ->where(function ($q) use ($start_time,$end_time) {
                 $q->where('start_time', '<=', $start_time);
                 $q->OrWhere('start_time', '<=', $end_time);
             })
             ->where(function ($q) use ($start_time,$end_time) {
                 $q->where('end_time', '>=', $start_time);
                 $q->OrWhere('end_time', '>=', $end_time);
             })
             ->pluck('booking_process_id');
             echo $start_date.' '.$end_date;
             dd($bookingIds);
         $instructorBookings = BookingProcessInstructorDetails::where('contact_id', $new_instructor_id)->whereIn('booking_process_id', $bookingIds)->pluck('booking_process_id')->toArray();
         if (count($instructorBookings) > 0) {
             return $this->sendResponse(false, __('strings.instructor_not_available'));
         }

      */
        if (!empty($checkAvailability['booking_processes_ids_main'])) {
            if (count($checkAvailability['booking_processes_ids_main'])  > 0) {
                $instructorBookings = BookingProcessInstructorDetails::where('contact_id', $new_instructor_id)->whereIn('booking_process_id', $checkAvailability['booking_processes_ids_main'])->pluck('booking_process_id')->toArray();
                if (count($instructorBookings) > 0) {
                    return $this->sendResponse(false, __('strings.instructor_not_available'));
                }
            }
        }
        $checkLeave = ContactLeave::where('contact_id', $new_instructor_id)->where('leave_status', 'A')->where('start_date', '<=', $start_date)->where('end_date', '>=', $end_date)->count();
        if ($checkLeave) {
            return $this->sendResponse(false, __('strings.instructor_on_leave'));
        }

        //Change instructor
        $newStartDateTime = $start_date . ' ' . $start_time;
        $newEndDateTime = $end_date . ' ' . $end_time;

        BookingProcessCustomerDetails::where('booking_process_id', $booking_id)->update([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'StartDate_Time' => $newStartDateTime,
            'EndDate_Time' => $newEndDateTime,
        ]);

        BookingProcessCourseDetails::where('booking_process_id', $booking_id)->update([
            'start_date' => $start_date,
            'end_date' => $end_date,
            'start_time' => $start_time,
            'end_time' => $end_time,
            'StartDate_Time' => $newStartDateTime,
            'EndDate_Time' => $newEndDateTime,
        ]);

        //Update new start time & end time in booking instructor page
        $bookingInstMapsIds = BookingInstructorDetailMap::where('booking_process_id', $booking_id)->get();
        foreach ($bookingInstMapsIds as $bookingInstMap) {
            $startdatetime = $bookingInstMap['startdate_time'];
            $enddatetime = $bookingInstMap['enddate_time'];
            $startdatetimeArray = explode(" ", $startdatetime);
            $enddatetimeArray = explode(" ", $enddatetime);
            $startdate = $startdatetimeArray[0];
            $enddate = $enddatetimeArray[0];

            $newstartDateTime = $startdate . " " . $start_time;
            $newendDateTime = $enddate . " " . $end_time;

            BookingInstructorDetailMap::where('id', $bookingInstMap['id'])->update([
                'startdate_time' => $newstartDateTime,
                'enddate_time' => $newendDateTime,
            ]);
        }

        // BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->update([
        //     'contact_id'=>$new_instructor_id
        // ]);

        if ($request->move_type == 'single') {

            //Check if instructor not assign then add new instructor for this booking
            $countdays = BookingInstructorDetailMap::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->count();
            if ($countdays <= 1) {
                //If New Instructor not in instructor table then add new other wise old instructor record delete
                $checkInstructorInBooking = BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $new_instructor_id)->count();
                if ($checkInstructorInBooking === 0) {
                    $instructor_detail_inputs['created_by'] = auth()->user()->id;
                    $instructor_detail_inputs['booking_process_id'] = $booking_id;
                    $instructor_detail_inputs['contact_id'] = $new_instructor_id;
                    BookingProcessInstructorDetails::create($instructor_detail_inputs);

                    //Delete Because if exits no duplicate instructor in this bookings
                    BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->delete();
                } else {
                    //Delete Because if exits no duplicate instructor in this bookings
                    BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->delete();

                    // BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->update([
                    //     'contact_id'=>$new_instructor_id
                    // ]);
                }
            } else {
                $checkInstructorInBooking = BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $new_instructor_id)->count();
                if ($checkInstructorInBooking === 0) {
                    $instructor_detail_inputs['created_by'] = auth()->user()->id;
                    $instructor_detail_inputs['booking_process_id'] = $booking_id;
                    $instructor_detail_inputs['contact_id'] = $new_instructor_id;
                    BookingProcessInstructorDetails::create($instructor_detail_inputs);
                } else {
                    BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->update([
                        'contact_id' => $new_instructor_id
                    ]);
                }
            }

            //Update Contact Id On Booking Instructor Detail Map Model
            BookingInstructorDetailMap::where('id', $request->booking_instructor_map_id)->where('contact_id', $old_instructor_id)->update([
                'contact_id' => $new_instructor_id
            ]);

            $bookinginstructorMap = BookingInstructorDetailMap::where('id', $request->booking_instructor_map_id)->first();

            $startdatetime = $bookinginstructorMap->startdate_time;
            $startdateArray = explode(" ", $startdatetime);
            $startdate = $startdateArray[0];

            $contact_old = Contact::find($old_instructor_id);
            //move instructor create & update timesheet if timesheet not available add new other wise update
            $timesheet = InstructorActivityTimesheet::where('booking_id', $booking_id)->where('instructor_id', $contact_old->user_detail->id)->where('activity_date', $startdate)->where('total_activity_hours', '!=', '00:00:00')->first();
            if ($timesheet) {
                //get for user id using new instructor contact id
                $contact = Contact::find($new_instructor_id);
                $data['instructor_id'] = $contact->user_detail->id; // refer to user table
                $data['booking_id'] = $booking_id;
                $data['created_by'] = auth()->user()->id; //login user id
                //old timesheet data
                $data['activity_date'] = $timesheet['activity_date'];
                $data['actual_start_time'] = $timesheet['actual_start_time'];
                $data['actual_end_time'] = $timesheet['actual_end_time'];
                $data['actual_hours'] = $timesheet['actual_hours'];
                InstructorActivityTimesheet::create($data);
            } else {
                //update new instructor id in the timesheet table
                $contact = Contact::find($new_instructor_id);
                InstructorActivityTimesheet::where('booking_id', $booking_id)->where('instructor_id', $contact_old->user_detail->id)->where('activity_date', $startdate)->update([
                    'instructor_id' => $contact->user_detail->id
                ]);
            }
        } else {

            //Update new instructor instead of old instructor id in booking instructor detail map & booking instructor detail model
            BookingInstructorDetailMap::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->update([
                'contact_id' => $new_instructor_id
            ]);

            //Update Contact id in instructor detail
            BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->where('contact_id', $old_instructor_id)->update([
                'contact_id' => $new_instructor_id
            ]);

            //Update Timesheet Detail new Contact id
            $contact_old = Contact::find($old_instructor_id); // get old instructor user id
            $timesheet_ids = InstructorActivityTimesheet::where('booking_id', $booking_id)->where('instructor_id', $contact_old->user_detail->id)->where('total_activity_hours', '=', '00:00:00')->where('status', '=', 'P')->pluck('id');

            $contact_new = Contact::find($new_instructor_id); // get new instructor user id
            InstructorActivityTimesheet::whereIn('id', $timesheet_ids)->update(['instructor_id' => $contact_new->user_detail->id]);
        }
        /**This logic for set instructor update then update mail assign then assign mail */
        $update = false;
        if ($new_instructor_id == $old_instructor_id) {
            $update = true;
        }
        /**End */
        /**For send push notification and email for the instructor and customer for any changes */
        $res = $this->sendUpdatesInstructorCustomer($request->booking_id, $new_instructor_id, $update);

        return $this->sendResponse(true, __('strings.booking_assigned_success'));
    }

    /* API for Booking Customer Invoice list */
    public function customerInvoiceList(Request $request)
    {
        $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
        $booking_processes_ids = BookingProcessPaymentDetails::query();
        if (empty($request->is_export)) {
            $contact_id = auth()->user()->contact_id;

            // if ($contact_id) {
            //     $contact = Contact::find($contact_id);
            //     if ($contact) {
            //         if ($contact->category_id==1) {
            //             $booking_processes_ids = $booking_processes_ids->where(function ($query) use ($contact_id) {
            //                 $query->where('customer_id', $contact_id);
            //                 $query->orWhere('payi_id', $contact_id);
            //             });
            //             /* where('customer_id',$contact_id)
            //             ->orWhere('payi_id',$contact_id); */
            //         }
            //     }
            // }

            if (auth()->user()->type() === 'Customer') {
                // show list where customer is payi.
                $booking_processes_ids = $booking_processes_ids->where('payi_id', $contact_id);

                // show list where customer is payi OR customer.
                /* ->where(function ($query) use ($contact_id) {
                    $query->where('customer_id', $contact_id);
                    $query->orWhere('payi_id', $contact_id);
                }); */
            }
        }

        $booking_processes_ids = $booking_processes_ids->orderBy('id', 'desc');
        if ($request->payment_status) {
            $booking_processes_ids = $booking_processes_ids->where('status', $request->payment_status);
        }

        if ($request->tax_consultant_status) {
            $booking_processes_ids = $booking_processes_ids->where('tax_consultant', $request->tax_consultant_status);
        }

        if ($request->date_range1 && $request->date_range2) {
            $booking_processes_ids = $booking_processes_ids->whereBetween('created_at', [$request->date_range1 . ' 00:00:00', $request->date_range2 . ' 23:59:59']);
        }

        if ($request->email_sent == 'YES') {
            $booking_processes_ids = $booking_processes_ids->where('no_invoice_sent', '>', 0);
        } elseif ($request->email_sent == 'NO') {
            $booking_processes_ids = $booking_processes_ids->where('no_invoice_sent', 0);
        }

        $booking_payment_data = array();
        $contact_ids = array();
        $sub_contact_ids = array();

        if ($request->search_keyword) {
            $search_keyword = $request->search_keyword;

            $contact_ids = Contact::where(function ($query) use ($search_keyword) {
                $query->where('first_name', 'like', "%$search_keyword%");
                $query->orWhere('middle_name', 'like', "%$search_keyword%");
                $query->orWhere('last_name', 'like', "%$search_keyword%");
            })->pluck('id');

            if (!count($contact_ids)) {
                /**For sub conatct base search */
                $sub_contact_ids = SubChildContact::where(function ($query) use ($search_keyword) {
                    $query->orWhere('first_name', 'like', "%$search_keyword%");
                    $query->orWhere('last_name', 'like', "%$search_keyword%");
                })->pluck('id');
            }

            if ($request->search_type == 'All' || $request->search_type == '') {
                $search_type = $request->search_type;

                if (!count($contact_ids)) {
                    $booking_ids = BookingProcessPaymentDetails::whereIn('sub_child_id', $sub_contact_ids)->pluck('booking_process_id')->toArray();
                } else {
                    $booking_ids = BookingProcessPaymentDetails::whereIn('customer_id', $contact_ids)->pluck('booking_process_id')->toArray();
                }

                $booking_ids1 = BookingProcessPaymentDetails::whereIn('payi_id', $contact_ids)
                    ->pluck('booking_process_id')
                    ->toArray();

                $courseIds = Course::where('name', 'like', "%$search_keyword%")
                    ->orWhere('name_en', 'like', "%$search_keyword%")
                    ->pluck('id');
                $courseDetailIds = CourseDetail::whereIn('course_id', $courseIds)->pluck('id')->toArray();
                $booking_ids2 = BookingProcessPaymentDetails::whereIn('course_detail_id', $courseDetailIds)->pluck('booking_process_id');

                //dd($booking_processes_ids->orderBy('id','desc')->get()->toArray());
                if (count($courseDetailIds) > 0) {
                    $booking_processes_ids = $booking_processes_ids->whereIn('course_detail_id', $courseDetailIds);
                } elseif ($contact_ids->count() == 0) {
                    $booking_processes_ids = $booking_processes_ids->Where(function ($query) use ($search_keyword) {
                        $query->where('invoice_number', 'like', "%$search_keyword%");
                    });
                } else {
                    $booking_ids = array_intersect($booking_ids, $booking_ids1);
                    $booking_processes_ids = $booking_processes_ids->whereIn('booking_process_id', $booking_ids)
                        ->whereIn('customer_id', $contact_ids);
                }
            } elseif ($request->search_type == 'Customer') {
                if (!count($contact_ids)) {
                    $booking_processes_ids = $booking_processes_ids->whereIn('sub_child_id', $sub_contact_ids);
                } else {
                    $booking_processes_ids = $booking_processes_ids->whereIn('customer_id', $contact_ids);
                }
            } elseif ($request->search_type == 'Payee') {
                $booking_processes_ids = $booking_processes_ids->whereIn('payi_id', $contact_ids);
            } elseif ($request->search_type == 'Course') {
                $courseIds = Course::where('name', 'like', "%$search_keyword%")
                    ->orWhere('name_en', 'like', "%$search_keyword%")
                    ->pluck('id');
                $courseDetailIds = CourseDetail::whereIn('course_id', $courseIds)->pluck('id');
                $booking_processes_ids = $booking_processes_ids->whereIn('course_detail_id', $courseDetailIds);
            } elseif ($request->search_type == 'Invoice') {
                if ($contact_ids->count() == 0) {
                    $booking_processes_ids = $booking_processes_ids->Where(function ($query) use ($search_keyword) {
                        $query->where('invoice_number', 'like', "%$search_keyword%");
                    });
                }
            }
        }

        if ($request->course_type) {
            $course_type = $request->course_type;
            $courseIds = Course::where('type', $course_type)->pluck('id');
            $courseDetailIds = CourseDetail::whereIn('course_id', $courseIds)->pluck('id');
            $booking_processes_ids = $booking_processes_ids->whereIn('course_detail_id', $courseDetailIds);
        }
        /**Payment card type and card brand base search */
        if ($request->payment_card_search) {
            $payment_card_search = $request->payment_card_search;
            $payment_ids = BookingPayment::where(function ($query) use ($payment_card_search) {
                $query->where('payment_card_type', 'like', "%$payment_card_search%");
                $query->orWhere('payment_card_brand', 'like', "%$payment_card_search%");
            })->pluck('id');
            $booking_processes_ids = $booking_processes_ids->whereIn('payment_id', $payment_ids);
        }

        /*** Payment Method Based Search ***/
        if ($request->payment_method_id) {
            $payment_method_id = $request->payment_method_id;
            $booking_processes_ids = $booking_processes_ids->where('payment_method_id', $payment_method_id);
        }

        /*** Payment Card Based Search ***/
        if ($request->payment_card) {
            $payment_card = $request->payment_card;
            $payment_ids = BookingPayment::where('payment_card_brand', $payment_card)->pluck('id');
            $booking_processes_ids = $booking_processes_ids->whereIn('payment_id', $payment_ids);
        }

        $booking_processes_count = $booking_processes_ids->count();

        $booking_processes_ids = $booking_processes_ids->skip($perPage * ($page - 1))->take($perPage);

        $booking_payment_data = $booking_processes_ids->with('payment_deta')
            ->with('customer.address.country_detail')
            ->with('payi_detail.address.country_detail')
            ->with('lead_datails.lead_data')
            ->with('lead_datails.course_data')
            ->with('booking_customer_datails')
            ->with('credit_card_detail')
            ->with(['payment_detail' => function ($query) {
                $query->select('id', 'payment_number', 'payment_card_type', 'payment_card_brand', 'beleg_uuid', 'beleg_nummer');
            }])
            ->with('sub_child_detail.allergies.allergy', 'sub_child_detail.languages.language')
            ->with('cancell_details')
            ->get();


        $data = [
            'booking_processes' => $booking_payment_data,
            'count' => $booking_processes_count
        ];

        if ($request->is_export) {
            return Excel::download(new CustomerInvoiceExport($booking_payment_data->toArray()), 'CustomerInvoice.csv');
        }

        return $this->sendResponse(true, 'success', $data);
    }

    public function getInvoice($id)
    {
        $query = BookingProcessPaymentDetails::with('customer')
            ->with('payi_detail')
            ->with('lead_datails.lead_data')
            ->with('lead_datails.course_data')
            ->with('booking_customer_datails')
            ->with('payment_detail.payment_type_detail')
            ->with('payment_detail.concardis_transaction')
            ->with(['booking_detail' => function ($query) {
                $query->select('id', 'booking_number');
            }])
            ->with(['course_detail' => function ($query) {
                $query->select('id', 'session', 'time');
            }])
            ->with('voucher')
            ->with('cancell_details')
            ->with('invoice_history.payment_detail')
            ->with('sub_child_detail.allergies.allergy', 'sub_child_detail.languages.language');

        if (strlen($id) === 50) {
            $invoice = $query->where('uuid', $id)->first();
        } else {
            $invoice = $query->find($id);
        }

        if (!$invoice) {
            return $this->sendResponse(false, __('strings.invoice_not_found'));
        }

        if (auth()->user()->type() === 'Customer') {
            if (auth()->user()->contact_detail->id !== $invoice->payi_id) {
                return $this->sendResponse(false, 'User is not authorized.');
            }
        }

        return $this->sendResponse(true, "Invoice data.", $invoice);
    }

    /* API for change Change TaxConsultant Status status */
    public function changeTaxConsultantStatus(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_payment_id' => 'integer|min:1',
            'tax_consultant_status' => 'in:Pending,Approved,Rejected'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_processes = BookingProcessPaymentDetails::where('id', $request->booking_process_payment_id);

        if (!$booking_processes) {
            return $this->sendResponse(false, __('strings.booking_process_payment_not_found'));
        }
        $booking_processes_update['tax_consultant'] = $request->tax_consultant_status;
        $booking_processes->update($booking_processes_update);

        /**Add crm user action trail */
        if ($booking_processes) {
            $action_id = $request->booking_process_payment_id; //Inoice id

            if ($request->tax_consultant_status == 'Approved') {
                $action_type = 'AP';
            } //AP = Approved
            elseif ($request->tax_consultant_status == 'Rejected') {
                $action_type = 'R';
            } //R = Rejected

            $module_id = 20; //module id base module table
            $module_name = "Customer Invoices"; //module name base module table
            $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
        }
        /**End manage trail */

        return $this->sendResponse(true, __('strings.change_tax_consultant_status_booking_process_success'));
    }

    /* API for change Change TaxConsultant Status status multiple */
    public function changeTaxConsultantStatusMultiple(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_payment_id' => 'required|array',
            'tax_consultant_status' => 'in:Approved,Rejected'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_processes = BookingProcessPaymentDetails::whereIn('id', $request->booking_process_payment_id);

        if ($booking_processes->count() == 0) {
            return $this->sendResponse(false, __('strings.booking_process_payment_not_found'));
        }
        $booking_processes->update(['tax_consultant' => $request->tax_consultant_status]);

        foreach ($booking_processes->get() as $invoice) {
            /**Add crm user action trail */
            if ($invoice) {
                $action_id = $invoice->id; //Inoice id

                if ($request->tax_consultant_status == 'Approved') {
                    $action_type = 'AP'; //AP= Approved
                } //AP = Approved
                elseif ($request->tax_consultant_status == 'Rejected') {
                    $action_type = 'R'; //R= Rejected
                } //R = Rejected

                $module_id = 20; //module id base module table
                $module_name = "Customer Invoices"; //module name base module table
                $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name);
            }
            /**End manage trail */
        }

        return $this->sendResponse(true, __('strings.change_tax_consultant_status_booking_process_success'));
    }

    /* API for send invoice again for particlaur customer */
    public function againSendInvoiceCustomer(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
            'customer_id' => 'required|integer|min:1',
            'payment_detail_id' => 'required|integer|min:1'
        ]);
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        $id = $request->booking_process_id;
        $customer_id = $request->customer_id;
        $booking_processes = BookingProcesses::find($id);
        $pdf_data['booking_no'] = $booking_processes->booking_number;
        $booking_processes_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $id)
            ->where('customer_id', $request->customer_id)->where('id', $request->payment_detail_id)
            ->orderBy('id', 'desc')->get();
        $booking_processes_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $id)
            ->where('customer_id', $request->customer_id)
            ->orderBy('id', 'desc')->get();
        $i = 0;
        $customer = Contact::find($booking_processes_payment_details[0]['payi_id']);
        $booking_number = $booking_processes->booking_number;
        $invoice_number = $booking_processes_payment_details[0]['invoice_number'];
        $payment_status = $booking_processes_payment_details[0]['status'];

        $template_data = [
            'customer_name' => $customer_name = ucfirst($customer->salutation) . " " . ucfirst($customer->first_name) . " " . ucfirst($customer->last_name),
            'booking_number' => $booking_number,
            'invoice_number' => $invoice_number,
            'payment_status' => $payment_status
        ];

        $course = DB::table('booking_process_course_details')->join('courses as c', 'c.id', '=', 'booking_process_course_details.course_id')
            ->where('booking_process_course_details.booking_process_id', $id)
            ->first();

        foreach ($booking_processes_payment_details as $key => $payment_detail) {
            //foreach ($booking_processes_customer_details as $key => $customer_detail) {
            $contact_data = Contact::with('address.country_detail:id,name,code')->find($request->customer_id);

            /**If sub child exist */
            if (isset($payment_detail->sub_child_id) && $payment_detail->sub_child_id) {
                $contact_data = SubChildContact::find($payment_detail->sub_child_id);
                $pdf_data['customer'][$i]['customer_name'] = $contact_data->first_name . " " . $contact_data->last_name;
            } else {
                $pdf_data['customer'][$i]['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;
            }

            $customer_email = $contact_data->email;
            $contact = Contact::with('address.country_detail:id,name,code')->find($payment_detail->payi_id);

            $contact_address = ContactAddress::with('country_detail')->where('contact_id', $payment_detail->payi_id)->first();

            ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . $contact_address->country_detail->name . "." : $address = "";

            $pdf_data['customer'][$i]['payi_id'] = $payment_detail->payi_id;
            $pdf_data['customer'][$i]['payi_name'] = $contact['salutation'] . "" . $contact['first_name'] . " " . $contact['last_name'];
            $pdf_data['customer'][$i]['payi_address'] = $address;
            $pdf_data['customer'][$i]['payi_contact_no'] = $contact['mobile1'];
            $pdf_data['customer'][$i]['payi_email'] = $contact['email'];
            $pdf_data['customer'][$i]['no_of_days'] = $payment_detail->no_of_days;
            $pdf_data['customer'][$i]['refund_payment'] = $payment_detail->refund_payment;
            /* $pdf_data['customer'][$i]['StartDate_Time'] = $customer_detail->StartDate_Time;
            $pdf_data['customer'][$i]['EndDate_Time'] = $customer_detail->EndDate_Time; */
            $pdf_data['customer'][$i]['total_price'] = $payment_detail->total_price;
            $pdf_data['customer'][$i]['extra_participant'] = $payment_detail->extra_participant;
            $pdf_data['customer'][$i]['discount'] = $payment_detail->discount;
            $pdf_data['customer'][$i]['net_price'] = $payment_detail->net_price;
            $pdf_data['customer'][$i]['vat_percentage'] = $payment_detail->vat_percentage;
            $pdf_data['customer'][$i]['vat_amount'] = $payment_detail->vat_amount;
            $pdf_data['customer'][$i]['vat_excluded_amount'] = $payment_detail->vat_excluded_amount;
            $pdf_data['customer'][$i]['invoice_number'] = $payment_detail->invoice_number;
            $pdf_data['customer'][$i]['invoice_date'] = $payment_detail->created_at;
            $pdf_data['customer'][$i]['lunch_vat_amount'] = $payment_detail->lunch_vat_amount;
            $pdf_data['customer'][$i]['lunch_vat_excluded_amount'] = $payment_detail->lunch_vat_excluded_amount;
            $pdf_data['customer'][$i]['is_include_lunch'] = $payment_detail->is_include_lunch;
            $pdf_data['customer'][$i]['include_lunch_price'] = $payment_detail->include_lunch_price;
            $pdf_data['customer'][$i]['lunch_vat_percentage'] = $payment_detail->lunch_vat_percentage;
            $pdf_data['customer'][$i]['payment_status'] = $payment_detail->status;
            $pdf_data['customer'][$i]['settlement_amount'] = $payment_detail->settlement_amount;
            $pdf_data['customer'][$i]['settlement_description'] = $payment_detail->settlement_description;
            $pdf_data['customer'][$i]['outstanding_amount'] = $payment_detail->outstanding_amount;
            $pdf_data['customer'][$i]['is_reverse_charge'] = $payment_detail->is_reverse_charge;
            $pdf_data['customer'][$i]['course_name'] = ($course ? $course->name : null);

            //}
            $i++;
        }

        $pdf = PDF::loadView('bookingProcess.customer_invoice', $pdf_data);

        $booking_processes_payment_deta = $booking_processes_payment_details->toArray();
        if ($booking_processes_payment_deta[0]['status'] == 'Pending') {
            $no_invoice_sent = $booking_processes_payment_deta[0]['no_invoice_sent'];
            $no_invoice_sent = $no_invoice_sent + 1;
            $update_data['no_invoice_sent'] = $no_invoice_sent;
            $booking_processes_payment_details[0]->update($update_data);
            $template_data['payment_link'] = $booking_processes_payment_deta[0]['payment_link'];
        }

        /**Get default locale and set user language locale */
        $temp_locale = \App::getLocale();
        $user = User::where('email', $contact['email'])->first();
        if ($user) {
            \App::setLocale($user->language_locale);
        }
        /**End */

        Mail::send('email.customer.send_again_customer_invoice', $template_data, function ($message) use ($pdf_data, $pdf, $booking_number, $invoice_number) {
            $message->to($pdf_data['customer'][0]['payi_email'], $pdf_data['customer'][0]['payi_name'])
                ->subject(__('email_subject.booking_invoice', ['booking_number' => $booking_number, 'invoice_number' => $invoice_number]))
                ->attachData($pdf->output(), $invoice_number . "_Invoice.pdf");
        });

        /**Set default language locale */
        \App::setLocale($temp_locale);

        /**
         * Date: 17-09-2020
         * Description : Now not need to customer receive payment invoice
         */
        // if ($payment_detail->payi_id!=$customer_id) {
        //     Mail::send('email.customer.send_again_customer_invoice', $template_data, function ($message) use ($pdf_data,$pdf,$customer_email,$booking_number,$invoice_number) {
        //         $message->to($customer_email, $pdf_data['customer'][0]['customer_name'])
        //         ->subject("Your booking #".$booking_number." invoice ".$invoice_number)
        //         ->attachData($pdf->output(), $invoice_number."_invoice.pdf");
        //     });
        // }
        // return $pdf->download('CustomerInvoice.pdf');
        return $this->sendResponse(true, __('strings.send_invoice_again_sucess'));
    }

    /* Ongoing course details with booking details */
    public function getOngoingCourseDetail(Request $request)
    {
        /* $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage;
 */
        $current_date = date('Y-m-d H:i:s');
        $contact_ids = array();

        $course_ids = array();

        $booking_process_ids = array();
        $booking_processes = BookingProcessCourseDetails::query();


        if ($request->search) {
            $search = $request->search;
            $contact_ids = Contact::whereIn('category_id', [1, 2]) //1: Customer, 2: Instructor
                ->where(function ($query) use ($search) {
                    $query->orWhere('salutation', 'like', "%$search%");
                    $query->orWhere('first_name', 'like', "%$search%");
                    $query->orWhere('middle_name', 'like', "%$search%");
                    $query->orWhere('last_name', 'like', "%$search%");
                })
                ->pluck('id');

            /**Search in instructor */
            if ($contact_ids) {
                $booking_process_ids = BookingProcessInstructorDetails::whereIn('contact_id', $contact_ids)->pluck('booking_process_id');
            }

            /**If search not found instructor then search on customer */
            if (!count($booking_process_ids)) {
                $booking_process_ids = BookingProcessCustomerDetails::whereIn('customer_id', $contact_ids)->pluck('booking_process_id');
            }

            $course_ids = Course::where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
                ->pluck('id');

            $booking_processes = $booking_processes
                ->where(function ($query) use ($course_ids, $booking_process_ids) {
                    $query->whereIn('course_id', $course_ids)
                        ->orWhereIn('booking_process_id', $booking_process_ids);
                });
        }

        $booking_processes = $booking_processes->where(function ($query) use ($current_date) {
            $query->where('StartDate_Time', '>', $current_date);
            $query->orWhere('EndDate_Time', '>=', $current_date);
        })
            ->pluck('booking_process_id');

        $booking_processes_details = BookingProcesses::with(['course_detail.course_data'])
            ->with([
                'customer_detail.customer', 'customer_detail.sub_child_detail.allergies.allergy',
                'customer_detail.sub_child_detail.languages.language'
            ])
            ->with(['instructor_detail.contact.languages.language' => function ($query) {
                $query->select('id', 'name');
            }, 'instructor_detail.contact.allergies.allergy' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['language_detail.language' => function ($query) {
                $query->select('id', 'name');
            }])
            ->whereIn('id', $booking_processes)
            ->get();

        return $this->sendResponse(true, 'success', $booking_processes_details);
    }

    /* Runing course details with booking details */
    public function getRuningCourseDetail(Request $request)
    {
        /* $v = validator($request->all(), [
            'page' => 'required|integer|min:1',
            'perPage' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $page = $request->page;
        $perPage = $request->perPage; */

        $current_date = date('Y-m-d H:i:s');
        $contact_ids = array();
        $course_ids = array();

        $booking_process_ids = array();
        $booking_processes = BookingProcessCourseDetails::query();

        if ($request->search) {
            $search = $request->search;
            $contact_ids = Contact::whereIn('category_id', [1, 2]) //1: Customer, 2: Instructor
                ->where(function ($query) use ($search) {
                    $query->orWhere('salutation', 'like', "%$search%");
                    $query->orWhere('first_name', 'like', "%$search%");
                    $query->orWhere('middle_name', 'like', "%$search%");
                    $query->orWhere('last_name', 'like', "%$search%");
                })
                ->pluck('id');

            /**Search in instructor */
            if ($contact_ids) {
                $booking_process_ids = BookingProcessInstructorDetails::whereIn('contact_id', $contact_ids)->pluck('booking_process_id');
            }

            /**If search not found instructor then search on customer */
            if (!count($booking_process_ids)) {
                $booking_process_ids = BookingProcessCustomerDetails::whereIn('customer_id', $contact_ids)->pluck('booking_process_id');
            }

            $course_ids = Course::where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
            })
                ->pluck('id');

            $booking_processes = $booking_processes
                ->where(function ($query) use ($course_ids, $booking_process_ids) {
                    $query->whereIn('course_id', $course_ids)
                        ->orWhereIn('booking_process_id', $booking_process_ids);
                });
        }

        $booking_processes = $booking_processes->where(function ($query) use ($current_date) {
            $query->where('StartDate_Time', '<=', $current_date);
            $query->Where('EndDate_Time', '>=', $current_date);
        })
            ->pluck('booking_process_id');


        $booking_processes_details = BookingProcesses::with(['course_detail.course_data'])
            ->with([
                'customer_detail.customer', 'customer_detail.sub_child_detail.allergies.allergy',
                'customer_detail.sub_child_detail.languages.language'
            ])
            ->with(['instructor_detail.contact.languages.language' => function ($query) {
                $query->select('id', 'name');
            }, 'instructor_detail.contact.allergies.allergy' => function ($query) {
                $query->select('id', 'name');
            }])
            ->with(['language_detail.language' => function ($query) {
                $query->select('id', 'name');
            }])
            ->whereIn('id', $booking_processes)
            ->get();

        $course_count = [
            'confirmed' => 0,
            'ended' => 0,
            'unconfirmed' => 0,
        ];
        foreach ($booking_processes_details as $booking_processes_detail) {
            $booking_processes_detail['ended'] = 0;
            $booking_processes_detail['unconfirmed'] = 0;
            $booking_processes_detail['confirmed'] = 0;
            if (($booking_processes_detail['course_detail']) && ($booking_processes_detail['course_detail']['end_date'] == date("Y-m-d"))) {
                $booking_processes_detail['ended'] = 1;
                $booking_processes_detail['course_status'] = "Ended Today's Course";
                $course_count['ended'] += 1;
            } else {
                $unconfirmed_status = false;
                if (count($booking_processes_detail['instructor_detail']) === 0) {
                    $unconfirmed_status = true;
                }
                foreach ($booking_processes_detail['instructor_detail'] as $instructor) {
                    if (!$instructor['is_course_confirmed']) {
                        $unconfirmed_status = true;
                        break;
                    }
                }
                if ($unconfirmed_status) {
                    $booking_processes_detail['unconfirmed'] = 1;
                    $booking_processes_detail['course_status'] = "Unconfirmed Start";
                    $course_count['unconfirmed'] += 1;
                } else {
                    $booking_processes_detail['confirmed'] = 1;
                    $booking_processes_detail['course_status'] = "Confirmed Start";
                    $course_count['confirmed'] += 1;
                }
            }
        };
        $data = [
            'course_count' => $course_count,
            'booking_detail' => $booking_processes_details
        ];
        return $this->sendResponse(true, 'success', $data);
    }

    /* API for send invoice again for particlaur customer */
    public function againSendCourseAlertInstructor(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
            'instructor_id' => 'required|integer|min:1',
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $current_date = date("Y-m-d H:i:s");

        $booking_data = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)
            ->first();

        $start_date_time = new DateTime($booking_data->StartDate_Time);

        $interval = Carbon::parse($current_date)->diff($start_date_time);

        $this->sendNotificationCourseConfirm($request->booking_process_id, $request->instructor_id, $interval);

        return $this->sendResponse(true, __('strings.send_course_alert_sucess'));
    }

    public function testForBookingUpdateEmail(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|integer|min:1',
        ]);

        $emails_sent = $this->customer_update_booking1($request->booking_process_id);

        return $this->sendResponse(true, 'Success');
    }

    /* API for Update Booking Process Invoice link */
    public function updateInvoiceLink($id)
    {
        /**For update payment invoice link */
        $url = $this->upatePayementInvoiceLink($id);
        return $url;
    }

    /** Course Detail Id base Get Bookings for Transfer Customer
     * $id = course_detail_id
     */
    public function getBookingsCourseDetailBase($id)
    {
        $current_date = date('Y-m-d H:i:s');
        $course_detail = CourseDetail::find($id);
        $course_details_ids = CourseDetail::where('course_id', $course_detail->course_id)
            ->where('session', $course_detail->session)
            ->where('time', $course_detail->time)
            ->where('cal_payment_type', $course_detail->cal_payment_type)->pluck('id');

        $booking_ids = BookingProcessCustomerDetails::whereIn('course_detail_id', $course_details_ids)
            ->pluck('booking_process_id');

        $booking_ids = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_ids)
            ->where('course_type', 'Group')
            ->where(function ($query) use ($current_date) {
                $query->where('StartDate_Time', '<=', $current_date);
                $query->where('EndDate_Time', '>', $current_date);
            })
            ->pluck('booking_process_id');

        $booking_data = BookingProcesses::whereIn('id', $booking_ids)
            ->where('is_trash', 0)
            ->with('course_detail.course_data')
            ->select('id', 'is_trash', 'booking_number')->get();
        return $this->sendResponse(true, 'Success', $booking_data);
    }

    /* API for Send Booking Detail to Instructor */
    public function sendBookingDetailtoInstructor(Request $request)
    {
        $v = validator($request->all(), [
            'booking_id' => 'required|integer'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_id = $request->booking_id;

        $BookingInstructorIds =  BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->pluck('contact_id');

        $bookingInstructorIdsArray = $BookingInstructorIds->toArray();

        //get course name
        $course_id = BookingProcessCourseDetails::where('booking_process_id', $booking_id)->first()->course_id;
        $course = Course::find($course_id);
        $course_name = $course->name;

        foreach ($bookingInstructorIdsArray as $bookingInstructorId) {


            $instructor = Contact::find($bookingInstructorId);
            if ($instructor) {
                $booking_processes = BookingProcesses::find($booking_id);
                $booking_course_details = BookingProcessCourseDetails::where('booking_process_id', $booking_id)->first();
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
        }

        return $this->sendResponse(true, __('strings.booking_detail_to_instructor'));
    }

    /* API for Transfer customer to another booking(course) for by admin */
    public function transferBookingCustomer(Request $request)
    {
        $v = validator($request->all(), [
            'qr_number' => 'required|integer',
            'new_booking_id' => 'required|integer'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_id = $request->new_booking_id;
        $qr_number = $request->qr_number;

        /**Old booking datails */
        $oldBookingDetail =  BookingProcessCustomerDetails::where('QR_number', $qr_number)->get();

        if (!count($oldBookingDetail)) {
            return $this->sendResponse(false, __('strings.booking_not_found'));
        }
        $oldBookingPaymentDetail =  BookingProcessPaymentDetails::where('customer_id', $oldBookingDetail[0]->customer_id)
            ->where('booking_process_id', $oldBookingDetail[0]->booking_process_id)
            ->get();

        $oldBookingCourseDetail =  BookingProcessCourseDetails::where('booking_process_id', $oldBookingDetail[0]->booking_process_id)->first();
        $old_booking_process_id = $oldBookingDetail[0]->booking_process_id;

        if (!$oldBookingPaymentDetail || !$oldBookingCourseDetail) {
            return $this->sendResponse(false, __('strings.booking_not_found'));
        }
        /**End  */

        /**New booking datails */
        $newBookingDetail =  BookingProcessCustomerDetails::where('booking_process_id', $booking_id)->first();
        $newBookingCustomer =  BookingProcessCustomerDetails::where('booking_process_id', $booking_id)->pluck('customer_id')->toArray();

        $totalStrudents =  BookingProcessCustomerDetails::where('booking_process_id', $booking_id)->count();
        $newBookingCourse =  BookingProcessCourseDetails::where('booking_process_id', $booking_id)->first();
        $newBookingInstructorIds =  BookingProcessInstructorDetails::where('booking_process_id', $booking_id)->pluck('contact_id');
        $exist_instructor_bookings = BookingProcessInstructorDetails::whereIn('contact_id', $newBookingInstructorIds)
            ->where('booking_process_id', '!=', $booking_id)
            ->pluck('booking_process_id');

        $currentDateTime = date('Y-m-d H:i:s');
        $groupLimit = (int)config('constants.STUDENT_LIMIT');
        /**End  */

        if (!$newBookingCourse || !$newBookingDetail) {
            return $this->sendResponse(false, __('strings.booking_not_found'));
        }
        $old_customer_id = $oldBookingDetail[0]->customer_id;

        if (in_array($old_customer_id, $newBookingCustomer)) {
            return $this->sendResponse(false, __('strings.participant_exist'));
        }

        if ($totalStrudents >= $groupLimit) {
            return $this->sendResponse(false, __('strings.maximum_group_limit', ['limit' => $groupLimit]));
        }

        $oldBookingCourse = $oldBookingDetail[0]->bookingProcessCourseDetails;
        $oldCourseDetail = $oldBookingDetail[0]->course_detail_data;
        $newCourseDetail = $newBookingDetail->course_detail_data;
        if (!$oldBookingCourse || !$oldCourseDetail || !$newCourseDetail) {
            return $this->sendResponse(false, __('strings.booking_course_not_found'));
        }

        if ($oldBookingDetail[0]->booking_process_id === $newBookingDetail->booking_process_id) {
            return $this->sendResponse(false, __('strings.both_booking_not_same_by_admin'));
        }

        /**Get old customer startdate and enddate if customer booking extend so this logic is used */
        $strt_date = $oldBookingDetail[0]->start_date;
        $end_date = $oldBookingDetail[0]->end_date;

        foreach ($oldBookingDetail as $booking) {
            if ($booking->start_date < $strt_date) {
                $strt_date = $booking->start_date;
            }
            if ($booking->end_date > $end_date) {
                $end_date = $booking->end_date;
            }
            $strt_date_time = $strt_date . ' ' . $booking->start_time;
            $strt_date_time = strtotime($strt_date_time);
            $new_strt_date_time = date("Y-m-d H:i:s", $strt_date_time);

            $end_date_time = $end_date . ' ' . $booking->end_time;
            $end_date_time = strtotime($end_date_time);
            $new_end_date_time = date("Y-m-d H:i:s", $end_date_time);
        }
        /**End */
        $booking_numbers = [];

        if ($exist_instructor_bookings) {
            $exist_instructor_bookings = BookingProcessCourseDetails::whereIn('booking_process_id', $exist_instructor_bookings);
            $exist_instructor_bookings1 =  BookingProcessCourseDetails::Where(function ($query) use ($new_strt_date_time) {
                $query->where('StartDate_Time', '<=', $new_strt_date_time);
                $query->where('EndDate_Time', '>=', $new_strt_date_time);
            })
                ->orWhere(function ($query) use ($new_end_date_time) {
                    $query->where('StartDate_Time', '<=', $new_end_date_time);
                    $query->where('EndDate_Time', '>=', $new_end_date_time);
                })->pluck('booking_process_id');

            $exist_instructor_bookings1 = $exist_instructor_bookings->whereIn('booking_process_id', $exist_instructor_bookings1)->pluck('booking_process_id');
            $booking_numbers = BookingProcesses::whereIn('id', $exist_instructor_bookings1)->where('is_trash', 0)->pluck('booking_number');
        }

        if (count($booking_numbers) > 0) {
            return $this->sendResponse(true, __('strings.instructor_already_assign'), $booking_numbers);
        }

        if (($oldBookingCourseDetail->StartDate_Time > $currentDateTime) || ($newBookingCourse->StartDate_Time > $currentDateTime) || ($oldBookingCourseDetail->EndDate_Time < $currentDateTime) || ($newBookingCourse->EndDate_Time < $currentDateTime)) {
            return $this->sendResponse(false, __('strings.both_booking_ongoing_by_admin'));
        }

        if (($newBookingCourse->course_type === 'Private') || ($oldBookingCourse->course_type === 'Private')) {
            return $this->sendResponse(false, __('strings.both_booking_not_private_by_admin'));
        }

        if ($newBookingCourse->course_id !== $oldBookingCourse->course_id) {
            return $this->sendResponse(false, __('strings.both_course_same_by_admin'));
        }

        if (($oldCourseDetail->session !== $newCourseDetail->session) || ($oldCourseDetail->time !== $newCourseDetail->time)) {
            return $this->sendResponse(false, __('strings.both_course_same_by_admin'));
        }

        /** Old Booking Customer details update with new booking id */
        foreach ($oldBookingDetail as $booking) {
            $booking->booking_process_id = $booking_id;
            $booking->save();
        }

        /**Old Booking Payment details update with new booking id */
        foreach ($oldBookingPaymentDetail as $booking_payment) {
            $booking_payment->booking_process_id = $booking_id;
            $booking_payment->save();
        }

        if ($strt_date < $newBookingCourse->start_date) {
            $updateCourseDetail['start_date'] = $strt_date;
        } elseif ($end_date > $newBookingCourse->end_date) {
            $updateCourseDetail['end_date'] = $end_date;
        }

        /** New Booking update main general startdate, enddate , starttime, endtime, StartDate_Time , EndDate_Time fileds */
        if (!empty($updateCourseDetail['start_date'])) {
            $start_date1 = $updateCourseDetail['start_date'] . ' ' . $newBookingCourse->start_time;
            $start_date1 = strtotime($start_date1);
            $new_start_date = date("Y-m-d H:i:s", $start_date1);
            $newBookingCourse->StartDate_Time = $new_start_date;
            $newBookingCourse->start_date = $new_start_date;
            $tempStrt = explode(" ", $new_strt_date_time);
            if ($tempStrt[1] < $newBookingCourse->start_time) {
                $start_time = $tempStrt[1];
                $start_date1 = $updateCourseDetail['start_date'] . ' ' . $start_time;
                $start_date1 = strtotime($start_date1);
                $new_start_date_time = date("Y-m-d H:i:s", $start_date1);
                $newBookingCourse->StartDate_Time = $new_start_date_time;
                $newBookingCourse->start_time = $start_time;
            }
        }

        if (!empty($updateCourseDetail['end_date'])) {
            $end_date1 = $updateCourseDetail['end_date'] . ' ' . $newBookingCourse->end_time;
            $end_date1 = strtotime($end_date1);
            $new_end_date = date("Y-m-d H:i:s", $end_date1);
            $newBookingCourse->EndDate_Time = $new_end_date;
            $newBookingCourse->end_date = $new_end_date;
            $tempEnd = explode(" ", $new_end_date_time);
            if ($tempEnd[1] > $newBookingCourse->end_time) {
                $end_time = $tempEnd[1];
                $end_date1 = $updateCourseDetail['end_date'] . ' ' . $end_time;
                $end_date1 = strtotime($end_date1);
                $new_end_date_time = date("Y-m-d H:i:s", $end_date1);
                $newBookingCourse->EndDate_Time = $new_end_date_time;
                $newBookingCourse->end_time = $end_time;
            }
        }
        $newBookingCourse->save();
        /**End  */

        $oldBookingCustomerDetail =  BookingProcessCustomerDetails::where('booking_process_id', $old_booking_process_id)->get();

        $data = ['new_booking_id' => $booking_id];

        if ($oldBookingCustomerDetail->count() > 0) {
            /**Update Old Booking general dates fields */
            $old_start_time = $oldBookingCustomerDetail[0]->StartDate_Time;
            $old_end_time = $oldBookingCustomerDetail[0]->EndDate_Time;

            foreach ($oldBookingCustomerDetail as $customer_detail) {
                if ($customer_detail->StartDate_Time < $old_start_time) {
                    $old_start_time = $customer_detail->StartDate_Time;
                }
                if ($customer_detail->EndDate_Time > $old_end_time) {
                    $old_end_time = $customer_detail->EndDate_Time;
                }
                $old_strt = explode(" ", $old_start_time);
                $old_end = explode(" ", $old_end_time);
            }
            $oldBookingCourseDetail->StartDate_Time = $old_start_time;
            $oldBookingCourseDetail->EndDate_Time = $old_end_time;
            $oldBookingCourseDetail->start_date = $old_strt[0];
            $oldBookingCourseDetail->end_date = $old_end[0];
            $oldBookingCourseDetail->start_time = $old_strt[1];
            $oldBookingCourseDetail->end_time = $old_end[1];
            $oldBookingCourseDetail->save();

            if ($oldBookingDetail[0]->customer && $oldBookingDetail[0]->customer->user_detail) {
                $user_detail = $oldBookingDetail[0]->customer->user_detail;
                $type = 11;
                $title = "Course Transfered!";
                $body =  "Congratulations! Your ongoing course is now transfered";
                SendPushNotification::dispatch($user_detail['device_token'], $user_detail['device_type'], $title, $body, $type, $data);
            }
            /**End */
        } else {
            /**Booking soft delete if last participate is transfered to another booking */
            $delete_booking = $this->deleteBookingProcess($old_booking_process_id);
        }

        return $this->sendResponse(true, __('strings.booking_transfer_success'), $data);
    }

    /* Check validation for adding/updating contact */
    public function checkValidation($request)
    {
        $v = validator($request->all(), [

            'course_detail' => 'array',
            'course_detail.booking_process_id' => 'integer|min:1',
            'course_detail.course_type' => 'max:20',
            'course_detail.course_id' => 'integer|min:1',
            //'course_detail.course_detail_id' => 'integer|min:1',
            'course_detail.lead' => 'nullable|integer|min:1',
            'course_detail.contact_id' => 'nullable|integer|min:1',
            'course_detail.source_id' => 'nullable|integer|min:0|nullable',
            'course_detail.no_of_instructor' => 'nullable|integer|min:1',
            'course_detail.no_of_participant' => 'nullable|integer|min:1',
            'course_detail.meeting_point' => 'required|max:250',
            'course_detail.meeting_point_lat' => 'required|numeric|nullable',
            'course_detail.meeting_point_long' => 'required|numeric|nullable',
            'course_detail.lunch_hour' => 'nullable|min:1',

            'customer_detail' => 'array',
            'customer_detail.*.booking_process_id' => 'integer|min:1',
            'customer_detail.*.additional_participant' => 'max:50',
            'customer_detail.*.accommodation' => 'max:50',
            'customer_detail.*.StartDate_Time' => 'date',
            'customer_detail.*.EndDate_Time' => 'date',
            'customer_detail.*.payi_id' => 'nullable|integer|min:1',
            'customer_detail.*.sub_child_id' => 'nullable|integer',
            'customer_detail.*.course_detail_id' => 'nullable|integer|min:1',
            'customer_detail.*.no_of_days' => 'nullable|integer',
            'customer_detail.*.hours_per_day' => 'nullable|integer',
            'customer_detail.*.is_payi' => 'nullable|in:Yes,No',
            'customer_detail.*.cal_payment_type' => 'nullable|in:PH,PD,PIS',
            'customer_detail.*.is_include_lunch' => 'nullable|in:1,0',
            'customer_detail.*.include_lunch_price' => 'nullable|numeric',
            'customer_detail.*.is_include_lunch_hour' => 'nullable|boolean',

            'instructor_detail' => 'array',
            'language_detail' => 'array',

            'additional_information.note' => 'max:200',

            'payment_detail' => 'array',
            'payment_detail.*.payi_id' => 'nullable|integer|min:1',
            'payment_detail.*.customer_id' => 'nullable|integer|min:1',
            'payment_detail.*.sub_child_id' => 'nullable|integer',
            'payment_detail.*.payment_method_id' => 'nullable|integer',
            'payment_detail.*.credit_card_type' => 'nullable|integer',
            'payment_detail.*.no_of_days' => 'nullable|integer|min:1',
            'payment_detail.*.course_detail_id' => 'nullable|integer|min:1',
            'payment_detail.*.cal_payment_type' => 'nullable|in:PH,PD,PIS',
            'payment_detail.*.is_include_lunch' => 'nullable|in:1,0',
            'payment_detail.*.include_lunch_price' => 'nullable|numeric',
            'payment_detail.*.lunch_vat_amount' => 'nullable',
            'payment_detail.*.lunch_vat_excluded_amount' => 'nullable',
            'payment_detail.*.lunch_vat_percentage' => 'nullable',
            'payment_detail.*.settlement_amount' => 'nullable',
            'payment_detail.*.settlement_description' => 'nullable',
            'language_detail' => 'array',

        ]);
        return $v;
    }

    /* Check validation for Draft adding/updating Booking */
    public function checkValidationDraft($request)
    {
        $v = validator($request->all(), [
            'course_detail.course_type' => 'required|max:20',
            'course_detail.course_id' => 'required|integer|min:1',

            'customer_detail' => 'required|array',
            'customer_detail.*.booking_process_id' => 'integer|min:1',
            'customer_detail.*.additional_participant' => 'max:50',
            'customer_detail.*.accommodation' => 'max:50',
            'customer_detail.*.StartDate_Time' => 'date',
            'customer_detail.*.EndDate_Time' => 'date',
            'customer_detail.*.payi_id' => 'nullable|integer|min:1',
            'customer_detail.*.sub_child_id' => 'nullable|integer',
            'customer_detail.*.course_detail_id' => 'nullable|integer|min:1',
            'customer_detail.*.no_of_days' => 'nullable|integer',
            'customer_detail.*.hours_per_day' => 'nullable|integer',
            'customer_detail.*.is_payi' => 'nullable|in:Yes,No',
            'customer_detail.*.is_include_lunch_hour' => 'nullable|boolean',

            'language_detail' => 'required|array',
        ]);
        return $v;
    }

    public function callObonoApi()
    {
        $res = $this->callObonoApiTest();
        return $res;
    }

    /*Get same booking list for merge booking time
     *Date : 21-08-2020
     *Same Booking criteria : course id, course type, booking start and end date
     */
    public function getCommonBookingList(Request $request)
    {
        $v = validator($request->all(), [
            /**Rules */
            'course_id' => 'required|exists:courses,id,deleted_at,NULL',
            'course_type' => 'required|in:Group,Private,Other',
            'start_date_time' => 'required|date_format:Y-m-d H:i:s',
            'end_date_time' => 'required|date_format:Y-m-d H:i:s|after_or_equal:start_date_time',
            'merge_booking_id' => 'required|exists:booking_processes,id,deleted_at,NULL'
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }
        /**Start date time, end date time, course and coure type base get common bookings  */
        $sameBookingIds = BookingProcessCourseDetails::where('course_id', $request->course_id)
            ->where('course_type', $request->course_type)
            ->where('StartDate_Time', '>=', $request->start_date_time)
            ->where('EndDate_Time', '<=', $request->end_date_time)
            ->where('booking_process_id', '!=', $request->merge_booking_id)
            ->pluck('booking_process_id');

        if (auth()->user()->type() === 'Instructor') {
            $contact_id = auth()->user()->contact_id;
            $sameBookingIds = BookingProcessInstructorDetails::where('contact_id', $contact_id)
                ->whereIn('booking_process_id', $sameBookingIds)->pluck('booking_process_id');
        }

        /**Get booking customer details */
        $bookingCustomerDetais = BookingProcesses::whereIn('id', $sameBookingIds)
            ->where('is_trash', false)
            ->with(
                'customer_detail.customer',
                'customer_detail.payi_detail',
                'course_detail.course_data',
                'customer_detail.sub_child_detail.allergies.allergy',
                'customer_detail.sub_child_detail.languages.language'
            )
            ->get();;

        /**Return success response with booking details */
        return $this->sendResponse(true, __('strings.get_message', ['name' => 'Same booking customer']), $bookingCustomerDetais);
    }

    /*Merge booking customer in another booking
     *Date : 21-08-2020
     */
    public function mergeBooking(Request $request)
    {
        $v = validator($request->all(), [
            /**Rules */
            'merge_booking_id' => 'required|exists:booking_processes,id,deleted_at,NULL',
            'merge_bookings' => 'required|array',
            'merge_bookings.*.booking_id' => 'required|exists:booking_processes,id,deleted_at,NULL',
            'merge_bookings.*.customers' => 'required|array',
        ], [
            /**Dynamic validation strings for array objects */
            'merge_bookings.*.booking_id.required' => __('strings.required_validation', ['name' => 'booking id in merge bookings']),
            'merge_bookings.*.booking_id.exists' => __('strings.exist_validation', ['name' => 'booking id in merge bookings']),
            'merge_bookings.*.customers.required' => __('strings.required_validation', ['name' => 'customers in merge bookings']),
            'merge_bookings.*.customers.array' => __('strings.array_validation', ['name' => 'customers in merge bookings']),
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        foreach ($request->merge_bookings as $booking) {
            foreach ($booking['customers'] as $customer) {
                /**Check customer is exist in booking or not if not then error response */
                $bookingCustomerDetais = BookingProcessCustomerDetails::where('booking_process_id', $booking['booking_id'])->where('customer_id', $customer)->get();

                $bookingPaymentDetais = BookingProcessPaymentDetails::where('booking_process_id', $booking['booking_id'])->where('customer_id', $customer)->get();

                if (!count($bookingCustomerDetais) || !count($bookingPaymentDetais)) {
                    return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Booking customer / payment details are']));
                }
                /**End */

                BookingProcessCustomerDetails::where('booking_process_id', $booking['booking_id'])
                    ->where('customer_id', $customer)
                    ->update(['booking_process_id' => $request->merge_booking_id]);

                BookingProcessPaymentDetails::where('booking_process_id', $booking['booking_id'])
                    ->where('customer_id', $customer)
                    ->update(['booking_process_id' => $request->merge_booking_id]);

                /**Get booking details if empty then soft delete */
                $bookingCustomerDetais = BookingProcessCustomerDetails::where('booking_process_id', $booking['booking_id'])->get();

                $bookingPaymentDetais = BookingProcessPaymentDetails::where('booking_process_id', $booking['booking_id'])->get();

                /**If booking customer and payment details are empty then booking trash */
                if (!count($bookingCustomerDetais) || !count($bookingPaymentDetais)) {
                    $booking_processes = BookingProcesses::find($booking['booking_id']);
                    $booking_processes->trash_at = date("Y-m-d H:i:s");
                    $booking_processes->is_trash = 1;
                    $booking_processes->update();
                }
                /**End */
            }
        }

        /**Return success response */
        return $this->sendResponse(true, __('strings.completed_sucess', ['name' => 'Merge booking process']));
    }

    /**Attend booking
     * Date : 25-08-2020
     */
    public function attendBooking()
    {
        $current_date = date("Y-m-d");
        /**Customer qr required validation */
        if (!isset($_GET['customer_qr']))
            return $this->sendResponse(false, __('strings.required_validation', ['name' => 'customer qr']));

        /**Check customer exist or not */
        $customer_exist = BookingProcessCustomerDetails::where('QR_number', $_GET['customer_qr'])->first();
        if (!$customer_exist)
            return $this->sendResponse(false, __('strings.not_found_validation', ['name' => 'Customer']));

        /**Check customer booking cancel or not */
        $customer_cancel_booking = BookingProcessCustomerDetails::where('QR_number', $_GET['customer_qr'])
            ->where('is_cancelled', true)
            ->count();

        if ($customer_cancel_booking)
            return $this->sendResponse(false, __('strings.customer_booking_cancelled'));

        $check_print_qr = BookingProcessCustomerDetails::whereDate('scaned_at', '=', $current_date)
            ->where('QR_number', $_GET['customer_qr'])
            ->first();

        if ($check_print_qr) {
            return $this->sendResponse(false, __('strings.ticket_already_scaned', ['type' => 'Print']));
        }

        /**Get booking and customer base extended details */
        $booking_customer_details = BookingProcessCustomerDetails::where('booking_process_id', $customer_exist->booking_process_id)
            ->where('customer_id', $customer_exist->customer_id)
            ->get();

        /**Count total booking days with extended */
        $total_days = 0;
        foreach ($booking_customer_details as $customer_detail) {
            $start_date = new DateTime($customer_detail['start_date']);
            $end_date = new DateTime($customer_detail['end_date']);

            $diff = Carbon::parse($start_date)->diff($end_date);
            $total_days = $total_days + ($diff->d + 1);
            /**+ 1 for carbon return 0 for if both(from and to) values are same */
            $final_end_date = $customer_detail['end_date'];
        }

        $attended_days = BookingParticipantsAttendance::where('booking_process_id', $customer_exist->booking_process_id)
            ->where('customer_id', $customer_exist->customer_id)
            ->where('is_attend', true)
            ->count();

        /**Check customer booking days base his/ her attendance */
        if ($attended_days <= $total_days) {
            /**Check if booked days is passed away and still customer booked days are remain for attend then error response */

            if ($total_days == $attended_days) {
                return $this->sendResponse(false, __('strings.alreday_completed_course'));
            }

            if ($final_end_date < date('Y-m-d')) {
                return $this->sendResponse(false, __('strings.booking_is_expired', ['total_days' => $total_days, 'attended_days' => $attended_days, 'remain_days' => $total_days - $attended_days]));
            }
            /**Increment one in attended_days value */
            BookingProcessCustomerDetails::where('QR_number', $_GET['customer_qr'])->increment('attended_days');
            BookingProcessCustomerDetails::where('QR_number', $_GET['customer_qr'])->update(['scaned_at' => date('Y-m-d')]);
        } else {
            return $this->sendResponse(false, __('strings.alreday_completed_course'));
        }

        /**Return success response */
        return $this->sendResponse(true, __('strings.completed_sucess', ['name' => 'Booking attendance']));
    }

    /**Cancel booking
     * Date : 10-09-2020
     */
    public function cancelBooking(Request $request)
    {
        $v = validator($request->all(), [
            /**Rules */
            'booking_id' => 'required|exists:booking_processes,id,deleted_at,NULL',
            'customer_id' => 'required|exists:contacts,id,category_id,1,deleted_at,NULL',
            'payee_id' => 'required|exists:contacts,id,deleted_at,NULL',
            'cash_taken_out' => 'required',
            'cancellation_fee' => 'required',
            'money_back_amount' => 'required',
            'payback_method' => 'nullable|in:C,BT,V', //C: Cash, BT: Bank Transfer, V: Voucher
            'voucher_code' => 'nullable|exists:vouchers,code,deleted_at,NULL',
            'office_id' => 'nullable|exists:offices,id'
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $input_details = $request->only('booking_id', 'customer_id', 'payee_id', 'cash_taken_out', 'cancellation_fee', 'money_back_amount', 'payback_method', 'voucher_code', 'office_id');

        $checkPaymentBookingCancelled = BookingProcessPaymentDetails::where('booking_process_id', $input_details['booking_id'])
            ->where('customer_id', $input_details['customer_id'])
            ->where('payi_id', $input_details['payee_id'])
            ->where('is_cancelled', true)
            ->first();

        $checkCustomerBookingCancelled = BookingProcessCustomerDetails::where('booking_process_id', $input_details['booking_id'])
            ->where('customer_id', $input_details['customer_id'])
            ->where('payi_id', $input_details['payee_id'])
            ->where('is_cancelled', true)
            ->first();

        if ($checkPaymentBookingCancelled || $checkCustomerBookingCancelled) {
            return $this->sendResponse(false, __('strings.already_cancelled_booking'));
        }

        $course_id = BookingProcessCourseDetails::where('booking_process_id', $input_details['booking_id'])->value('course_id');
        $input_details['course_id'] = $course_id;

        $cancel_booking =  CancelledBooking::create($input_details);

        $pendingPaymentExist = BookingProcessPaymentDetails::where('booking_process_id', $input_details['booking_id'])
            ->where('customer_id', $input_details['customer_id'])
            ->where('payi_id', $input_details['payee_id'])
            ->where('status', 'Pending')
            ->count();

        BookingProcessPaymentDetails::where('booking_process_id', $input_details['booking_id'])
            ->where('customer_id', $input_details['customer_id'])
            ->where('payi_id', $input_details['payee_id'])
            ->update(['is_cancelled' => true, 'cancel_id' => $cancel_booking->id, 'status' => 'Cancelled']);

        BookingProcessCustomerDetails::where('booking_process_id', $input_details['booking_id'])
            ->where('customer_id', $input_details['customer_id'])
            ->where('payi_id', $input_details['payee_id'])
            ->update(['is_cancelled' => true, 'cancel_id' => $cancel_booking->id]);

        $check_payment = BookingProcessPaymentDetails::where('booking_process_id', $input_details['booking_id'])
            ->where('is_cancelled', false)->count();

        $check_customer = BookingProcessCustomerDetails::where('booking_process_id', $input_details['booking_id'])
            ->where('is_cancelled', false)->count();

        if (!$check_payment || !$check_customer) {
            $booking_process  = BookingProcesses::find($input_details['booking_id']);
            $booking_process->is_cancel = true;
            $booking_process->save();
        }

        if (!$pendingPaymentExist) {
            $this->generateCancellationInvoice($cancel_booking->id);
        }

        /**For if cash threw money back amount given then add in office CASHOUT */
        if (isset($input_details['payback_method'])) {
            if ($input_details['payback_method'] === 'C') {
                $invoice_number = BookingProcessPaymentDetails::where('booking_process_id', $input_details['booking_id'])
                    ->where('customer_id', $input_details['customer_id'])
                    ->where('payi_id', $input_details['payee_id'])
                    ->latest()
                    ->value('invoice_number');

                $booking_number = BookingProcesses::where('id', $input_details['booking_id'])
                    ->value('booking_number');

                $cash_entry_data = [
                    'date_of_entry' => date('Y-m-d'),
                    'type' => 'CASHOUT',
                    'office_id' => $input_details['office_id'],
                    'contact_id' => $input_details['payee_id'],
                    'description' => 'Cancelled booking payment, Booking #' . $booking_number . ' Invoice #' . $invoice_number,
                    'amount' => $input_details['money_back_amount']
                ];
                $cash_entry = $this->createCashEntry($cash_entry_data);
            }
        }

        $this->sendPayeeCancellationEmail($cancel_booking->id);

        /**Return success response */
        return $this->sendResponse(true, __('strings.cancelled_booking'));
    }

    /**Dowenload cancellation invoice url
     * Date : 12-09-2020
     */
    public function generateCancellationReceipt($id)
    {
        $data = $this->generateCancellationInvoice($id);

        return $data['pdf']->download($data['customer_name'] . '_cancellation_invoice.pdf');
    }

    /**Generate cancellation invoice */
    public function generateCancellationInvoice($id)
    {
        $cancelBookingDetails = CancelledBooking::find($id);

        $booking_processes = BookingProcesses::find($cancelBookingDetails->booking_id);

        $booking_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $cancelBookingDetails->booking_id)
            ->where('customer_id', $cancelBookingDetails->customer_id)
            ->where('payi_id', $cancelBookingDetails->payee_id)
            ->first();

        $course = DB::table('booking_process_course_details')->join('courses as c', 'c.id', '=', 'booking_process_course_details.course_id')
            ->where('booking_process_course_details.booking_process_id', $id)
            ->first();

        $pdf_data['booking_no'] = $booking_processes->booking_number;
        $pdf_data['invoice_number'] = $booking_payment_details->invoice_number;
        $pdf_data['invoice_date'] = $booking_payment_details->created_at;

        $contact_data = Contact::with('address.country_detail:id,name,code')->find($booking_payment_details['customer_id']);
        $pdf_data['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;

        $contact = Contact::with('address.country_detail:id,name,code')->find($booking_payment_details['payi_id']);

        $contact_address = ContactAddress::with('country_detail')->where('contact_id', $booking_payment_details['payi_id'])->first();

        ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . $contact_address->country_detail->name . "." : $address = "";

        $pdf_data['payi_name'] = $contact->salutation . "" . $contact->first_name . " " . $contact->last_name;
        $pdf_data['payi_address'] = $address;
        $pdf_data['payi_contact_no'] = $contact->mobile1;
        $pdf_data['payi_email'] = $contact->email;

        $pdf_data['cash_taken_out'] = $cancelBookingDetails->cash_taken_out;
        $pdf_data['cancellation_fee'] = $cancelBookingDetails->cancellation_fee;
        $pdf_data['money_back_amount'] = $cancelBookingDetails->money_back_amount;
        $pdf_data['payback_method'] = $cancelBookingDetails->payback_method;
        $pdf_data['voucher_code'] = $cancelBookingDetails->voucher_code;
        $pdf_data['payment_status'] = $booking_payment_details->status;
        $pdf_data['course_name'] = ($course ? $course->name : null);
        $pdf_data['payment_method_id'] = $booking_payment_details->payment_method_id;

        $pdf = PDF::loadView('bookingProcess.cancellation_booking_invoice', $pdf_data);

        $url = 'Invoice/' . $pdf_data['customer_name'] . '_cancellation_invoice' . mt_rand(1000000000, time()) . '.pdf';
        Storage::disk('s3')->put($url, $pdf->output());
        $url = Storage::disk('s3')->url($url);

        $cancelBookingDetails->cancellation_receipt = $url;
        $cancelBookingDetails->save();

        $data = [
            "customer_name" => $pdf_data['customer_name'],
            "pdf" => $pdf
        ];
        return $data;
    }

    public function sendPayeeCancellationEmail($id)
    {
        $cancelBookingDetails = CancelledBooking::find($id);

        $booking_processes = BookingProcesses::find($cancelBookingDetails->booking_id);

        $booking_payment_details = BookingProcessPaymentDetails::where('booking_process_id', $cancelBookingDetails->booking_id)
            ->where('customer_id', $cancelBookingDetails->customer_id)
            ->where('payi_id', $cancelBookingDetails->payee_id)
            ->first();

        $email_data['booking_no'] = $booking_processes->booking_number;
        $email_data['invoice_number'] = $booking_payment_details->invoice_number;
        $email_data['invoice_date'] = $booking_payment_details->created_at;

        $contact_data = Contact::with('address.country_detail:id,name,code')->find($booking_payment_details['customer_id']);
        $email_data['customer_name'] = $contact_data->salutation . "" . $contact_data->first_name . " " . $contact_data->last_name;

        $contact = Contact::with('address.country_detail:id,name,code')->find($booking_payment_details['payi_id']);

        $contact_address = ContactAddress::with('country_detail')->where('contact_id', $booking_payment_details['payi_id'])->first();

        ($contact_address) ? $address = $contact_address->street_address1 . ", " . $contact_address->city . ", " . $contact_address->country_detail->name . "." : $address = "";

        $email_data['payi_name'] = $contact->salutation . "" . $contact->first_name . " " . $contact->last_name;
        $email_data['payi_address'] = $address;
        $email_data['payi_contact_no'] = $contact->mobile1;
        $email_data['payi_email'] = $contact->email;

        $mail = new Mail();
        $email = $contact->email;
        $name = $email_data['payi_name'];
        $invoice_url = $cancelBookingDetails->cancellation_receipt ? $cancelBookingDetails->cancellation_receipt : null;

        /**Get default locale and set user language locale */
        $temp_locale = \App::getLocale();
        $user = User::where('email', $email)->first();
        if ($user) {
            \App::setLocale($user->language_locale);
        }
        /**End */

        $mail::send('email.payee_Fcancellation_invoice', $email_data, function ($message) use ($invoice_url, $email, $name) {
            $message->to($email)
                ->subject(__('email_subject.cancellation_booking_invoice', ['name' => $name, 'created_at' => date('h:i a')]));
            if ($invoice_url) {
                $message->attachData(file_get_contents($invoice_url), $name . "_cancellation_invoice.pdf");
            }
        });

        /**Set default language locale */
        \App::setLocale($temp_locale);

        return;
    }

    /**Send email when draft booking time customers payment is done */
    public function sendDraftPayedEmail($booking_id)
    {
        $payment_details = BookingProcessPaymentDetails::where('booking_process_id', $booking_id)
            ->where('status', 'Success')
            ->get();
        $course_details = BookingProcessCourseDetails::where('booking_process_id', $booking_id)
            ->first();
        $course_name = ($course_details ? ($course_details->course_data) ? $course_details->course_data->name : '' : '');

        $booking_number = ($course_details ? ($course_details->booking_process_detail) ? $course_details->booking_process_detail->booking_number : '' : '');

        foreach ($payment_details as $payment) {
            $customer_details = BookingProcessCustomerDetails::where('booking_process_id', $booking_id)
                ->where('customer_id', $payment['customer_id'])
                ->where('payi_id', $payment['payi_id'])
                ->get();

            foreach ($customer_details as $customer) {
                $payee = Contact::find($customer['payi_id']);
                dd($payee->language_locale);
                $email_data['name'] = $payee['first_name'];
                $email_data['booking_number'] = $booking_number;
                $email_data['start_date'] = $customer->start_date;
                $email_data['end_date'] = $customer->end_date;
                $email_data['course_name'] = $course_name;
                // $payee->notify(new DraftPayedNotification($email_data, $booking_number, $course_name));
                $payee->notify((new DraftPayedNotification($email_data, $booking_number, $course_name))->locale($payee->language_locale));
            }
        }
        return 1;
    }

    public function updateOtherCourseDetails($request)
    {
        $input['created_by'] = auth()->user()->id;
        $updateContact = BookingProcessCustomerDetails::where('booking_process_id', $request->booking_process_id)->where('customer_id', $request->contact_id)->first();

        $course_data = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();

        $addContactDetail['StartDate_Time'] = $request->StartDate_Time;
        $addContactDetail['booking_process_id'] = $request->booking_process_id;
        $addContactDetail['customer_id'] = $request->contact_id;
        $addContactDetail['is_payi'] = $updateContact->is_payi;
        $addContactDetail['payi_id'] = $updateContact->payi_id;
        $addContactDetail['EndDate_Time'] = $request->EndDate_Time;
        $addContactDetail['hours_per_day'] = $request->hours_per_day;
        $addContactDetail['accommodation'] = $request->accommodation;
        $addContactDetail['cal_payment_type'] = $request->cal_payment_type;
        $addContactDetail['is_new_invoice'] = 0;
        $addContactDetail['created_by'] = $input['created_by'];
        $addContactDetail['created_at'] = date("Y-m-d H:i:s");
        // $addContactDetail['is_updated'] = 1;
        $addContactDetail['QR_number'] = ($updateContact->QR_number) ? $updateContact->QR_number : $request->booking_process_id . $request->contact_id . mt_rand(100000, 999999);

        $addContactDetail['is_include_lunch'] = $request->is_include_lunch;
        $tempStrt1 = explode(" ", $addContactDetail['StartDate_Time']);
        $tempEnd1 = explode(" ", $request->EndDate_Time);
        $addContactDetail['start_date'] = $tempStrt1[0];
        $addContactDetail['start_time'] = $tempStrt1[1];
        $addContactDetail['end_date'] = $tempEnd1[0];
        $addContactDetail['end_time'] = $tempEnd1[1];
        $addContactDetail['no_of_days'] = $request->no_of_days;

        $updateContact = $updateContact->update($addContactDetail);

        $updateCourseDetail['StartDate_Time'] = $request->StartDate_Time;
        $tempStrt = explode(" ", $request->StartDate_Time);
        $updateCourseDetail['start_date'] = $tempStrt[0];
        $updateCourseDetail['start_time'] = $tempStrt[1];
        $updateCourseDetail['EndDate_Time'] = $request->EndDate_Time;
        $tempStrt = explode(" ", $request->EndDate_Time);
        $updateCourseDetail['end_date'] = $tempStrt[0];
        $updateCourseDetail['end_time'] = $tempStrt[1];

        $courseDetails = BookingProcessCourseDetails::where('booking_process_id', $request->booking_process_id)->first();
        $courseDetails->update($updateCourseDetail);

        if ($request->booking_process_id) {
            $emails_sent = $this->customer_update_booking($request->booking_process_id);
        }

        return 1;
    }

    /**For invoice history */
    public function invoiceHistoryList(Request $request)
    {
        $v = validator($request->all(), [
            /**Rules */
            'page' => 'nullable|integer|min:1',
            'perPage' => 'nullable|integer|min:1',
            'invoice_id' => 'nullable|exists:booking_process_payment_details,id,deleted_at,NULL',
        ]);

        /**Check above validation and if any mismatch then return error response */
        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $invoice_history = InvoicePaymentHistory::query();

        if ($request->invoice_id) {
            $invoice_history = $invoice_history->where('invoice_id', $request->invoice_id);
        }

        $invoice_history_count = $invoice_history->count();
        if ($request->page && $request->perPage) {
            $page = $request->page;
            $perPage = $request->perPage;
            $invoice_history = $invoice_history->skip($perPage * ($page - 1))->take($perPage);
        }
        $invoice_history = $invoice_history->with('payment_detail', 'booking_detail')->get();

        $data = [
            'invoice_history' => $invoice_history,
            'count' => $invoice_history_count
        ];

        return $this->sendResponse(true, __('strings.list_message', ['name' => 'Invoice history']), $data);
    }

    public function checkInvoiceAssignedConsolidated($id)
    {
        $ids = array();
        $assigned = false;
        $invoice_ids = BookingProcessPaymentDetails::where('booking_process_id', $id)->pluck('id')->toArray();
        $consolidate_ids = ConsolidatedInvoice::pluck('invoices');
        foreach ($consolidate_ids as $consolidate) {
            foreach ($consolidate as $id) {
                $ids[] = $id;
            }
        }
        $ids = array_unique($ids);
        $ids = array_intersect($invoice_ids, $ids);
        if (count($ids)) {
            $assigned = true;
        }
        return $assigned;
    }

    /*Get booking participants and instrucotrs details  */
    public function getBookingDetails(Request $request)
    {
        $v = validator($request->all(), [
            'booking_process_id' => 'required|exists:booking_processes,id'
        ]);

        if ($v->fails()) {
            return $this->sendResponse(false, $v->errors()->first());
        }

        $booking_processes_id = $request->booking_process_id;

        $booking_processes = BookingProcesses::where('is_trash', false)
            ->with(['customer_detail.customer', 'customer_detail.sub_child_detail.allergies.allergy', 'customer_detail.sub_child_detail.languages.language'])
            ->with(['instructor_detail.contact'])
            // ->with(['request_instructor_detail.contact'])
            ->find($booking_processes_id);

        return $this->sendResponse(true, 'success', $booking_processes);
    }
}
