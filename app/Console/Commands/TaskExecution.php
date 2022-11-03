<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;

class TaskExecution extends Command
{
    use \App\Traits\ApiRequest;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'TaskExecution';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Task Execution';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tasks = Task::where('isActive', 1)
            ->where(function ($query) {
                $query->where('nextRunDateTimeUtc', date('Y-m-d H:i:00'))
                ->orWhere(function ($query) {
                    $query->where('mustBeCompleted', 0)->where('nextRunDateTimeUtc', '<', date('Y-m-d H:i:00'));
                });
            })
            ->get();

        echo $tasks->count() . "\n";
        foreach ($tasks as $task) {
            foreach ($task->userDevices as $userDevice) {
                if (!$userDevice->notificationToken)
                    continue;

                $this->sendPost('https://exp.host/--/api/v2/push/send', [
                    'to' => $userDevice->notificationToken,
                    'sound' => 'default',
                    'title' => $task->name,
                    'body' => $task->description,
                    'data' => [
                        'redirectTo' => '/tasks/' . $task->id,
                    ],
                ], [
                    'Accept: application/json',
                    'Accept-encoding: gzip, deflate',
                    'Content-Type: application/json',
                ]);
            }
            if (!$task->mustBeCompleted) {
                $task->calculateNextRunDateTime(true);
                $task->save();
            }
        }
    }
}
