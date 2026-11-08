<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Parameter;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

        // Narx bo'sh yoki 0 — "Kelishiladi". FormData null maydonni umuman yubormaydi, shunda tahrirlashda
        // eski narx qolib ketardi — shuning uchun maydon doim mavjud bo'ladi (bo'sh bo'lsa null)
        $price = $this->input('price');
        $this->merge(['price' => is_numeric($price) && (float) $price > 0 ? $price : null]);

        // Sarlavha so'ralmaydigan kategoriya: sarlavhani tanlangan parametrlardan yasaymiz (tahrirlashda ham yangilanadi)
        $category = $this->category();
        if ($category && ! $category->with_title) {
            $values = collect($this->input('parameters', []))
                ->filter(fn ($item) => is_array($item) && isset($item['id']))
                ->mapWithKeys(fn ($item) => [(int) $item['id'] => $item['value'] ?? null])
                ->all();
            $this->merge(['title' => $category->autoTitle($values)]);
        }
    }

    /** E'lon kategoriyasi: yangi e'londa — so'rovdan, tahrirlashda — so'rovdan yoki e'lonning o'zidan */
    private function category(): ?Category
    {
        $categoryId = $this->input('category_id')
            ?: ($this->route('id') ? Product::withTrashed()->whereKey($this->route('id'))->value('category_id') : null);

        return $categoryId ? Category::find($categoryId) : null;
    }

    /** Admin "majburiy" deb belgilagan parametrlar to'ldirilgan bo'lishi kerak */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $category = $this->category();
                if (! $category) {
                    return;
                }
                $filled = collect($this->input('parameters', []))
                    ->filter(fn ($item) => is_array($item) && filled($item['value'] ?? null))
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id);

                $category->parameters()->wherePivot('is_required', true)->get()
                    ->reject(fn (Parameter $parameter) => $filled->contains($parameter->id))
                    ->each(fn (Parameter $parameter) => $validator->errors()->add(
                        "parameters.{$parameter->id}",
                        __('validation.required', ['attribute' => $parameter->title]),
                    ));
            },
        ];
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
