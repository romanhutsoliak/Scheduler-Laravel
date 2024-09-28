<?php

namespace App\Console\Commands;

use App\Jobs\TaskNotificationJob;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class TaskNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'taskNotifications {dateTime?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Task Notifications';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(): void
    {
        $dateTime = $this->argument('dateTime') ?? null;

        if ($dateTime) {
            $dateTime = Carbon::parse($dateTime)->format('Y-m-d H:i:00');
        }

        TaskNotificationJob::dispatch($dateTime);
    }
}
