<?php
namespace App\Services;
use App\Models\Task;

class CalculateNextRunDateTimeService {

    protected $task;

    public function __construct(Task $task) {
        $this->task = $task;
    }

    /**
     * Calculate Next Run Date Time For a Task
     * @param type $forceMoveToNextPeriod
     * @return type array
     */
    public function calculateNextRunDateTime($forceMoveToNextPeriod = false): array {
        $currentTimestamp = time() - $this->task->user->timezoneOffset * 60;
        $nextRunDateTime = null;
        $scheduledTimestamp = strtotime(date('Y-m-d ' . $this->task->periodTypeTime . ':00', $currentTimestamp));
        // daily
        if ($this->task->periodType == 1) {
            if ($currentTimestamp < $scheduledTimestamp) {
                if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (3 * 3600))
                    $scheduledTimestamp += 86400;
                $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $scheduledTimestamp);
            } else {
                $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $scheduledTimestamp + 86400);
            }
        }
        // weekly
        else if ($this->task->periodType == 2) {
            $i = 0;
            while (!$nextRunDateTime && $i < 100) {
                if ($currentTimestamp < $scheduledTimestamp) {
                    if (in_array(date('N', $scheduledTimestamp), $this->task->periodTypeWeekDays)) {
                        $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $scheduledTimestamp);
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < 86400)
                            $nextRunDateTime = null;
                    }
                }
                $scheduledTimestamp += 86400;
                $i++;
            }
        }
        // monthly
        else if ($this->task->periodType == 3) {
            $i = 0;
            while (!$nextRunDateTime && $i < 100) {
                if ($currentTimestamp < $scheduledTimestamp) {
                    if (in_array(date('j', $scheduledTimestamp), $this->task->periodTypeMonthDays)) {
                        $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $scheduledTimestamp);
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (2 * 86400))
                            $nextRunDateTime = null;
                    }
                }
                $scheduledTimestamp += 86400;
                $i++;
            }
        }
        // yearly and once
        else if (in_array($this->task->periodType, [4, 5])) {
            // make available dates array
            $dates = [];
            foreach ($this->task->periodTypeMonthDays as $day) {
                foreach ($this->task->periodTypeMonths as $month) {
                    $scheduledTimestamp = strtotime(date('Y-' . $month . '-' . $day . ' ' . $this->task->periodTypeTime . ':00'));
                    if ($currentTimestamp < $scheduledTimestamp) {
                        $dates[] = $scheduledTimestamp;
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (3 * 86400))
                            $dates = array_pop($dates);
                    } else {
                        $dates[] = strtotime(date((string) ((int) date('Y') + 1) . '-' . $month . '-' . $day . ' ' . $this->task->periodTypeTime . ':00'));
                    }
                }
            }
            $minDate = 2300000000;
            foreach ($dates as $date) {
                if ($date < $minDate)
                    $minDate = $date;
            }
            if ($minDate < 2300000000)
                $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $minDate);
        }

        return [
            'nextRunDateTime' => $nextRunDateTime,
            'nextRunDateTimeUtc' => date('Y-m-d H:i:00', (strtotime($nextRunDateTime) + $this->task->user->timezoneOffset * 60)),
        ];
    }

}
