<?php
namespace App\Exports;

use App\Models\Contact;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class BookingProcessExport implements FromView
{
    public $booking_processes_data;
    
    public function __construct($booking_processes) {
        $this->booking_processes_data = $booking_processes;        
    }
    
    public function view(): View
    {
        return view('csv.booking_process', [
            'booking_data' => $this->booking_processes_data
        ]);
    }
}