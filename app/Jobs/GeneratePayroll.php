<?php

namespace App\Jobs;

use App\Models\Contact;
use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use App\Http\Controllers\Functions;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class GeneratePayroll implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Functions;

    public $payroll_data;
    public $created_by;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($payroll_data,$created_by)
    {
        $this->payroll_data = $payroll_data;
        $this->created_by = $created_by;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('JOB GeneratePayroll started');
        \Log::info('data', $this->payroll_data);
        try {
            $this->payroll_data['total_contacts'] = $this->payroll_data['total_contacts_processed'] = $this->payroll_data['amount'] = 0;

            // time variables
            $year = $this->payroll_data['year'];
            $month = $this->payroll_data['month'];

            // get active contacts (instructors, employees)
            $contacts = Contact::whereIn('category_id', ['2','3'])->where('is_active', '1')->get();
            $this->payroll_data['total_contacts'] = $contacts->count();


            $payroll = Payroll::where('month',$month)->where('year',$year)->first();

            $is_update = false;
            $created_by = $this->created_by;
            if($payroll) {
                $is_update = true;
                /**Add crm user action trail */
                    $action_id = $payroll->id; //payroll id
                    $action_type = 'U'; //U = Updated
                    $module_id = 25; //module id base module table
                    $module_name = "Payroll"; //module name base module table
                    $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name, $created_by);
                /**End manage trail */
            }

            // generate new payroll
            $payroll = Payroll::updateOrCreate([
                'month'=> $month,
                'year'=> $year
            ], $this->payroll_data);

            if ($payroll && !$is_update) {
                /**Add crm user action trail */
                    $action_id = $payroll->id; //payroll id
                    $action_type = 'A'; //A = Add
                    $module_id = 25; //module id base module table
                    $module_name = "Payroll"; //module name base module table
                    $trail = $this->addCrmUserActionTrail($action_id, $action_type, $module_id, $module_name, $created_by);
                /**End manage trail */
            }


            foreach ($contacts as $contact) {
                try {
                    // for each contact found generate a payslip
                    GeneratePayslip::dispatch($payroll->id, $contact->id, true);
                } catch (\Exception $ex) {
                    throw $ex;
                }
            }
        } catch (\Exception $ex) {
            \Log::info('error genertaing payroll');
            \Log::info('payroll data', $this->payroll_data);
            \Log::error($ex);
            $this->fail($ex);
        }
        \Log::info('JOB GeneratePayroll ended');
        \Log::info('data', $this->payroll_data);
    }
}
