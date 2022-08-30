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
     * @return int
     */
    public function handle()
    {
        $tasks = Task::where('nextRunDateTime', '<=', date('Y-m-d H:i:00'))->get();
        foreach ($tasks as $task) {
            foreach ($task->userDevices as $userDevice) {
                $postData = [
                    'to' => $userDevice->notificationToken,
                    'sound' => 'default',
                    'title' => $task->name,
                    'body' => $task->description,
                    'data' => [
                        'redirectTo' => '/tasks/' . $task->id,
                    ],
                ];

                $this->sendPost('https://exp.host/--/api/v2/push/send', $postData, [
                    'Accept: application/json',
                    'Accept-encoding: gzip, deflate',
                    'Content-Type: application/json',
                ]);
            }
            $task->calculateNextRunDateTime(true);
            $task->save();
        }
    }
}
