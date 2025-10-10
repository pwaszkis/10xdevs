<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Update Preferences Request
 *
 * Validates user preferences update data.
 */
class UpdatePreferencesRequest extends FormRequest
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
            'language' => ['sometimes', 'string', 'in:en,es,fr,de,it,pt'],
            'timezone' => ['sometimes', 'string', 'timezone'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'notifications_enabled' => ['sometimes', 'boolean'],
            'email_notifications' => ['sometimes', 'boolean'],
            'push_notifications' => ['sometimes', 'boolean'],
            'theme' => ['sometimes', 'string', 'in:light,dark,auto'],
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
            'language' => 'preferred language',
            'timezone' => 'timezone',
            'currency' => 'preferred currency',
            'notifications_enabled' => 'notifications',
            'email_notifications' => 'email notifications',
            'push_notifications' => 'push notifications',
            'theme' => 'theme preference',
        ];
    }
}
