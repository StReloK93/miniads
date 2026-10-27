<?php

namespace App\Models\Concerns;

/**
 * Bazadagi matnlar uchun ko'p tillilik. Asosiy (o'zbekcha) qiymat o'z ustunida, tarjimalar translations JSON'da.
 * JSON'ga aylantirishda joriy til (App::getLocale) bo'yicha almashtiriladi; tarjima bo'lmasa — o'zbekchasi.
 * Server mantig'i (qidiruv, filtrlar) doim asl qiymat bilan ishlaydi — almashtirish faqat chiqishda.
 *
 * Modelda: protected array $translatable = ['name'];
 *          protected array $translatableLists = ['options']; // ixtiyoriy: ro'yxatlar (tartibi asl bilan bir xil)
 *
 * Admin panel (o'zbekcha, X-Locale'siz) tarjimalarni tekis maydonlar bilan oladi va yuboradi: name_ru, name_en, options_ru...
 */
trait HasTranslations
{
    /** Asosiy (uz) tildan tashqari tillar; ro'yxat: App\Http\Middleware\SetLocale::LOCALES */
    public const TRANSLATED_LOCALES = ['ru', 'en'];

    public function initializeHasTranslations(): void
    {
        $this->mergeCasts(['translations' => 'array']);
    }

    /** Joriy (yoki berilgan) tildagi qiymat */
    public function translated(string $field, ?string $locale = null): mixed
    {
        $locale ??= app()->getLocale();
        $value = $locale === 'uz' ? null : ($this->translations[$locale][$field] ?? null);

        return filled($value) ? $value : $this->getAttribute($field);
    }

    /** Admin formasidan: name_ru, name_en, options_ru (ro'yxat)... → translations */
    public function fillTranslations(array $input): static
    {
        $translations = $this->translations ?? [];

        foreach (self::TRANSLATED_LOCALES as $locale) {
            foreach ([...$this->translatable, ...$this->translatableListFields()] as $field) {
                $key = "{$field}_{$locale}";
                if (! array_key_exists($key, $input)) {
                    continue;
                }

                $value = $input[$key];
                $value = is_array($value) ? array_values(array_filter(array_map('trim', $value), 'strlen')) : trim((string) $value);
                if ($value === '' || $value === []) {
                    unset($translations[$locale][$field]);
                } else {
                    $translations[$locale][$field] = $value;
                }
            }
        }

        $this->translations = array_filter($translations) ?: null;

        return $this;
    }

    /** Validatsiya qoidalari: name_ru, name_en... (controller'larda) */
    public static function translationRules(): array
    {
        $model = new static();
        $rules = [];
        foreach (self::TRANSLATED_LOCALES as $locale) {
            foreach ($model->translatable as $field) {
                $rules["{$field}_{$locale}"] = ['nullable', 'string', 'max:255'];
            }
            foreach ($model->translatableListFields() as $field) {
                $rules["{$field}_{$locale}"] = ['nullable', 'array'];
                $rules["{$field}_{$locale}.*"] = ['nullable', 'string', 'max:255'];
            }
        }

        return $rules;
    }

    public function attributesToArray(): array
    {
        $attributes = parent::attributesToArray();
        $locale = app()->getLocale();

        if ($locale !== 'uz') {
            foreach ($this->translatable as $field) {
                $value = $this->translations[$locale][$field] ?? null;
                if (filled($value) && array_key_exists($field, $attributes)) {
                    $attributes[$field] = $value;
                }
            }

            return $attributes;
        }

        // O'zbekcha (admin panel) — tahrirlash formasi uchun tekis tarjima maydonlari
        foreach (self::TRANSLATED_LOCALES as $translatedLocale) {
            foreach ([...$this->translatable, ...$this->translatableListFields()] as $field) {
                $attributes["{$field}_{$translatedLocale}"] = $this->translations[$translatedLocale][$field] ?? null;
            }
        }

        return $attributes;
    }

    private function translatableListFields(): array
    {
        return property_exists($this, 'translatableLists') ? $this->translatableLists : [];
    }
}
