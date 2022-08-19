<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Task;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

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
        $expoPushToken = 'ExponentPushToken[kIDjBHI4CgM_dQlI7k6xhn]';
        $postData = [
            'to' => $expoPushToken,
            'sound' => 'default',
            'title' => 'Laravel Title',
            'body' => 'And here is the body!',
            'data' => [
                'redirectTo' => '/tasks/1',
            ],
        ];

        $this->sendPost('https://exp.host/--/api/v2/push/send', $postData, [
            'Accept: application/json',
            'Accept-encoding: gzip, deflate',
            'Content-Type: application/json',
        ]);
    }
}
