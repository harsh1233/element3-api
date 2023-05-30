<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class BookingEstimateExport implements FromView
{
    public $booking_estimate_data;
    
    public function __construct($booking_processes) {
        $this->booking_estimate_data = $booking_processes;        
    }
    
    public function view(): View
    {
        return view('csv.booking_estimate', [
            'estiamte_data' => $this->booking_estimate_data
        ]);
    }
}
