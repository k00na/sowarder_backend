<?php

namespace App\Http\Requests\Api\V1\Cashier;

use Illuminate\Foundation\Http\FormRequest;

class BoardIndexRequest extends FormRequest
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
            'scope' => ['sometimes', 'string', 'in:current,today'],
            'occupancy' => ['sometimes', 'string', 'in:all,occupied,vacant'],
        ];
    }

    public function scope(): string
    {
        return $this->string('scope', 'current')->toString();
    }

    public function occupancy(): string
    {
        return $this->string('occupancy', 'all')->toString();
    }
}
