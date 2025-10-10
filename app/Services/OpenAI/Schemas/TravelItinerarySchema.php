<?php

namespace App\Services\OpenAI\Schemas;

class TravelItinerarySchema
{
    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return [
            'type' => 'json_schema',
            'json_schema' => [
                'name' => 'travel_itinerary',
                'strict' => true,
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'destination' => [
                            'type' => 'string',
                            'description' => 'The destination city and country',
                        ],
                        'duration_days' => [
                            'type' => 'integer',
                            'description' => 'Number of days for the trip',
                        ],
                        'days' => [
                            'type' => 'array',
                            'description' => 'Daily itinerary',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'day_number' => [
                                        'type' => 'integer',
                                        'description' => 'Day number (1-indexed)',
                                    ],
                                    'date' => [
                                        'type' => 'string',
                                        'description' => 'Date in YYYY-MM-DD format',
                                    ],
                                    'activities' => [
                                        'type' => 'array',
                                        'description' => 'Activities for the day',
                                        'items' => [
                                            'type' => 'object',
                                            'properties' => [
                                                'time' => [
                                                    'type' => 'string',
                                                    'description' => 'Time in HH:MM format',
                                                ],
                                                'activity' => [
                                                    'type' => 'string',
                                                    'description' => 'Description of activity',
                                                ],
                                                'location' => [
                                                    'type' => 'string',
                                                    'description' => 'Location name',
                                                ],
                                                'cost_estimate' => [
                                                    'type' => 'number',
                                                    'description' => 'Estimated cost in USD',
                                                ],
                                                'category' => [
                                                    'type' => 'string',
                                                    'enum' => ['sightseeing', 'food', 'entertainment', 'shopping', 'relaxation', 'transport'],
                                                    'description' => 'Activity category',
                                                ],
                                            ],
                                            'required' => ['time', 'activity', 'location', 'cost_estimate', 'category'],
                                            'additionalProperties' => false,
                                        ],
                                    ],
                                    'daily_budget' => [
                                        'type' => 'number',
                                        'description' => 'Total budget for the day',
                                    ],
                                ],
                                'required' => ['day_number', 'date', 'activities', 'daily_budget'],
                                'additionalProperties' => false,
                            ],
                        ],
                        'total_cost_estimate' => [
                            'type' => 'number',
                            'description' => 'Total estimated cost for entire trip',
                        ],
                        'tips' => [
                            'type' => 'array',
                            'description' => 'General tips for the trip',
                            'items' => [
                                'type' => 'string',
                            ],
                        ],
                    ],
                    'required' => ['destination', 'duration_days', 'days', 'total_cost_estimate', 'tips'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
