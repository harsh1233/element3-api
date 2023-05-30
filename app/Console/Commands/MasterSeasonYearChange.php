<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SeasonDaytimeMaster;
use Illuminate\Support\Facades\Log;

class MasterSeasonYearChange extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'master_season_year_change:season';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command is use for season master table change season year at a yearly';

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
       Log::info("master_season_year_change:season this cron is start the execute.");

       try{
            $high_season = SeasonDaytimeMaster::where('name', 'High Season')->first();
            $low_season = SeasonDaytimeMaster::where('name', 'Low Season')->first();
            $high_season_start = date('Y-m-d', strtotime('+1 years', strtotime($high_season->start_date)));
            $high_season_end = date('Y-m-d', strtotime('+1 years', strtotime($high_season->end_date)));
            $low_season_start = date('Y-m-d', strtotime('+1 years', strtotime($low_season->start_date)));
            $low_season_end = date('Y-m-d', strtotime('+1 years', strtotime($low_season->end_date)));

            /**Update the startdate year and enddate year for seasons */
            $high_season->update(['start_date'=>$high_season_start,'end_date'=>$high_season_end]);
            $low_season->update(['start_date'=>$low_season_start,'end_date'=>$low_season_end]);
            /**End */
       } catch (\Exception $e)
       {
             Log::error("An error occurs in master_season_year_change:season command execute.");
       }

       Log::info("master_season_year_change:season this cron is successfully executed.");
    }
}
