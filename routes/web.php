<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StudyScheduleController;


Route::get('/', [StudyScheduleController::class, 'index']);