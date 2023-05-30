<?php
namespace App\Exports;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PayrollListExport implements FromView
{
    public $payroll_data;
    
    public function __construct($payroll)
    {
        $this->payroll_data = $payroll;
    }
    
    public function view(): View
    {
        return view('csv.payrolllist', [
            'payroll_data' => $this->payroll_data
        ]);
    }
}
