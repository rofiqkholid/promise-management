<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\CalendarEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function index()
    {
        return view('management.calendar.index');
    }

    public function getEvents(Request $request)
    {
        try {
            // Load all database events
            $dbEvents = CalendarEvent::all();
            
            // Separate overrides and normal events
            $normalDbEvents = [];
            $overridesByDate = [];
            foreach ($dbEvents as $event) {
                if ($event->api_holiday_date) {
                    $overridesByDate[$event->api_holiday_date->format('Y-m-d')] = $event;
                } else {
                    $normalDbEvents[] = $event;
                }
            }

            $events = [];

            // Map normal database events
            foreach ($normalDbEvents as $event) {
                // Determine color based on type if not explicitly set
                $color = $event->color;
                if (!$color) {
                    $color = $event->is_holiday ? '#ef4444' : '#3b82f6'; // Solid red or solid blue
                }

                $startStr = $event->start_date->format('Y-m-d');
                if ($event->start_time) {
                    $startStr .= 'T' . $event->start_time;
                }

                $endStr = $event->end_date ? $event->end_date->format('Y-m-d') : $event->start_date->format('Y-m-d');
                if ($event->end_time) {
                    $endStr .= 'T' . $event->end_time;
                } elseif ($event->end_date) {
                    $endStr = $event->end_date->addDay()->format('Y-m-d');
                }

                $events[] = [
                    'id' => 'db-' . $event->id,
                    'title' => $event->title,
                    'start' => $startStr,
                    'end' => $endStr,
                    'allDay' => empty($event->start_time),
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'is_holiday' => $event->is_holiday,
                        'description' => $event->description,
                        'color' => $event->color,
                        'start_time' => $event->start_time ? substr($event->start_time, 0, 5) : null,
                        'end_time' => $event->end_time ? substr($event->end_time, 0, 5) : null,
                        'raw_end' => $event->end_date ? $event->end_date->format('Y-m-d') : $event->start_date->format('Y-m-d'),
                        'is_db' => true,
                        'api_holiday_date' => null
                    ]
                ];
            }

            // Merge dynamic national holidays from external API
            $startYear = $request->input('start') ? date('Y', strtotime($request->input('start'))) : date('Y');
            $endYear = $request->input('end') ? date('Y', strtotime($request->input('end'))) : date('Y');

            $nationalHolidays = [];
            for ($year = $startYear; $year <= $endYear; $year++) {
                $nationalHolidays = array_merge($nationalHolidays, $this->getNationalHolidays($year));
            }

            foreach ($nationalHolidays as $nh) {
                $dateKey = $nh['date'];

                if (isset($overridesByDate[$dateKey])) {
                    $override = $overridesByDate[$dateKey];
                    $isHoliday = (bool) $override->is_holiday;
                    $color = $override->color;
                    if (!$color) {
                        $color = $isHoliday ? '#ef4444' : '#10b981'; // Green for effective working day overrides
                    }

                    $events[] = [
                        'id' => 'db-' . $override->id,
                        'title' => $override->title,
                        'start' => $dateKey,
                        'end' => $dateKey,
                        'allDay' => true,
                        'backgroundColor' => $color,
                        'borderColor' => $color,
                        'extendedProps' => [
                            'is_holiday' => $isHoliday,
                            'description' => $override->description ?? 'Hari Libur Nasional Indonesia (API - Kebijakan Perusahaan)',
                            'color' => $override->color,
                            'raw_end' => $dateKey,
                            'is_db' => true,
                            'api_holiday_date' => $dateKey,
                            'original_title' => $nh['title']
                        ]
                    ];
                } else {
                    $events[] = [
                        'id' => 'national-' . $dateKey,
                        'title' => $nh['title'],
                        'start' => $dateKey,
                        'end' => $dateKey,
                        'allDay' => true,
                        'backgroundColor' => '#ef4444',
                        'borderColor' => '#ef4444',
                        'extendedProps' => [
                            'is_holiday' => true,
                            'description' => 'Hari Libur Nasional Indonesia (API)',
                            'color' => '#ef4444',
                            'raw_end' => $dateKey,
                            'is_db' => false,
                            'api_holiday_date' => $dateKey
                        ]
                    ];
                }
            }

            return response()->json($events);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve calendar events', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'start_time' => 'nullable|string',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_time' => 'nullable|string',
            'api_holiday_date' => 'nullable|date',
            'is_holiday' => 'nullable|boolean',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            $validated['is_holiday'] = $request->boolean('is_holiday', false);
            
            // Set colors if empty
            if ($validated['is_holiday'] && empty($validated['color'])) {
                $validated['color'] = '#ef4444';
            } elseif (!$validated['is_holiday'] && empty($validated['color'])) {
                $validated['color'] = !empty($validated['api_holiday_date']) ? '#10b981' : '#3b82f6';
            }

            if (empty($validated['end_date'])) {
                $validated['end_date'] = $validated['start_date'];
            }

            $event = CalendarEvent::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Event successfully created!',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to create calendar event', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(Request $request, $id)
    {
        $event = CalendarEvent::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'required|date',
            'start_time' => 'nullable|string',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'end_time' => 'nullable|string',
            'api_holiday_date' => 'nullable|date',
            'is_holiday' => 'nullable|boolean',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            $validated['is_holiday'] = $request->boolean('is_holiday', false);

            if ($validated['is_holiday'] && empty($validated['color'])) {
                $validated['color'] = '#ef4444';
            } elseif (!$validated['is_holiday'] && empty($validated['color'])) {
                $validated['color'] = !empty($validated['api_holiday_date']) ? '#10b981' : '#3b82f6';
            }

            if (empty($validated['end_date'])) {
                $validated['end_date'] = $validated['start_date'];
            }

            $event->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Event successfully updated!',
                'event' => $event
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update calendar event', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function destroy($id)
    {
        try {
            $event = CalendarEvent::findOrFail($id);
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event successfully deleted!'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete calendar event', ['id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Get list of dates that are holidays (as Y-m-d strings).
     * Used for SPK plan date calculation.
     */
    public function getHolidays()
    {
        try {
            $holidays = CalendarEvent::where('is_holiday', true)->whereNull('api_holiday_date')->get();
            $holidayDates = [];

            foreach ($holidays as $h) {
                $start = clone $h->start_date;
                $end = clone $h->end_date;

                // Loop through all dates in the range
                while ($start->lte($end)) {
                    $holidayDates[] = $start->format('Y-m-d');
                    $start->addDay();
                }
            }

            // Get DB overrides
            $overrides = CalendarEvent::whereNotNull('api_holiday_date')->get();
            $overridePolicies = [];
            foreach ($overrides as $ov) {
                $overridePolicies[$ov->api_holiday_date->format('Y-m-d')] = (bool) $ov->is_holiday;
            }

            // Merge dynamic national holidays from external API
            $nationalHolidays = $this->getNationalHolidays();
            foreach ($nationalHolidays as $nh) {
                $dateKey = $nh['date'];
                if (isset($overridePolicies[$dateKey])) {
                    if ($overridePolicies[$dateKey] === true) {
                        $holidayDates[] = $dateKey;
                    }
                } else {
                    $holidayDates[] = $dateKey;
                }
            }

            return response()->json(array_unique($holidayDates));
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Fetch Indonesian public holidays from Google Calendar API and cache them.
     */
    private function getNationalHolidays($year = null)
    {
        if ($year) {
            $cacheKey = "id_national_holidays_{$year}_v7";
            $years = [$year];
        } else {
            $cacheKey = "id_national_holidays_multi_v7";
            $years = [now()->year - 1, now()->year, now()->year + 1];
        }

        return cache()->remember($cacheKey, 86400, function () use ($years) {
            $apiKey = env('GOOGLE_CALENDAR_API_KEY');
            if (empty($apiKey)) {
                Log::warning("GOOGLE_CALENDAR_API_KEY is not set in env. Indonesian holidays cannot be loaded from Google Calendar API.");
                return [];
            }

            $calendarId = 'id.indonesian.official#holiday@group.v.calendar.google.com';
            $url = "https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendarId) . "/events";

            $holidays = [];
            foreach ($years as $y) {
                try {
                    $timeMin = "{$y}-01-01T00:00:00Z";
                    $timeMax = "{$y}-12-31T23:59:59Z";

                    $response = \Illuminate\Support\Facades\Http::withHeaders([
                        'Referer' => 'https://promise.summitadyawinsa.co.id/'
                    ])->timeout(5)->get($url, [
                        'key' => $apiKey,
                        'timeMin' => $timeMin,
                        'timeMax' => $timeMax,
                        'singleEvents' => 'true',
                        'orderBy' => 'startTime',
                        'maxResults' => 250,
                    ]);

                    if ($response->successful()) {
                        $data = $response->json();
                        $items = $data['items'] ?? [];

                        foreach ($items as $item) {
                            $date = $item['start']['date'] ?? substr($item['start']['dateTime'] ?? '', 0, 10);
                            if (!empty($date)) {
                                $holidays[] = [
                                    'date' => $date,
                                    'title' => $item['summary'] ?? 'Hari Libur Nasional',
                                ];
                            }
                        }
                    } else {
                        Log::warning("Failed to fetch Google Calendar holidays for year {$y}: " . $response->body());
                    }
                } catch (\Exception $e) {
                    Log::warning("Failed to fetch Google Calendar holidays for year {$y}: " . $e->getMessage());
                }
            }

            return $holidays;
        }) ?? [];
    }
}

