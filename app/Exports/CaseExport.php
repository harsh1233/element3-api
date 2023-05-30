<?php

namespace App\Exports;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class CaseExport implements FromView
{
    public $cash_entries_data;
    
    public function __construct($cash_entries) {
        $this->cash_entries_data = $cash_entries;   
    }
    
    public function view(): View
    {
        return view('csv.cash', [
            'cash_entries_data' => $this->cash_entries_data
        ]);
    }
}
