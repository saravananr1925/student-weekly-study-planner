@php
use Carbon\Carbon;
use App\Models\Schedule;

@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Study Schedule</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f7f9;
            padding: 20px;
            text-align: center;
        }
        .container {
            max-width: 700px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 15px;
        }
        .day-container {
            background: #ffffff;
            padding: 15px;
            margin: 10px 0;
            border-left: 5px solid #3498db;
            border-radius: 5px;
            text-align: left;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.1);
        }
        .study-time {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        ul {
            list-style: none;
            padding: 0;
        }
        li {
            background: #ecf0f1;
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
            font-size: 14px;
            display: flex;
            justify-content: space-between;
        }
        .time {
            font-weight: bold;
            color: #e74c3c;
        }
    </style>
</head>
<body>

    <div class="container">
        <h2> Weekly Study Schedule</h2>

        @foreach($schedule as $date => $dayPlanArr)
        @if($dayPlanArr['type'] == 1)
            @php
                $totalMinutes = $dayPlanArr['study_time']; 
                $hours = floor($totalMinutes / 60);
                $minutes = $totalMinutes % 60;
            @endphp
            <div class="day-container">
                <p class="study-time">{{ \Carbon\Carbon::parse($date)->format('d-M-Y') }} 
                    @if($hours > 0 || $minutes > 0)
                        (Study Time: 
                        @if($hours > 0) {{ $hours }}h @endif 
                        @if($minutes > 0) {{ $minutes }}m @endif)
                    @endif</p>
                <ul>
                    @foreach($dayPlanArr['activities'] as $key => $subject)
                    <li>{{$subject['name']}}
                         <span class="time">({{$subject['duration']. ' m'}})</span>

                     </li>
                    @endforeach
                   
                </ul>
            </div>
        @else 
        <div class="day-container">
            <p class="study-time">{{ \Carbon\Carbon::parse($date)->format('d-M-Y') }} ({{ $dayPlanArr['type'] == Schedule::WEEKEND ? 'Weekend' : 'Holiday'}})</p>
        </div>

        @endif
        @endforeach

     

        

     

    </div>

</body>
</html>
