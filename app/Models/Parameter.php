<?php

namespace App\Models;

use App\Models\Concerns\HasTranslations;
use Illuminate\Database\Eloquent\Model;

class Parameter extends Model
{
    use HasTranslations {
        attributesToArray as translatedAttributesToArray;
    }

    public $timestamps = false;

    protected array $translatable = ['title', 'placeholder', 'unit'];

    protected array $translatableLists = ['options'];

    protected $fillable = ['title', 'placeholder', 'type', 'component', 'unit', 'options', 'translations'];

    protected $casts = [
        'options' => 'array',
    ];

    /**
     * Tanlov variantlari tarjimasi tartib bo'yicha: translations.ru.options[i] ↔ options[i].
     * E'londa asl (o'zbekcha) variant saqlanadi — filtr va qidiruv shunga tayanadi; tarjima faqat ko'rsatish uchun.
     */
    public function optionLabels(?string $locale = null): ?array
    {
        if (! is_array($this->options)) {
            return null;
        }

        $locale ??= app()->getLocale();
        $labels = $locale === 'uz' ? null : ($this->translations[$locale]['options'] ?? null);

        return array_map(
            fn ($option, $index) => filled($labels[$index] ?? null) ? $labels[$index] : $option,
            $this->options,
            array_keys($this->options),
        );
    }

    /** Asl variantning shu tildagi nomi (e'lon parametrini ko'rsatish uchun) */
    public function optionLabel(string $value): string
    {
        $index = is_array($this->options) ? array_search($value, $this->options, true) : false;

        return $index === false ? $value : ($this->optionLabels()[$index] ?? $value);
    }

    public function attributesToArray(): array
    {
        return [
            ...$this->translatedAttributesToArray(),
            // Mantiq uchun asl birlik ("yil", "-qavat"): ilova yil/qavat maydonlarini shundan taniydi
            'unit_code' => $this->getAttribute('unit'),
            'option_labels' => $this->optionLabels(),
        ];
    }
}
