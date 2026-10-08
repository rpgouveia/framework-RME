<?php

namespace App\Http\Requests;

use App\Enums\SystemChangeType;
use App\Support\AiRiskDomains;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A change of the system (0021): its type, description and date, and the
 * MIT risk subdomains it affects, if known.
 */
class StoreSystemChangeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(SystemChangeType::class)],
            'description' => ['required', 'string', 'max:2000'],
            'change_date' => ['required', 'date', 'before_or_equal:today'],
            // Optional: without them, every verified link is reverted.
            'risk_subdomains' => ['sometimes', 'nullable', 'list'],
            'risk_subdomains.*' => [
                'string',
                'distinct',
                Rule::in(app(AiRiskDomains::class)->subdomains()->pluck('code')->all()),
            ],
        ];
    }

    /**
     * Get the custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'change_date.before_or_equal' => __('The change date cannot be in the future.'),
            'risk_subdomains.*.in' => __('The code :input is not a subdomain of the MIT AI risk domain taxonomy.'),
            'risk_subdomains.*.distinct' => __('The risk subdomain :input was selected more than once.'),
        ];
    }
}
