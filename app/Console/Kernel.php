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
        //
    ];

    /**
     * Define the application's command schedule.
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('import:events --isolated')
            ->everySixHours()
            ->appendOutputTo(storage_path('logs/importEvents.log'));
        $schedule->command('orderslist:fetch-all --isolated')->everyMinute()->withoutOverlapping();
        $schedule->command('weather:fetchAll --isolated')->everyFifteenMinutes();
        $schedule->command('nina:fetchAll --isolated')->everyFiveMinutes();

        $schedule->command('telescope:prune')->daily()->environments(['local']);
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
