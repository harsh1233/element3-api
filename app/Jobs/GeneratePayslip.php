<?php

namespace App\Jobs;

use DateTime;
use Carbon\Carbon;
use App\Models\Contact;
use App\Models\Payroll;
use App\Models\Payslip;
use Illuminate\Bus\Queueable;
use App\Models\InstructorBlock;
use App\Models\InstructorBlockMap;
use Illuminate\Support\Facades\DB;
use App\Models\Finance\Expenditure;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\BookingProcess\BookingInstructorDetailMap;
use App\Models\BookingProcess\BookingProcessCourseDetails;

class GeneratePayslip implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $payroll_id;
    public $contact_id;
    public $is_new;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payroll_id, $contact_id, $is_new = false)
    {
        $this->payroll_id = $payroll_id;
        $this->contact_id = $contact_id;
        $this->is_new = $is_new;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('JOB GeneratePayslip started');
        \Log::info('data', ['payroll_id' => $this->payroll_id, 'contact_id' => $this->contact_id]);
        try {
            $payroll = Payroll::find($this->payroll_id);
            if (!$payroll) {
                throw new \Exception("Payroll with id `$this->payroll_id` not found.");
            }
            $contact = Contact::find($this->contact_id);

            if (!$payroll) {
                throw new \Exception("Contact with id `$this->contact_id` not found.");
            }

            // find the payslip if exists
            $payslip = Payslip::where('payroll_id', $this->payroll_id)
                ->where('contact_id', $this->contact_id)->first();

            if (!$payslip) {
                // if payslip does not exist, start making a payslip
                $payslip = new Payslip();
                $payslip->payroll_id = $this->payroll_id;
                $payslip->contact_id = $this->contact_id;
            }

            $payslip_data = array();
            $payslip_data['contact_id'] = $contact->id;

            // salary
            $salary_group = $contact->bank_detail->salary_group_detail;

            if ($salary_group) {
                $payslip_data['salary_name'] = $salary_group->name;
                $payslip_data['salary_type'] = $salary_group->salary_type;
                $payslip_data['salary_amount'] = $salary_group->amount;
                $payslip_data['salary_sum_per_extra_hour'] = $salary_group->sum_per_extra_hour;
                $payslip_data['salary_paid_sick_leave'] = $salary_group->paid_sick_leave;
                $payslip_data['salary_paid_vacation_leave'] = $salary_group->paid_vacation_leave;
            } else {
                // delete the payroll if already exsists
                $payslip->delete();

                \Log::info('Not found Salary group for this Contact', ['payroll_id' => $this->payroll_id, 'contact_id' => $this->contact_id, 'first name' => $contact->first_name, 'last name' => $contact->last_name]);
                return;
            }

            // time variables
            $time = strtotime("$payroll->year-$payroll->month-01");
            $first_day = date("Y-m-d", $time);
            $last_day = date("Y-m-t", $time);
            $first_day_time = date("Y-m-d 00:00:00", $time);
            $last_day_time = date("Y-m-t 23:59:59", $time);

            if ($contact->isType('Instructor')) {
                $paid_blocks = InstructorBlock::where('instructor_id', $contact->id)
                    ->where('is_paid', true)
                    ->where('start_date', '>=', $first_day)
                    ->where('end_date', '<=', $last_day)
                    ->get();
            }

            if ($contact->isType('Instructor') && ($payslip_data['salary_type'] == 'FD' || $payslip_data['salary_type'] == 'H')) {
                /**Get instructor actual days and hours */
                $actual_days = BookingInstructorDetailMap::where('contact_id', $contact->id)
                ->where('startdate_time', '>=', $first_day_time)
                ->where('enddate_time', '<=', $last_day_time)
                ->count();

                $actual_hours = BookingInstructorDetailMap::where('contact_id', $contact->id)
                ->where('startdate_time', '>=', $first_day_time)
                ->where('enddate_time', '<=', $last_day_time)
                ->get()
                ->sum('payroll_actual_hour');

                $booking_ids = BookingInstructorDetailMap::where('contact_id', $contact->id)
                ->where('startdate_time', '>=', $first_day_time)
                ->where('enddate_time', '<=', $last_day_time)
                ->groupBy('booking_process_id')
                ->pluck('booking_process_id');

                $total_lunch_hour = BookingProcessCourseDetails::whereIn('booking_process_id', $booking_ids)
                ->sum('lunch_hour');
                
                $actual_hours = $actual_hours - $total_lunch_hour;
                /**End */
            } else {
                $actual_days = $payroll->working_days;
            }

            // leaves
            $leaves = $contact->leave_data
                ->where('start_date', '>=', $first_day)
                ->where('start_date', '<=', $last_day);

            $payslip_data['total_leaves'] = $leaves->sum('no_of_days');
            $payslip_data['present_days'] = $actual_days - $payslip_data['total_leaves'];
            $payslip_data['course_total_days'] = $actual_days;
            $payslip_data['leaves_pending'] = $leaves->where('leave_status', 'P')->sum('no_of_days');
            $payslip_data['leaves_approved'] = $leaves->where('leave_status', 'A')->sum('no_of_days');
            $payslip_data['leaves_rejected'] = $leaves->where('leave_status', 'R')->sum('no_of_days');
            $payslip_data['leaves_paid'] = $leaves->where('is_paid', 'Y')->sum('no_of_days');
            $payslip_data['leaves_unpaid'] = $leaves->where('is_paid', 'N')->sum('no_of_days');
            $payslip_data['days_paid'] = $payslip_data['present_days'] + $payslip_data['leaves_paid'];

            /**Get instructor paid block amounts */
            $paid_blocks = [];
            $paid_blocks_amount = 0;
            $paid_block_hours = 0;
            /**End */
            //  activity timesheet and actual salary calculation
            if ($payslip_data['salary_type'] === 'H' && $contact->isType('Instructor') && $contact->user_detail) {
                $activites = $contact->user_detail->timesheets
                    ->where('activity_date', '>=', $first_day)
                    ->where('activity_date', '<=', $last_day);

                $payslip_data['total_hours'] = $payslip_data['hours_pending'] = $payslip_data['hours_inprogress'] = $payslip_data['hours_approved'] = $payslip_data['hours_approved_overtime'] = $payslip_data['hours_rejected'] = 0;
                // if instructor has activites
                if ($activites->count()) {
                    foreach ($activites as $activity) {
                        $payslip_data['total_hours'] += strtotime($activity->total_activity_hours) - strtotime("00:00:00");
                        if ($activity->status === 'P') {
                            $payslip_data['hours_pending'] += strtotime($activity->total_activity_hours) - strtotime("00:00:00");
                        }
                        if ($activity->status === 'IP') {
                            $payslip_data['hours_inprogress'] += strtotime($activity->total_activity_hours) - strtotime("00:00:00");
                        }
                        if ($activity->status === 'R') {
                            $payslip_data['hours_rejected'] += strtotime($activity->total_activity_hours) - strtotime("00:00:00");
                        }
                        if ($activity->status === 'A') {
                            $activity_hours = strtotime($activity->total_activity_hours) - strtotime("00:00:00");
                            $activities_actual_hours = strtotime($activity->actual_hours) - strtotime("00:00:00");
                            if ($activity_hours > $activities_actual_hours) {
                                //overtime
                                $payslip_data['hours_approved'] += $activities_actual_hours;
                                $payslip_data['hours_approved_overtime'] += $activity_hours - $activities_actual_hours;
                            } else {
                                // normal
                                $payslip_data['hours_approved'] += $activity_hours;
                            }
                        }
                    }
                    $payslip_data['total_hours'] = ceil($payslip_data['total_hours'] / 60 / 60);
                    $payslip_data['hours_pending'] = ceil($payslip_data['hours_pending'] / 60 / 60);
                    $payslip_data['hours_inprogress'] = ceil($payslip_data['hours_inprogress'] / 60 / 60);
                    $payslip_data['hours_approved'] = ceil($payslip_data['hours_approved'] / 60 / 60);
                    $payslip_data['hours_approved_overtime'] = ceil($payslip_data['hours_approved_overtime'] / 60 / 60);
                    $payslip_data['hours_rejected'] = ceil($payslip_data['hours_rejected'] / 60 / 60);
                }

                if ($contact->isType('Instructor')) {
                    $payslip_data['normal_payout'] = $actual_hours * $payslip_data['salary_amount'];
                    $payslip_data['course_total_hours'] = $actual_hours;
                } else {
                    $payslip_data['normal_payout'] = $payslip_data['hours_approved'] * $payslip_data['salary_amount'];
                    $payslip_data['course_total_hours'] = $payslip_data['hours_approved'];
                }
                $payslip_data['overtime_payout'] = $payslip_data['hours_approved_overtime'] * $payslip_data['salary_sum_per_extra_hour'];

                if ($paid_blocks) {
                    foreach ($paid_blocks as $block) {
                        $start_time = new DateTime($block['start_time']);
                        $end_time = new DateTime($block['end_time']);
                        $interval = Carbon::parse($start_time)->diff($end_time);

                        if (!$block['amount']) {
                            /**Calculate instructor un released days count */
                            $paid_blocks_count = InstructorBlockMap::where('instructor_id', $block['instructor_id'])
                                ->where('is_release', false) //un released days(paid with days are not added in any booking)
                                ->whereBetween('start_date', [$first_day, $last_day])
                                ->count();
                            /** */

                            $paid_blocks_amount = $paid_blocks_count * ($paid_blocks_amount + ($interval->h * $payslip_data['salary_amount']));
                        } else {
                            $paid_blocks_amount = $paid_blocks_amount + $block['amount'];
                        }
                        $paid_block_hours = $paid_blocks_count * ($paid_block_hours + $interval->h);
                    }
                }
                // total salary
                $payslip_data['total_payout'] = $payslip_data['normal_payout'] + $payslip_data['overtime_payout'] + $paid_blocks_amount;
            } elseif ($payslip_data['salary_type'] === 'FD') {

                if ($paid_blocks) {
                    foreach ($paid_blocks as $block) {
                        $start_time = new DateTime($block['start_time']);
                        $end_time = new DateTime($block['end_time']);
                        $interval = Carbon::parse($start_time)->diff($end_time);

                        if (!$block['amount']) {
                            /**Calculate instructor un released days count */
                            $paid_blocks_count = InstructorBlockMap::where('instructor_id', $block['instructor_id'])
                                ->where('is_release', false) //un released days(paid with days are not added in any booking)
                                ->whereBetween('start_date', [$first_day, $last_day])
                                ->count();
                            /** */
                            $paid_blocks_amount = $paid_blocks_count * ($paid_blocks_amount + ($interval->h * ($payslip_data['salary_amount'] / 8)));
                        } else {
                            $paid_blocks_amount = $paid_blocks_amount + $block['amount'];
                        }
                        $paid_block_hours = $paid_blocks_count * ($paid_block_hours + $interval->h);
                    }
                }
                // total salary
                $payslip_data['total_payout'] = ($payslip_data['salary_amount'] * $payslip_data['days_paid']) + $paid_blocks_amount;
            } elseif ($payslip_data['salary_type'] === 'FM') {
                // perday salary
                $per_day_salary = $payslip_data['salary_amount'] / $payroll->working_days;

                if ($paid_blocks) {
                    foreach ($paid_blocks as $block) {
                        $total_days = date('t', strtotime($time));
                        $start_time = new DateTime($block['start_time']);
                        $end_time = new DateTime($block['end_time']);
                        $interval = Carbon::parse($start_time)->diff($end_time);

                        if (!$block['amount']) {
                            /**Calculate instructor un released days count */
                            $paid_blocks_count = InstructorBlockMap::where('instructor_id', $block['instructor_id'])
                                ->where('is_release', false) //un released days(paid with days are not added in any booking)
                                ->whereBetween('start_date', [$first_day, $last_day])
                                ->count();
                            /** */

                            $paid_blocks_amount = $paid_blocks_count * ($paid_blocks_amount + ($interval->h * (($payslip_data['salary_amount'] / $total_days) / 8)));
                        } else {
                            $paid_blocks_amount = $paid_blocks_amount + $block['amount'];
                        }
                        $paid_block_hours = $paid_blocks_count * ($paid_block_hours + $interval->h);
                    }
                }

                // total salary
                $payslip_data['total_payout'] = ($per_day_salary * $payslip_data['days_paid']) + $paid_blocks_amount;
            } else {
                // Case : which have hourly salary group but are not instructors
                $payslip_data['total_payout'] = 0;
            }
            $payslip_data['paid_block_amount'] = $paid_blocks_amount;
            $payslip_data['approved_paid_block_hours'] = $paid_block_hours;

            // expenditure debts
            if ($contact->user_detail) {
                // if contact's user details are available find expenditures
                $expenditures = $contact->user_detail->expenditures->where('payment_status', 'PP')
                    ->where('date_of_expense', '>=', $first_day)
                    ->where('date_of_expense', '<=', $last_day);

                if ($expenditures->count()) {
                    $payslip_data['expenditure_debt'] = $expenditures->sum('amount');
                    $payslip_data['total_payout'] += $payslip_data['expenditure_debt'];
                }
            }

            // settlement if any
            $payslip_data['total_payout'] += $payslip['settlement_amount'];

            $payroll->amount += $payslip_data['total_payout'];
            if ($this->is_new) {
                $payroll->total_contacts_processed++;
            }

            // update payroll amount
            $payroll->save();

            // create payslip
            $payslip->fill($payslip_data);
            $payslip->save();
        } catch (\Exception $ex) {
            \Log::info('error genertaing not found while generating payslip');
            \Log::info('payslip data', ['payroll_id' => $this->payroll_id, 'contact_id' => $this->contact_id]);
            \Log::error($ex);
            $this->fail($ex);
        }
        \Log::info('JOB GeneratePayslip ended');
        \Log::info('data', ['payroll_id' => $this->payroll_id, 'contact_id' => $this->contact_id]);
    }
}
