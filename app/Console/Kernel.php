<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Jobs\MessagingScheduledJob;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('schedule:send-message')->everyMinute();
        $schedule->command('chatbot:follow-up')->hourly()->withoutOverlapping();
        $schedule->command('chatbot:retarget-warm')->dailyAt('10:00')->withoutOverlapping();

        // Protección de sesiones WhatsApp (todos los devices)
        $schedule->command('bot:backup-creds')->everyFourHours()->withoutOverlapping();
        $schedule->command('bot:heartbeat')->everyTwoHours()->withoutOverlapping();
        $schedule->command('bot:health-check')->everyFiveMinutes()->withoutOverlapping();

        // CRM bidireccional: lee status de ERPNext y actualiza local
        $schedule->command('crm:sync-status')->everyThirtyMinutes()->withoutOverlapping();
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
