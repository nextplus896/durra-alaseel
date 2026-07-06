<?php

namespace App\Http\Requests\Admin;

use App\Constants\CancellationPolicyConst;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates admin form submissions for the global cancellation policy.
 *
 * Conditional rules:
 *   - deduction_value  is required only when deduction_type != 'none'
 *   - service_fee_value is required only when service_fee_type != 'none'
 */
class StoreCancellationPolicyRequest extends FormRequest
{
    /**
     * Only admins can update the cancellation policy.
     */
    public function authorize(): bool
    {
        return auth()->guard('admin')->check();
    }

    public function rules(): array
    {
        return [
            'cancellation_window_hours' => [
                'required',
                'integer',
                'min:0',
                'max:8760', // 1 year cap
            ],
            'deduction_type' => [
                'required',
                Rule::in(CancellationPolicyConst::DEDUCTION_TYPES),
            ],
            'deduction_value' => [
                Rule::requiredIf(fn() => $this->input('deduction_type') !== CancellationPolicyConst::DEDUCTION_NONE),
                'nullable',
                'numeric',
                'min:0',
                'max:99999',
            ],
            'service_fee_type' => [
                'required',
                Rule::in(CancellationPolicyConst::FEE_TYPES),
            ],
            'service_fee_value' => [
                Rule::requiredIf(fn() => $this->input('service_fee_type') !== CancellationPolicyConst::FEE_NONE),
                'nullable',
                'numeric',
                'min:0',
                'max:99999',
            ],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancellation_window_hours.required' => __('Cancellation window hours is required.'),
            'cancellation_window_hours.integer'  => __('Cancellation window hours must be a whole number.'),
            'cancellation_window_hours.min'      => __('Cancellation window hours cannot be negative.'),
            'deduction_type.required'            => __('Rental deduction type is required.'),
            'deduction_type.in'                  => __('Invalid rental deduction type selected.'),
            'deduction_value.required'           => __('Rental deduction value is required when a deduction type is selected.'),
            'deduction_value.numeric'            => __('Rental deduction value must be a number.'),
            'deduction_value.min'                => __('Rental deduction value cannot be negative.'),
            'service_fee_type.required'          => __('Service fee type is required.'),
            'service_fee_type.in'                => __('Invalid service fee type selected.'),
            'service_fee_value.required'         => __('Service fee value is required when a service fee type is selected.'),
            'service_fee_value.numeric'          => __('Service fee value must be a number.'),
            'service_fee_value.min'              => __('Service fee value cannot be negative.'),
        ];
    }

    /**
     * Normalise the request before validation.
     *
     * When deduction_type is 'none', force deduction_value to 0 so the
     * database is always consistent regardless of what was submitted.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('deduction_type') === CancellationPolicyConst::DEDUCTION_NONE) {
            $this->merge(['deduction_value' => 0]);
        }

        if ($this->input('service_fee_type') === CancellationPolicyConst::FEE_NONE) {
            $this->merge(['service_fee_value' => 0]);
        }
    }
}
