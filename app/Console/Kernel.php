<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\BookingAlert',
        'App\Console\Commands\BergbahnApi',
        'App\Console\Commands\OpenfireRegister',
        'App\Console\Commands\BookingAlertInstructorTwoHourAgo',
        'App\Console\Commands\InvoiceAfterCourseEndPaidBefore',
        'App\Console\Commands\InvoiceAfterCourseEnds',
        'App\Console\Commands\PastCourseReview',
        'App\Console\Commands\SendBookingAlertInstructorFivePm',
        'App\Console\Commands\MasterSeasonYearChange',
        'App\Console\Commands\BookingStartNotificationAlert',
        'App\Console\Commands\BookingEndNotificationAlert',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('booking_alert:alertcustomers')
                   ->hourly();
        $schedule->command('Bergbahn:Api')
                   ->everyFifteenMinutes();
        $schedule->command('booking_alert_two_hour_ago:instructor')
                   ->everyFifteenMinutes();
        $schedule->command('course_invoice_after_end_paid_before:customers')
                   ->daily();
        $schedule->command('course_invoice_after_end:customers')
                   ->daily();
        $schedule->command('past_course_review:customers')
                   ->daily();
        /**This cron is not use current requirement because review email is send only customer */
        /* $schedule->command('review_course:customers')
                   ->everyFiveMinutes(); */
        $schedule->command('send_booking_alert_five_pm:instructor')
                   ->everyFifteenMinutes();
        $schedule->command('openfire:register')
                   ->everyMinute();
        $schedule->command('master_season_year_change:season')
                   ->yearly();
        $schedule->command('booking_start_alert:alertinstructors')
                    ->everyMinute();
        $schedule->command('booking_end_alert:alertinstructors')
                    ->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
