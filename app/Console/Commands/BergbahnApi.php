<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BergbahnApi extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Bergbahn:Api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'API calling in every 15 minutes and getting xml response into cache';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        // Log::info("Calling Bergbahn:Api");
        $property = file_get_contents('http://sgm.kitzski.at/api/public/property?Region=kitzski');
        $lift = file_get_contents('http://sgm.kitzski.at/api/public/facility?Region=kitzski&Type=lift');
        $liftWithOmitClose = file_get_contents('http://sgm.kitzski.at/api/public/facility?Region=kitzski&Type=lift&OmitClosed=1');
        $slope = file_get_contents('http://sgm.kitzski.at/api/public/facility?Region=kitzski&Type=piste');
        $slopeWithOmitClose = file_get_contents('http://sgm.kitzski.at/api/public/facility?Region=kitzski&Type=piste&OmitClosed=1');
        $expire_at = now()->addMinutes(15);

        Cache::put('property', $property, $expire_at);
        Cache::put('lift', $lift, $expire_at);
        Cache::put('liftWithOmitClose', $liftWithOmitClose, $expire_at);
        Cache::put('slope', $slope, $expire_at);
        Cache::put('slopeWithOmitClose', $slopeWithOmitClose, $expire_at);

    }
}
