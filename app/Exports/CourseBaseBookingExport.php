<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CourseBaseBookingExport implements FromView
{
    public $data;
    public $payment_card_base_details;
    public $payment_method_base_details;
    public $credit_card_type_details;

    public function __construct($data, $payment_card_base_details, $payment_method_base_details, $credit_card_type_details) {
        $this->data = $data;
        $this->payment_card_base_details = $payment_card_base_details;
        $this->payment_method_base_details = $payment_method_base_details;
        $this->credit_card_type_details = $credit_card_type_details;
    }
    
    public function view(): View
    {
        return view('csv.course_base_booking', [
            'data' => $this->data,
            'payment_card_base_details' => $this->payment_card_base_details,
            'payment_method_base_details' => $this->payment_method_base_details,
            'credit_card_type_details' => $this->credit_card_type_details
        ]);
    }
}
