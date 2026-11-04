<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Parameter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Bosh sahifadagi "Tezkor bo'limlar": Kafe-Restoranlar, Naqd-Plastik, Xizmatlar, Yo'lovchilar.
 * Kategoriyalar nomi bo'yicha topiladi (serverda id'lar boshqacha bo'lishi mumkin) — qayta ishga tushsa ham takrorlamaydi.
 * Keyin hammasini admin paneldan o'zgartirish mumkin.
 *
 * Mavjud bazaga — migratsiya (2026_11_04_100001) orqali, yangi o'rnatishda — DatabaseSeeder orqali ishlaydi.
 */
class FeaturedCategorySeeder extends Seeder
{
    /** Yo'lovchilar uchun "Qayerdan / Qayerga" ro'yxati: Navoiy viloyati tumanlari, keyin viloyat markazlari */
    private const CITIES = [
        ['Navoiy', 'Навои', 'Navoi'],
        ['Karmana', 'Кармана', 'Karmana'],
        ['Zarafshon', 'Зарафшан', 'Zarafshan'],
        ['Uchquduq', 'Учкудук', 'Uchkuduk'],
        ['Qiziltepa', 'Кызылтепа', 'Kyzyltepa'],
        ['Nurota', 'Нурата', 'Nurata'],
        ['Konimex', 'Канимех', 'Konimex'],
        ['Tomdi', 'Тамды', 'Tomdi'],
        ['Xatirchi', 'Хатырчи', 'Khatyrchi'],
        ['Navbahor', 'Навбахор', 'Navbahor'],
        ['Toshkent', 'Ташкент', 'Tashkent'],
        ['Samarqand', 'Самарканд', 'Samarkand'],
        ['Buxoro', 'Бухара', 'Bukhara'],
        ['Qarshi', 'Карши', 'Karshi'],
        ['Jizzax', 'Джизак', 'Jizzakh'],
        ['Guliston', 'Гулистан', 'Gulistan'],
        ['Termiz', 'Термез', 'Termez'],
        ['Urganch', 'Ургенч', 'Urgench'],
        ['Nukus', 'Нукус', 'Nukus'],
        ['Andijon', 'Андижан', 'Andijan'],
        ["Farg'ona", 'Фергана', 'Fergana'],
        ['Namangan', 'Наманган', 'Namangan'],
    ];

    private const NEW_CATEGORIES = ['Kafe-Restoranlar', 'Naqd-Plastik', "Yo'lovchilar", 'Uy xizmatlari'];

    private const NEW_PARAMETERS = ['Muassasa turi', 'Ish vaqti', 'Nima qilmoqchisiz?', 'Summa',
        "E'lon beruvchi", 'Qayerdan', 'Qayerga', "Jo'nash vaqti", 'Joylar soni'];

    public function run(): void
    {
        $cities = [
            array_column(self::CITIES, 0),
            array_column(self::CITIES, 1),
            array_column(self::CITIES, 2),
        ];

        DB::transaction(function () use ($cities) {
            // ===== Kafe-Restoranlar: muddatsiz, rasmli =====
            $cafe = $this->category(
                ['Kafe-Restoranlar', 'Кафе и рестораны', 'Cafes & Restaurants'],
                ['image' => 'UtensilsCrossed', 'is_page' => true, 'with_price' => false, 'with_image' => true,
                    'is_permanent' => true, 'featured_order' => 1],
            );
            $this->attach($cafe, [
                [$this->select(
                    ['Muassasa turi', 'Тип заведения', 'Venue type'],
                    [
                        ['Kafe', 'Restoran', 'Choyxona', 'Fast-food', "To'yxona", 'Uy taomlari (uyda pishiriladi)'],
                        ['Кафе', 'Ресторан', 'Чайхана', 'Фастфуд', 'Банкетный зал', 'Домашняя еда (готовят дома)'],
                        ['Cafe', 'Restaurant', 'Teahouse', 'Fast food', 'Banquet hall', 'Home-cooked food'],
                    ],
                ), false],
                [$this->text(
                    ['Ish vaqti', 'Время работы', 'Opening hours'],
                    ['Masalan: 09:00 – 23:00', 'Например: 09:00 – 23:00', 'E.g. 09:00 – 23:00'],
                ), false],
                // "Yetkazib berish" maydoni yo'q: bo'lim buyurtma uchun — bu yerdagi kafelar yetkazib beradi
            ]);

            // ===== Naqd-Plastik: 1 kun =====
            $cash = $this->category(
                ['Naqd-Plastik', 'Наличные-Карта', 'Cash-Card'],
                ['image' => 'ArrowLeftRight', 'is_page' => true, 'with_price' => false, 'with_image' => false, 'with_title' => false,
                    'listing_duration_days' => 1, 'featured_order' => 2],
            );
            $this->attach($cash, [
                [$this->select(
                    ['Nima qilmoqchisiz?', 'Что вы хотите?', 'What do you want?'],
                    [
                        ['Naqd beraman, plastikka olaman', 'Plastikdan beraman, naqd olaman'],
                        ['Даю наличные, получаю на карту', 'Перевожу с карты, получаю наличные'],
                        ['I give cash, get it on card', 'I pay by card, get cash'],
                    ],
                ), true],
                [$this->number(
                    ['Summa', 'Сумма', 'Amount'],
                    ['Masalan: 1 000 000', 'Например: 1 000 000', 'E.g. 1 000 000'],
                    ["so'm", 'сум', 'UZS'],
                ), true],
            ]);

            // ===== Xizmatlar: mavjud bo'lim, ichiga "Uy xizmatlari" qo'shiladi =====
            $services = Category::whereNull('parent_id')->where('name', 'Xizmatlar')->first();
            if ($services) {
                $services->forceFill(['is_featured' => true, 'featured_order' => 3])->save();

                $home = $this->category(
                    ['Uy xizmatlari', 'Бытовые услуги', 'Home services'],
                    ['is_page' => true, 'with_price' => true, 'with_image' => true,
                        'listing_duration_days' => $services->listing_duration_days ?: 7, 'is_featured' => false],
                    $services->id,
                );
                $this->attach($home, [
                    [Parameter::where('title', "Xizmat ko'rsatish joyi")->first(), false],
                    [Parameter::where('title', 'Xizmat tajribasi')->first(), false],
                ]);
            }

            // ===== Yo'lovchilar: 1 kun, kim / qayerdan / qayerga / qachon =====
            $rides = $this->category(
                ["Yo'lovchilar", 'Попутчики', 'Rideshare'],
                ['image' => 'CarTaxiFront', 'is_page' => true, 'with_price' => false, 'with_image' => false,
                    'listing_duration_days' => 1, 'featured_order' => 4],
            );
            $this->attach($rides, [
                [$this->select(
                    ["E'lon beruvchi", 'Кто объявляет', 'Posted by'],
                    [
                        ["Yo'lovchi (joy kerak)", 'Haydovchi (joy bor)'],
                        ['Пассажир (нужно место)', 'Водитель (есть места)'],
                        ['Passenger (need a seat)', 'Driver (seats available)'],
                    ],
                ), true],
                [$this->select(['Qayerdan', 'Откуда', 'From'], $cities), true],
                [$this->select(['Qayerga', 'Куда', 'To'], $cities), true],
                [$this->text(
                    ["Jo'nash vaqti", 'Время выезда', 'Departure time'],
                    ['Masalan: bugun 18:00', 'Например: сегодня 18:00', 'E.g. today 6 PM'],
                ), true],
                [$this->number(
                    ['Joylar soni', 'Количество мест', 'Seats'],
                    ['Masalan: 2', 'Например: 2', 'E.g. 2'],
                ), false],
            ]);
        });
    }

    /** Migratsiyani orqaga qaytarish: e'lonlari bor kategoriyalarga tegilmaydi (e'lonlar yo'qolmasin) */
    public function rollback(): void
    {
        DB::transaction(function () {
            foreach (self::NEW_CATEGORIES as $name) {
                $category = Category::where('name', $name)->first();
                if (! $category || $category->products()->withTrashed()->exists()) {
                    continue;
                }
                DB::table('category_parameters')->where('category_id', $category->id)->delete();
                $category->forceDelete();
            }

            Category::whereNull('parent_id')->where('name', 'Xizmatlar')->update(['is_featured' => false, 'featured_order' => 0]);

            Parameter::whereIn('title', self::NEW_PARAMETERS)
                ->whereNotIn('id', DB::table('category_parameters')->select('parameter_id'))
                ->delete();
        });
    }

    /** @param array{0: string, 1: string, 2: string} $names uz, ru, en */
    private function category(array $names, array $attributes, ?int $parentId = null): Category
    {
        $category = Category::where('parent_id', $parentId)->where('name', $names[0])->first()
            ?? new Category(['name' => $names[0], 'parent_id' => $parentId]);

        $category->forceFill([
            'listing_duration_days' => 30,
            'is_featured' => true,
            ...$attributes,
            'translations' => ['ru' => ['name' => $names[1]], 'en' => ['name' => $names[2]]],
        ])->save();

        return $category;
    }

    /** @param array<int, array{0: ?Parameter, 1: bool}> $parameters [parametr, majburiymi] tartib bo'yicha */
    private function attach(Category $category, array $parameters): void
    {
        foreach (array_values($parameters) as $order => [$parameter, $required]) {
            if (! $parameter) {
                continue;
            }
            DB::table('category_parameters')->updateOrInsert(
                ['category_id' => $category->id, 'parameter_id' => $parameter->id],
                ['is_required' => $required, 'sort_order' => $order],
            );
        }
    }

    /** @param array{0: array, 1: array, 2: array} $options uz, ru, en variantlari (bir xil tartibda) */
    private function select(array $titles, array $options): Parameter
    {
        return $this->parameter($titles, 'FieldSelect', 'string', ['Tanlang', 'Выберите', 'Select'], null, $options);
    }

    private function text(array $titles, array $placeholders): Parameter
    {
        return $this->parameter($titles, 'FieldText', 'string', $placeholders);
    }

    private function number(array $titles, array $placeholders, ?array $units = null): Parameter
    {
        return $this->parameter($titles, 'FieldNumber', 'number', $placeholders, $units);
    }

    private function parameter(
        array $titles,
        string $component,
        string $type,
        array $placeholders,
        ?array $units = null,
        ?array $options = null,
    ): Parameter {
        $translations = [];
        foreach (['ru' => 1, 'en' => 2] as $locale => $i) {
            $translations[$locale] = array_filter([
                'title' => $titles[$i],
                'placeholder' => $placeholders[$i],
                'unit' => $units[$i] ?? null,
                'options' => $options[$i] ?? null,
            ]);
        }

        return Parameter::updateOrCreate(
            ['title' => $titles[0], 'component' => $component],
            [
                'placeholder' => $placeholders[0],
                'type' => $type,
                'unit' => $units[0] ?? null,
                'options' => $options[0] ?? null,
                'translations' => $translations,
            ],
        );
    }
}
