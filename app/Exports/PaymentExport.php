<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PaymentExport implements FromView
{
    public $payments_data;
    public $payment_card_base_details;
    public $payment_method_base_details;

    public function __construct($payments, $payment_card_base_details, $payment_method_base_details) {
        $this->payments_data = $payments;
        $this->payment_card_base_details = $payment_card_base_details;
        $this->payment_method_base_details = $payment_method_base_details;   
    }
    
    public function view(): View
    {
        return view('csv.payments', [
            'payments_data' => $this->payments_data,
            'payment_card_base_details' => $this->payment_card_base_details,
            'payment_method_base_details' => $this->payment_method_base_details
        ]);
    }
}
