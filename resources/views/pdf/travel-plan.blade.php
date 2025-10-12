<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $plan->title }} - {{ $plan->destination }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1f2937;
        }

        .container {
            padding: 20px;
        }

        /* Header Section */
        .plan-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #3b82f6;
        }

        .plan-title {
            font-size: 24pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 10px;
        }

        .plan-meta {
            font-size: 11pt;
            color: #6b7280;
            margin-bottom: 8px;
        }

        .plan-meta strong {
            color: #374151;
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            background-color: #dbeafe;
            color: #1e40af;
            border-radius: 12px;
            font-size: 9pt;
            font-weight: 600;
            margin-top: 8px;
        }

        /* Assumptions Section */
        .assumptions-section {
            background-color: #f9fafb;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            border-left: 4px solid #3b82f6;
        }

        .assumptions-title {
            font-size: 13pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .user-notes {
            font-size: 10pt;
            color: #4b5563;
            margin-bottom: 12px;
            white-space: pre-wrap;
        }

        .preferences {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .preference-badge {
            display: inline-block;
            padding: 4px 10px;
            background-color: #e0e7ff;
            color: #3730a3;
            border-radius: 6px;
            font-size: 9pt;
        }

        /* Days Section */
        .day-card {
            page-break-inside: avoid;
            margin-bottom: 25px;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            overflow: hidden;
        }

        .day-header {
            background-color: #3b82f6;
            color: white;
            padding: 12px 15px;
            font-size: 14pt;
            font-weight: bold;
        }

        .day-summary {
            font-size: 10pt;
            font-weight: normal;
            margin-top: 4px;
            opacity: 0.95;
        }

        .day-content {
            padding: 15px;
        }

        /* Day Part */
        .day-part {
            margin-bottom: 20px;
        }

        .day-part:last-child {
            margin-bottom: 0;
        }

        .day-part-title {
            font-size: 12pt;
            font-weight: bold;
            color: #374151;
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        /* Point */
        .point {
            page-break-inside: avoid;
            margin-bottom: 15px;
            padding: 12px;
            background-color: #f9fafb;
            border-radius: 6px;
            border-left: 3px solid #60a5fa;
        }

        .point:last-child {
            margin-bottom: 0;
        }

        .point-name {
            font-size: 11pt;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 6px;
        }

        .point-description {
            font-size: 10pt;
            color: #4b5563;
            margin-bottom: 6px;
            line-height: 1.5;
        }

        .point-justification {
            font-size: 9pt;
            color: #6b7280;
            font-style: italic;
            margin-bottom: 8px;
        }

        .point-meta {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 9pt;
            color: #6b7280;
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px solid #e5e7eb;
        }

        .point-duration {
            font-weight: 600;
        }

        .point-maps-link {
            color: #2563eb;
            text-decoration: none;
            word-break: break-all;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            bottom: 10px;
            right: 10px;
            font-size: 8pt;
            color: #9ca3af;
            font-style: italic;
        }

        /* Page Break Control */
        .page-break {
            page-break-after: always;
        }

        .no-break {
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    <div class="container">
        {{-- Plan Header --}}
        <div class="plan-header">
            <h1 class="plan-title">{{ $plan->title }}</h1>

            <div class="plan-meta">
                <strong>Destynacja:</strong> {{ $plan->destination }}
            </div>

            <div class="plan-meta">
                <strong>Data wyjazdu:</strong> {{ $plan->start_date->format('d.m.Y') }}
            </div>

            <div class="plan-meta">
                <strong>Liczba dni:</strong> {{ $plan->number_of_days }}
                | <strong>Liczba osób:</strong> {{ $plan->number_of_people }}
            </div>

            @if($plan->budget_per_person)
                <div class="plan-meta">
                    <strong>Budżet na osobę:</strong> {{ number_format($plan->budget_per_person, 2) }} {{ $plan->currency ?? 'PLN' }}
                </div>
            @endif

            <span class="status-badge">
                @if($plan->status === 'planned')
                    Zaplanowane
                @elseif($plan->status === 'completed')
                    Zrealizowane
                @else
                    {{ ucfirst($plan->status) }}
                @endif
            </span>
        </div>

        {{-- Assumptions Section --}}
        @if($plan->user_notes || ($plan->user->preferences && count($plan->user->preferences->toArray()) > 0))
            <div class="assumptions-section no-break">
                <h2 class="assumptions-title">Twoje założenia</h2>

                @if($plan->user_notes)
                    <div class="user-notes">{{ $plan->user_notes }}</div>
                @endif

                @if($plan->user->preferences)
                    @php
                        $prefs = $plan->user->preferences;
                        $allPreferences = [];

                        if ($prefs->interests) {
                            $allPreferences = array_merge($allPreferences, $prefs->interests);
                        }

                        if ($prefs->travel_pace) {
                            $allPreferences[] = 'Tempo: ' . ucfirst($prefs->travel_pace);
                        }

                        if ($prefs->budget_preference) {
                            $allPreferences[] = 'Budżet: ' . ucfirst($prefs->budget_preference);
                        }

                        if ($prefs->transport_preference) {
                            $allPreferences[] = 'Transport: ' . $prefs->transport_preference;
                        }

                        if ($prefs->accessibility_needs) {
                            $allPreferences[] = 'Ograniczenia: ' . $prefs->accessibility_needs;
                        }
                    @endphp

                    @if(count($allPreferences) > 0)
                        <div class="preferences">
                            @foreach($allPreferences as $preference)
                                <span class="preference-badge">{{ $preference }}</span>
                            @endforeach
                        </div>
                    @endif
                @endif
            </div>
        @endif

        {{-- Plan Days --}}
        @if($plan->days && $plan->days->count() > 0)
            @foreach($plan->days as $day)
                <div class="day-card">
                    <div class="day-header">
                        Dzień {{ $day->day_number }} - {{ $day->date->format('d.m.Y') }}
                        @if($day->summary)
                            <div class="day-summary">{{ $day->summary }}</div>
                        @endif
                    </div>

                    <div class="day-content">
                        @php
                            $pointsByDayPart = $day->points->groupBy('day_part');
                            $dayPartOrder = ['rano', 'poludnie', 'popołudnie', 'wieczór'];
                            $dayPartLabels = [
                                'rano' => 'Rano',
                                'poludnie' => 'Południe',
                                'popołudnie' => 'Popołudnie',
                                'wieczór' => 'Wieczór',
                            ];
                        @endphp

                        @foreach($dayPartOrder as $dayPart)
                            @if(isset($pointsByDayPart[$dayPart]))
                                <div class="day-part">
                                    <h3 class="day-part-title">
                                        @if($dayPart === 'rano') 🌅 @endif
                                        @if($dayPart === 'poludnie') ☀️ @endif
                                        @if($dayPart === 'popołudnie') 🌇 @endif
                                        @if($dayPart === 'wieczór') 🌙 @endif
                                        {{ $dayPartLabels[$dayPart] ?? ucfirst($dayPart) }}
                                    </h3>

                                    @foreach($pointsByDayPart[$dayPart] as $point)
                                        <div class="point">
                                            <div class="point-name">{{ $point->name }}</div>

                                            <div class="point-description">{{ $point->description }}</div>

                                            @if($point->justification)
                                                <div class="point-justification">
                                                    {{ $point->justification }}
                                                </div>
                                            @endif

                                            <div class="point-meta">
                                                @if($point->duration_minutes)
                                                    <span class="point-duration">
                                                        ⏱️ {{ $point->duration_minutes }} min
                                                    </span>
                                                @endif

                                                @if($point->google_maps_url)
                                                    <span>
                                                        📍 <a href="{{ $point->google_maps_url }}" class="point-maps-link">
                                                            Zobacz na mapie
                                                        </a>
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                {{-- Page break after every 2 days --}}
                @if($loop->iteration % 2 === 0 && !$loop->last)
                    <div class="page-break"></div>
                @endif
            @endforeach
        @else
            <p style="text-align: center; color: #9ca3af; margin: 40px 0;">
                Brak szczegółów planu do wyświetlenia.
            </p>
        @endif
    </div>

    <div class="watermark">
        Generated by VibeTravels
    </div>
</body>
</html>
