<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * E'lon yaratish (POST /products) va yangilash (POST /products/{id}) uchun umumiy validatsiya.
 */
class SaveProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // 0 = "Barcha shaharlar" => null
        if ((int) $this->input('district_id') === 0) {
            $this->merge(['district_id' => null]);
        }
    }

    public function rules(): array
    {
        $isUpdate = $this->route('id') !== null;

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:10000'],
            'category_id' => [
                $isUpdate ? 'nullable' : 'required',
                'integer',
                Rule::exists('categories', 'id')->whereNull('deleted_at'),
            ],
            'district_id' => ['nullable', 'integer', Rule::exists('districts', 'id')],
            'phone' => ['nullable', 'string', 'max:32'],
            'price' => ['nullable', 'numeric', 'min:0', 'max:9999999999999'],
            'price_type_id' => ['nullable', 'integer', Rule::exists('price_types', 'id')],
            'back_color_id' => ['nullable', 'integer', Rule::exists('back_colors', 'id')],
            'parameters' => ['nullable', 'array'],
            'parameters.*.id' => ['required', 'integer'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*.file' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:20480'],
        ];
    }
}
