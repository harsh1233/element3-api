<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ConsolidatedInvoiceExport implements FromView
{
    public $consolidated_invoices_data;
    
    public function __construct($consolidated_invoices) {
        $this->consolidated_invoices_data = $consolidated_invoices;
    }
    
    public function view(): View
    {
        return view('csv.consolidated_invoices', [
            'consolidated_invoices_data' => $this->consolidated_invoices_data
        ]);
    }
}