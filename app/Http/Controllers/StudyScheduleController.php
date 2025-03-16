<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Schedule;

class StudyScheduleController extends Controller
{

    public function index()
    {
        $model = new Schedule();
        $schedule = $model->generateSchedule();
        return view('study-schedule.index', compact('schedule'));
    }

    
}
