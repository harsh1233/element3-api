<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class VoucherExport implements FromView
{
    public $vouchers_data;
    
    public function __construct($vouchers) {
        $this->vouchers_data = $vouchers;   
    }
    
    public function view(): View
    {
        return view('csv.voucher', [
            'vouchers_data' => $this->vouchers_data
        ]);
    }
}
