<?php

declare(strict_types=1);

namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Feedback Request
 *
 * Validates feedback update data (admin only).
 */
class UpdateFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:pending,in_review,in_progress,resolved,closed,rejected'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high'],
            'admin_notes' => ['nullable', 'string', 'max:1000'],
            'assigned_to' => ['nullable', 'exists:users,id'],
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
            'status' => 'status',
            'priority' => 'priority',
            'admin_notes' => 'admin notes',
            'assigned_to' => 'assigned to',
        ];
    }
}
