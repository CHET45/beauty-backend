<?php

namespace App\Http\Requests\Admin;

use App\Rules\SpecialistPhoto;
use Illuminate\Foundation\Http\FormRequest;

class StoreSpecialistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'title' => ['nullable', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'photo_url' => ['nullable', new SpecialistPhoto],
            'is_active' => ['sometimes', 'boolean'],
            'service_ids' => ['array'],
            'service_ids.*' => ['integer', 'exists:services,id'],
        ];
    }
}
