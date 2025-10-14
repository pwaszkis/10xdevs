<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitFeedbackRequest extends FormRequest
{
    /**
     * Available issue types when feedback is negative.
     */
    public const ISSUE_NOT_ENOUGH_DETAILS = 'not_enough_details';

    public const ISSUE_NOT_MATCHING_PREFERENCES = 'not_matching_preferences';

    public const ISSUE_POOR_ITINERARY_ORDER = 'poor_itinerary_order';

    public const ISSUE_OTHER = 'other';

    /**
     * @var list<string>
     */
    private const ALLOWED_ISSUES = [
        self::ISSUE_NOT_ENOUGH_DETAILS,
        self::ISSUE_NOT_MATCHING_PREFERENCES,
        self::ISSUE_POOR_ITINERARY_ORDER,
        self::ISSUE_OTHER,
    ];

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Authorize that plan belongs to authenticated user
        $plan = $this->route('plan');

        return $plan && $this->user()->id === $plan->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'satisfied' => 'required|boolean',
            'issues' => 'array|required_if:satisfied,false',
            'issues.*' => [
                'string',
                'in:' . implode(',', self::ALLOWED_ISSUES),
            ],
            'other_comment' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'satisfied.required' => 'Musisz wybrać czy plan spełnia Twoje oczekiwania.',
            'satisfied.boolean' => 'Nieprawidłowa wartość feedbacku.',
            'issues.required_if' => 'Wybierz przynajmniej jeden problem, jeśli plan nie spełnia oczekiwań.',
            'issues.array' => 'Nieprawidłowy format problemów.',
            'issues.*.in' => 'Wybrano nieprawidłowy typ problemu.',
            'other_comment.max' => 'Komentarz może mieć maksymalnie 1000 znaków.',
        ];
    }

    /**
     * Get sanitized and validated data.
     *
     * @return array<string, mixed>
     */
    public function sanitized(): array
    {
        $data = [
            'satisfied' => (bool) $this->satisfied,
            'issues' => null,
        ];

        // Only include issues if feedback is negative
        if (! $this->satisfied && $this->has('issues')) {
            $issues = array_values(array_unique($this->issues ?? []));

            // If 'other' is selected and there's a comment, include it
            if (in_array(self::ISSUE_OTHER, $issues, true) && $this->filled('other_comment')) {
                $issues[] = 'other: ' . strip_tags((string) $this->other_comment);
            }

            $data['issues'] = $issues;
        }

        return $data;
    }

    /**
     * Get all allowed issue types.
     *
     * @return list<string>
     */
    public static function getAllowedIssues(): array
    {
        return self::ALLOWED_ISSUES;
    }
}
