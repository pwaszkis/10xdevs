<?php

declare(strict_types=1);

namespace App\Http\Requests\TravelPlan;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Travel Plan Request
 *
 * Validates data for updating an existing travel plan.
 */
class UpdateTravelPlanRequest extends FormRequest
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
            'destination' => ['sometimes', 'string', 'max:255'],
            'start_date' => ['sometimes', 'date', 'after_or_equal:today'],
            'end_date' => ['sometimes', 'date', 'after:start_date'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'travelers_count' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'preferences' => ['nullable', 'array'],
            'preferences.accommodation_type' => ['nullable', 'string', 'in:hotel,hostel,apartment,resort,other'],
            'preferences.interests' => ['nullable', 'array'],
            'preferences.interests.*' => ['string', 'max:100'],
            'preferences.dietary_restrictions' => ['nullable', 'array'],
            'preferences.dietary_restrictions.*' => ['string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'string', 'in:draft,planning,confirmed,in_progress,completed,cancelled'],
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
            'destination' => 'destination',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'budget' => 'budget',
            'currency' => 'currency',
            'travelers_count' => 'number of travelers',
            'preferences.accommodation_type' => 'accommodation type',
            'preferences.interests' => 'interests',
            'preferences.dietary_restrictions' => 'dietary restrictions',
            'notes' => 'additional notes',
            'status' => 'status',
        ];
    }
}
