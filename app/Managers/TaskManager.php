<?php

namespace App\Managers;

use App\Enums\TaskPeriodTypesEnum;
use App\Models\Task;

class TaskManager
{
    public function __construct(protected Task $task)
    {
    }

    /**
     *  Calculate Next Run Date Time
     *
     * @param bool $forceMoveToNextPeriod
     * @return array
     */
    public function calculateNextRunDateTime(bool $forceMoveToNextPeriod = false): array
    {
        $currentTimestamp = time() - $this->task->user->timezoneOffset * 60;
        $nextRunDateTime = null;
        $scheduledTimestamp = strtotime(date('Y-m-d ' . $this->task->periodTypeTime . ':00', $currentTimestamp));

        if ($this->task->periodTypeId == TaskPeriodTypesEnum::Daily) {
            if ($currentTimestamp < $scheduledTimestamp) {
                if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (3 * 3600)) {
                    $scheduledTimestamp += 86400;
                }
                $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $scheduledTimestamp);
            } else {
                $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $scheduledTimestamp + 86400);
            }
        } else if ($this->task->periodTypeId == TaskPeriodTypesEnum::Weekly) {
            $i = 0;
            while (!$nextRunDateTime && $i < 100) {
                if ($currentTimestamp < $scheduledTimestamp) {
                    if (in_array(date('N', $scheduledTimestamp), $this->task->periodTypeWeekDays)) {
                        $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $scheduledTimestamp);
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < 86400) {
                            $nextRunDateTime = null;
                        }
                    }
                }
                $scheduledTimestamp += 86400;
                $i++;
            }
        } else if ($this->task->periodTypeId == TaskPeriodTypesEnum::Monthly) {
            $i = 0;
            while (!$nextRunDateTime && $i < 100) {
                if ($currentTimestamp < $scheduledTimestamp) {
                    if (in_array(date('j', $scheduledTimestamp), $this->task->periodTypeMonthDays)) {
                        $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $scheduledTimestamp);
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (2 * 86400)) {
                            $nextRunDateTime = null;
                        }
                    }
                }
                $scheduledTimestamp += 86400;
                $i++;
            }
        } else if ($this->task->periodTypeId == TaskPeriodTypesEnum::Yearly) {
            // make available dates array
            $dates = [];
            foreach ($this->task->periodTypeMonthDays as $day) {
                foreach ($this->task->periodTypeMonths as $month) {
                    $scheduledTimestamp = strtotime(date('Y-' . $month . '-' . $day . ' ' . $this->task->periodTypeTime . ':00'));
                    if ($currentTimestamp < $scheduledTimestamp) {
                        $dates[] = $scheduledTimestamp;
                        if ($forceMoveToNextPeriod && $scheduledTimestamp - $currentTimestamp < (3 * 86400)) {
                            $dates = array_pop($dates);
                        }
                    } else {
                        $dates[] = strtotime(date((string)((int)date('Y') + 1) . '-' . $month . '-' . $day . ' ' . $this->task->periodTypeTime . ':00'));
                    }
                }
            }
            $minDate = 2300000000;
            foreach ($dates as $date) {
                if ($date < $minDate) {
                    $minDate = $date;
                }
            }
            if ($minDate < 2300000000) {
                $nextRunDateTime = date('Y-m-d ' . $this->task->periodTypeTime . ':00', $minDate);
            }
        } else if ($this->task->periodTypeId == TaskPeriodTypesEnum::Once) {
            $nextRunDateTime = null;
        }

        if ($nextRunDateTime) {
            $nextRunDateTimeUtc = date('Y-m-d H:i:00', (strtotime($nextRunDateTime) + $this->task->user->timezoneOffset * 60));
        }

        return [
            'nextRunDateTime' => $nextRunDateTime,
            'nextRunDateTimeUtc' => $nextRunDateTimeUtc ?? null,
        ];
    }
}
