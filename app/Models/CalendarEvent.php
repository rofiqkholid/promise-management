<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalendarEvent extends Model
{
    use HasFactory;

    protected $table = 'mng_calendar_events';

    protected $fillable = [
        'title',
        'start_date',
        'end_date',
        'is_holiday',
        'description',
        'color',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'is_holiday' => 'boolean',
    ];
}
