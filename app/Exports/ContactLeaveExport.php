<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ContactLeaveExport implements FromView
{
    public $leave_data;
    
    public function __construct($contact_leave) {
        $this->leave_data = $contact_leave;   
    }
    
    public function view(): View
    {
        return view('csv.contact_leave', [
            'leave_data' => $this->leave_data
        ]);
    }
}
