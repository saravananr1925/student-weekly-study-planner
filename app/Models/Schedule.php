<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Schedule extends Model
{
    public $activities, $schedule = [], $perDayStudyMin = 120, $threshold = 10;
    public $holidays = ['2025-03-19', '2025-04-04'];

    const WORKING_DAY = 1;
    const WEEKEND = 2;
    const HOLIDAY = 3;

    public function __construct()
    {
        $this->loadActivities();
    }

    public function loadActivities()
    {
        $data = json_decode(file_get_contents("https://kp-lms-static.s3.us-east-2.amazonaws.com/activities.json"), true);
        $this->activities = $data['activities'] ?? [];
    }

    public function generateSchedule()
    {
        $startDate = $this->getStartDate();
        $remainingMinutes = $this->perDayStudyMin;

        foreach ($this->activities as $activity) {
            $activityDuration = $activity['durationMinutes'];
            $activityName = $activity['name'];

            if ($remainingMinutes < $activityDuration) {
                if ($remainingMinutes + $this->threshold >= $activityDuration) {
                    $this->schedule[$startDate->toDateString()]['activities'][] = [
                        "name" => $activityName, 
                        'duration' => $activityDuration
                    ];
                    $this->schedule[$startDate->toDateString()]['study_time'] += $activityDuration;

                    $remainingMinutes -= $activityDuration;
                } else {
                    $splitDuration = $remainingMinutes;
                    $remainingPart = $activityDuration - $splitDuration;

                    $this->schedule[$startDate->toDateString()]['activities'][] = [
                        "name" => $activityName . ' (Part 1)', 
                        'duration' => $splitDuration
                    ];
                    $this->schedule[$startDate->toDateString()]['study_time'] += $splitDuration;

                    $startDate = $this->getNextAvailableDate($startDate);
                    $remainingMinutes = $this->perDayStudyMin;

                    $this->splitActivityAcrossDays($activityName, $remainingPart, $startDate, 2, $remainingMinutes);
                }
            } else {
                if (!isset($this->schedule[$startDate->toDateString()])) {
                    $this->schedule[$startDate->toDateString()] = [
                        'study_time' => 0,
                        'activities' => [],
                        'type' => self::WORKING_DAY
                    ];
                }

                $this->schedule[$startDate->toDateString()]['activities'][] = [
                    "name" => $activityName, 
                    'duration' => $activityDuration
                ];
                $this->schedule[$startDate->toDateString()]['study_time'] += $activityDuration;

                $remainingMinutes -= $activityDuration;
            }

            if ($remainingMinutes <= 0) {
                $startDate = $this->getNextAvailableDate($startDate);
                $remainingMinutes = $this->perDayStudyMin;
            }
        }

        return $this->schedule;
    }

    public function splitActivityAcrossDays($activityName, $duration, &$date, $partNumber = 2, &$remainingMinutes)
    {
        while ($duration > 0) {
            if (!isset($this->schedule[$date->toDateString()])) {
                $this->schedule[$date->toDateString()] = [
                    'study_time' => 0,
                    'activities' => [],
                    'type' => self::WORKING_DAY
                ];
            }

            if ($remainingMinutes <= 0) {
                $date = $this->getNextAvailableDate($date);
                $remainingMinutes = $this->perDayStudyMin;
            }

            $studyMinutes = min($remainingMinutes, $duration);

            $this->schedule[$date->toDateString()]['activities'][] = [
                "name" => $activityName . " (Part $partNumber)", 
                'duration' => $studyMinutes
            ];
            $this->schedule[$date->toDateString()]['study_time'] += $studyMinutes;

            $duration -= $studyMinutes;
            $remainingMinutes -= $studyMinutes;
            $partNumber++;

            if ($remainingMinutes <= 0 && $duration > 0) {
                $date = $this->getNextAvailableDate($date);
                $remainingMinutes = $this->perDayStudyMin;
            }
        }
    }

    public function getNextAvailableDate($date)
    {
        $nextDate = $date->copy()->addDay();

        while ($this->isHolidayOrWeekend($nextDate)) {
            $this->setHolidayOrWeekend($nextDate);
            $nextDate = $nextDate->addDay();
        }

        return $nextDate;
    }

    public function getStartDate()
    {
        $startDate = Carbon::now();

        while ($this->isHolidayOrWeekend($startDate)) {
            $this->setHolidayOrWeekend($startDate);
            $startDate = $startDate->addDay();
        }

        return $startDate;
    }

    public function isHolidayOrWeekend($date)
    {
        return in_array($date->toDateString(), $this->holidays) || $date->isWeekend();
    }

    public function setHolidayOrWeekend($date)
    {
        $this->schedule[$date->toDateString()] = [
            'study_time' => 0,
            'activities' => [],
            'type' => $date->isWeekend() ? self::WEEKEND : self::HOLIDAY
        ];
    }
}