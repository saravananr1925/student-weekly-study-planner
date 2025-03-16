<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Schedule;
use Carbon\Carbon;

class ScheduleTest extends TestCase
{
    public function test_schedule_structure_is_correct()
    {
        $schedule = new Schedule();
        $generatedSchedule = $schedule->generateSchedule();
        
        $this->assertIsArray($generatedSchedule, 'Schedule should be an array');
        
        foreach ($generatedSchedule as $date => $day) {
            $this->assertArrayHasKey('study_time', $day);
            $this->assertArrayHasKey('activities', $day);
            $this->assertArrayHasKey('type', $day);
        }
    }

    public function test_study_time_does_not_exceed_limit()
    {
        $schedule = new Schedule();
        $generatedSchedule = $schedule->generateSchedule();
        
        foreach ($generatedSchedule as $date => $day) {
            if ($day['type'] === Schedule::WORKING_DAY) {
                $this->assertLessThanOrEqual($schedule->perDayStudyMin + $schedule->threshold, $day['study_time'], "Study time on $date exceeds $schedule->perDayStudyMin + $schedule->threshold minutes");
            }
        }
    }

    public function test_holidays_and_weekends_are_empty()
    {
        $schedule = new Schedule();
        $generatedSchedule = $schedule->generateSchedule();
        
        foreach ($generatedSchedule as $date => $day) {
            if ($day['type'] === Schedule::HOLIDAY || $day['type'] === Schedule::WEEKEND) {
                $this->assertEmpty($day['activities'], "There should be no activities on $date");
            }
        }
    }

    public function test_activities_are_split_properly()
    {
        $schedule = new Schedule();
        $generatedSchedule = $schedule->generateSchedule();
        
        foreach ($generatedSchedule as $date => $day) {
            $totalTime = array_sum(array_column($day['activities'], 'duration'));
            $this->assertEquals($day['study_time'], $totalTime, "Mismatch in total study time on $date");
        }
    }

    public function test_dynamic_holiday_check()
    {
        $schedule = new Schedule();
        $randomHoliday = $schedule->holidays[array_rand($schedule->holidays)];
        $generatedSchedule = $schedule->generateSchedule();
        
        if (isset($generatedSchedule[$randomHoliday])) {
            $this->assertEquals(Schedule::HOLIDAY, $generatedSchedule[$randomHoliday]['type'], "$randomHoliday should be marked as a holiday");
            $this->assertEmpty($generatedSchedule[$randomHoliday]['activities'], "Activities should not be scheduled on $randomHoliday");
        }
    }
}
