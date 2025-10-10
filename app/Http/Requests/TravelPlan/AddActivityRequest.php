<?php

declare(strict_types=1);

namespace App\Http\Requests\TravelPlan;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Add Activity Request
 *
 * Validates data for adding an activity to a travel plan.
 */
class AddActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('travelPlan'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', 'string', 'in:sightseeing,restaurant,activity,accommodation,transport,other'],
            'date' => ['required', 'date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'location' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'booking_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'activity name',
            'description' => 'description',
            'type' => 'activity type',
            'date' => 'date',
            'start_time' => 'start time',
            'end_time' => 'end time',
            'location' => 'location',
            'latitude' => 'latitude',
            'longitude' => 'longitude',
            'cost' => 'cost',
            'currency' => 'currency',
            'booking_reference' => 'booking reference',
            'notes' => 'notes',
        ];
    }
}
