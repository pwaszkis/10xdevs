<?php

declare(strict_types=1);

namespace App\Http\Requests\TravelPlan;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Generate Itinerary Request
 *
 * Validates data for AI-powered itinerary generation.
 */
class GenerateItineraryRequest extends FormRequest
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
            'preferences' => ['nullable', 'array'],
            'preferences.pace' => ['nullable', 'string', 'in:relaxed,moderate,fast'],
            'preferences.style' => ['nullable', 'string', 'in:budget,comfort,luxury'],
            'preferences.focus' => ['nullable', 'array'],
            'preferences.focus.*' => ['string', 'in:culture,food,adventure,relaxation,shopping,nightlife'],
            'preferences.include_meals' => ['nullable', 'boolean'],
            'preferences.include_transport' => ['nullable', 'boolean'],
            'preferences.accessibility_needs' => ['nullable', 'string', 'max:500'],
            'additional_notes' => ['nullable', 'string', 'max:1000'],
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
            'preferences.pace' => 'travel pace',
            'preferences.style' => 'travel style',
            'preferences.focus' => 'focus areas',
            'preferences.include_meals' => 'include meal suggestions',
            'preferences.include_transport' => 'include transport details',
            'preferences.accessibility_needs' => 'accessibility needs',
            'additional_notes' => 'additional notes',
        ];
    }
}
