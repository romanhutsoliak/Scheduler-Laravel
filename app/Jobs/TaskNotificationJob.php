<?php

namespace App\Jobs;

use App\Models\Task;
use App\Services\FirebaseNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class TaskNotificationJob // implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public ?string $runDateTime = null)
    {
    }

    public function handle(): void
    {
        if (empty($this->runDateTime)) {
            $this->runDateTime = date('Y-m-d H:i:00');
        }

        $tasks = Task::query()
            ->where('isActive', true)
            ->where('nextRunDateTimeUtc', $this->runDateTime)
            ->with('userDevices')
            ->get();

        $fireBaseService = new FirebaseNotificationService;

        /* @var $task Task */
        foreach ($tasks as $task) {
            /* @var $task Task */
            foreach ($task->userDevices as $userDevice) {
                if (! $userDevice->notificationToken) {
                    continue;
                }

                $fireBaseService->sendNotification(
                    $userDevice->notificationToken,
                    trim($task->name),
                    trim($task->description),
                    [
                        'redirectTo' => '/tasks/'.$task->id,
                    ]
                );
            }
        }
    }
}
