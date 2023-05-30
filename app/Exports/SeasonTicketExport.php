<?php
namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SeasonTicketExport implements FromView
{
    public $season_tickets_data;
    
    public function __construct($season_tickets) {
        $this->season_tickets_data = $season_tickets;
    }
    
    public function view(): View
    {
        return view('csv.season_tickets', [
            'season_tickets_data' => $this->season_tickets_data
        ]);
    }
}