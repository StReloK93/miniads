<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContentTranslationSeeder extends Seeder
{
    /**
     * Mavjud kategoriya, parametr, shahar va narx turlarini ru/en tiliga to'ldiradi (database/data/content_translations.php).
     * Faqat tarjimasi bo'sh qatorlar: admin keyin o'zgartirgan tarjimalar qayta yozilmaydi.
     */
    public function run(): void
    {
        $data = require database_path('data/content_translations.php');

        $this->fillSimple('categories', 'name', $data['categories']);
        $this->fillSimple('districts', 'name', $data['districts']);
        $this->fillSimple('price_types', 'type', $data['price_types']);

        foreach (DB::table('parameters')->whereNull('translations')->get() as $parameter) {
            $row = $data['parameters'][$parameter->title] ?? null;
            if (! $row) {
                continue;
            }

            $options = json_decode($parameter->options ?? 'null', true);
            $translations = [];
            foreach (['ru' => 0, 'en' => 1] as $locale => $index) {
                [$title, $unit, $optionLabels] = $row[$index] + [null, null, null];
                $translations[$locale] = array_filter([
                    'title' => $title,
                    'unit' => $unit,
                    'placeholder' => $this->placeholder($parameter->placeholder, $locale),
                    // Tartib va soni asl ro'yxat bilan mos bo'lsagina (aks holda noto'g'ri variant ko'rinib qolardi)
                    'options' => is_array($options) && is_array($optionLabels) && count($options) === count($optionLabels)
                        ? $optionLabels
                        : null,
                ], fn ($value) => $value !== null);
            }

            DB::table('parameters')->where('id', $parameter->id)->update([
                'translations' => json_encode($translations, JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    private function fillSimple(string $table, string $field, array $map): void
    {
        foreach (DB::table($table)->whereNull('translations')->get(['id', $field]) as $row) {
            [$ru, $en] = $map[$row->{$field}] ?? [null, null];
            if (! $ru) {
                continue;
            }

            DB::table($table)->where('id', $row->id)->update([
                'translations' => json_encode(['ru' => [$field => $ru], 'en' => [$field => $en]], JSON_UNESCAPED_UNICODE),
            ]);
        }
    }

    /** "Masalan: 65" → "Например: 65" / "e.g. 65"; "Tanlang" → "Выберите" / "Select" */
    private function placeholder(?string $value, string $locale): ?string
    {
        if (! $value) {
            return null;
        }
        if ($value === 'Tanlang') {
            return $locale === 'ru' ? 'Выберите' : 'Select';
        }
        if (str_starts_with($value, 'Masalan:')) {
            return ($locale === 'ru' ? 'Например:' : 'e.g.') . substr($value, strlen('Masalan:'));
        }

        return null;
    }
}
