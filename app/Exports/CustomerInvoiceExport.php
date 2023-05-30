<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CustomerInvoiceExport implements FromView
{
    public $customer_invoice_data;
    
    public function __construct($booking_payment_data) {
        $this->customer_invoice_data = $booking_payment_data;   
    }
    
    public function view(): View
    {
    
        return view('csv.customer_invoice', [
            'invoice_data' => $this->customer_invoice_data
        ]);
    }
}
