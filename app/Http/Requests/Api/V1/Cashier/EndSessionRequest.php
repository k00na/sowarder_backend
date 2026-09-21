<?php

namespace App\Http\Requests\Api\V1\Cashier;

use Illuminate\Foundation\Http\FormRequest;

class EndSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'confirm_unacknowledged' => ['sometimes', 'boolean'],
        ];
    }

    public function confirmUnacknowledged(): bool
    {
        return $this->boolean('confirm_unacknowledged');
    }
}
