<?php
namespace App\Exports;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PayrollExport implements FromView
{
    public $payroll_data;
    
    public function __construct($payroll)
    {
        $this->payroll_data = $payroll;
    }
    
    public function view(): View
    {
        return view('csv.payroll', [
            'payroll_data' => $this->payroll_data
        ]);
    }
}
