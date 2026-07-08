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
            $events = CalendarEvent::all()->map(function ($event) {
                // Determine color based on type if not explicitly set
                $color = $event->color;
                if (!$color) {
                    $color = $event->is_holiday ? '#ffe2dd' : '#e3f2fd'; // Notion default soft red or blue pastel
                }

                return [
                    'id' => 'db-' . $event->id,
                    'title' => $event->title,
                    'start' => $event->start_date->format('Y-m-d'),
                    // FullCalendar end date is exclusive, so add 1 day if end_date exists
                    'end' => $event->end_date ? $event->end_date->addDay()->format('Y-m-d') : $event->start_date->format('Y-m-d'),
                    'allDay' => true,
                    'backgroundColor' => $color,
                    'borderColor' => $color,
                    'extendedProps' => [
                        'is_holiday' => $event->is_holiday,
                        'description' => $event->description,
                        'color' => $event->color,
                        'raw_end' => $event->end_date ? $event->end_date->format('Y-m-d') : $event->start_date->format('Y-m-d'),
                        'is_db' => true
                    ]
                ];
            })->toArray();

            // Merge dynamic national holidays from external API
            $nationalHolidays = $this->getNationalHolidays();
            foreach ($nationalHolidays as $nh) {
                $events[] = [
                    'id' => 'national-' . $nh['date'],
                    'title' => $nh['title'],
                    'start' => $nh['date'],
                    'end' => $nh['date'],
                    'allDay' => true,
                    'backgroundColor' => '#ffe2dd', // Red pastel to match holidays
                    'borderColor' => '#ffe2dd',
                    'extendedProps' => [
                        'is_holiday' => true,
                        'description' => 'Hari Libur Nasional Indonesia (API)',
                        'color' => '#ffe2dd',
                        'raw_end' => $nh['date'],
                        'is_db' => false
                    ]
                ];
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
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_holiday' => 'nullable|boolean',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            $validated['is_holiday'] = $request->boolean('is_holiday', false);
            
            // Set red color for holiday if color is not custom
            if ($validated['is_holiday'] && empty($validated['color'])) {
                $validated['color'] = '#ef4444';
            } elseif (!$validated['is_holiday'] && empty($validated['color'])) {
                $validated['color'] = '#3b82f6';
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
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_holiday' => 'nullable|boolean',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:50',
        ]);

        try {
            $validated['is_holiday'] = $request->boolean('is_holiday', false);

            if ($validated['is_holiday'] && empty($validated['color'])) {
                $validated['color'] = '#ef4444';
            } elseif (!$validated['is_holiday'] && empty($validated['color'])) {
                $validated['color'] = '#3b82f6';
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
            $holidays = CalendarEvent::where('is_holiday', true)->get();
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

            // Merge dynamic national holidays from external API
            $nationalHolidays = $this->getNationalHolidays();
            foreach ($nationalHolidays as $nh) {
                $holidayDates[] = $nh['date'];
            }

            return response()->json(array_unique($holidayDates));
        } catch (\Exception $e) {
            return response()->json([], 500);
        }
    }

    /**
     * Fetch Indonesian public holidays from Google Calendar API and cache them.
     */
    private function getNationalHolidays()
    {
        return cache()->remember('id_national_holidays_cache_v3', 86400, function () {
            $apiKey = env('GOOGLE_CALENDAR_API_KEY');
            if (empty($apiKey)) {
                Log::warning("GOOGLE_CALENDAR_API_KEY is not set in env. Indonesian holidays cannot be loaded from Google Calendar API.");
                return [];
            }

            $calendarId = 'id.indonesian.official#holiday@group.v.calendar.google.com';
            $url = "https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendarId) . "/events";

            try {
                $timeMin = (now()->year - 1) . '-01-01T00:00:00Z';
                $timeMax = (now()->year + 1) . '-12-31T23:59:59Z';

                $response = \Illuminate\Support\Facades\Http::timeout(5)->get($url, [
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
                    $holidays = [];

                    foreach ($items as $item) {
                        $date = $item['start']['date'] ?? substr($item['start']['dateTime'] ?? '', 0, 10);
                        if (!empty($date)) {
                            $holidays[] = [
                                'date' => $date,
                                'title' => $item['summary'] ?? 'Hari Libur Nasional',
                            ];
                        }
                    }

                    return $holidays;
                } else {
                    Log::warning("Failed to fetch Google Calendar holidays: " . $response->body());
                }
            } catch (\Exception $e) {
                Log::warning("Failed to fetch Google Calendar holidays: " . $e->getMessage());
            }

            return [];
        }) ?? [];
    }
}

