<?php

declare(strict_types=1);

namespace App\Http\Requests\Feedback;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Submit Feedback Request
 *
 * Validates user feedback submission data.
 */
class SubmitFeedbackRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:bug,feature_request,improvement,general'],
            'category' => ['required', 'string', 'in:ui,functionality,performance,content,other'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'priority' => ['nullable', 'string', 'in:low,medium,high'],
            'screenshots' => ['nullable', 'array', 'max:5'],
            'screenshots.*' => ['image', 'max:5120'],
            'browser' => ['nullable', 'string', 'max:100'],
            'os' => ['nullable', 'string', 'max:100'],
            'url' => ['nullable', 'url', 'max:500'],
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
            'type' => 'feedback type',
            'category' => 'category',
            'title' => 'title',
            'description' => 'description',
            'priority' => 'priority',
            'screenshots' => 'screenshots',
            'browser' => 'browser',
            'os' => 'operating system',
            'url' => 'page URL',
        ];
    }
}
