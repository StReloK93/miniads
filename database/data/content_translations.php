<?php

/**
 * Bazadagi mavjud kontent tarjimasi (o'zbekcha nom → ru/en). ContentTranslationSeeder ishlatadi (php artisan db:seed --class=ContentTranslationSeeder).
 * Yangi kategoriya/parametr tarjimalari admin panelda kiritiladi.
 * Parametrlar: options tartibi asl (o'zbekcha) ro'yxat bilan bir xil bo'lishi shart.
 */
return [
    'categories' => [
        'Uy-joy' => ['Недвижимость', 'Real estate'],
        'Sotuv' => ['Продажа', 'For sale'],
        'Kvartira' => ['Квартиры', 'Apartments'],
        'Hovli' => ['Дома', 'Houses'],
        'Yer' => ['Участки', 'Land'],
        "Do'kon-Ofis" => ['Магазины и офисы', 'Shops & offices'],
        'Ijara' => ['Аренда', 'For rent'],
        'Texnika' => ['Электроника', 'Electronics'],
        'Telefon' => ['Телефоны', 'Phones'],
        'Noutbuk-Kompyuter' => ['Ноутбуки и компьютеры', 'Laptops & computers'],
        'Maishiy texnika' => ['Бытовая техника', 'Home appliances'],
        'TV-Audio' => ['ТВ и аудио', 'TV & audio'],
        'Avto' => ['Транспорт', 'Vehicles'],
        'Yengil avto' => ['Легковые авто', 'Cars'],
        'Mototsikl' => ['Мотоциклы', 'Motorcycles'],
        'Avtobus-Yuk tashuvchi' => ['Автобусы и грузовики', 'Buses & trucks'],
        'Ehtiyot qismlar' => ['Запчасти', 'Spare parts'],
        'Ish' => ['Работа', 'Jobs'],
        'Doimiy ish' => ['Постоянная работа', 'Full-time jobs'],
        'Kunlik ish' => ['Подработка', 'Day jobs'],
        'Masofaviy ish' => ['Удалённая работа', 'Remote jobs'],
        'Xizmat takliflari' => ['Предложения услуг', 'Service offers'],
        'Xizmatlar' => ['Услуги', 'Services'],
        "Qurilish va ta'mirlash" => ['Строительство и ремонт', 'Construction & repair'],
        'Usta xizmatlari' => ['Услуги мастеров', 'Handyman services'],
        'Avto xizmatlar' => ['Автоуслуги', 'Car services'],
        'IT va texnik xizmatlar' => ['IT и техуслуги', 'IT & tech services'],
        'Mebel' => ['Мебель', 'Furniture'],
        'Divan-Krovat' => ['Диваны и кровати', 'Sofas & beds'],
        'Stol-Stul' => ['Столы и стулья', 'Tables & chairs'],
        'Shkaf' => ['Шкафы', 'Wardrobes'],
        'Oshxona mebeli' => ['Кухонная мебель', 'Kitchen furniture'],
        'Talim' => ['Обучение', 'Education'],
        'Kurslar' => ['Курсы', 'Courses'],
        'Repetitor' => ['Репетиторы', 'Tutors'],
        'Online darslar' => ['Онлайн-уроки', 'Online lessons'],
        'Hayvonot olami' => ['Животные', 'Animals'],
        'Qoramol' => ['Крупный скот', 'Cattle'],
        "Qo'y-Echki" => ['Овцы и козы', 'Sheep & goats'],
        'Parrandalar' => ['Птица', 'Poultry'],
        'Uy hayvonlari' => ['Домашние питомцы', 'Pets'],
        'Shaxsiy buyumlar' => ['Личные вещи', 'Personal items'],
        'Kiyim-kechak' => ['Одежда', 'Clothing'],
        'Poyabzal' => ['Обувь', 'Shoes'],
        'Parfyumeriya va kosmetika' => ['Парфюмерия и косметика', 'Perfume & cosmetics'],
        'Aksessuarlar' => ['Аксессуары', 'Accessories'],
        'Topilmalar' => ['Находки', 'Lost & found'],
        'Topilgan' => ['Найдено', 'Found'],
        "Yo'qotilgan" => ['Потеряно', 'Lost'],
        'Bepul beriladi' => ['Отдам даром', 'Free giveaway'],
        'Boshqa' => ['Другое', 'Other'],
    ],

    'districts' => [
        'Uchquduq' => ['Учкудук', 'Uchkuduk'],
        'Zarafshon' => ['Зарафшан', 'Zarafshan'],
        'Navoiy' => ['Навои', 'Navoi'],
        'Karmana' => ['Кармана', 'Karmana'],
        'Qiziltepa' => ['Кызылтепа', 'Kyziltepa'],
    ],

    'price_types' => [
        "So'm" => ['сум', 'UZS'],
    ],

    // [ru, en]: title, unit (bo'lsa), options (bo'lsa)
    'parameters' => [
        'Xonalar soni' => [['Количество комнат', 'комн.'], ['Rooms', 'rooms']],
        'Maydoni' => [['Площадь', 'м²'], ['Area', 'm²']],
        'Qavati' => [['Этаж', 'этаж'], ['Floor', 'floor']],
        'Umumiy qavatlar' => [['Этажность', 'этажей'], ['Total floors', 'floors']],
        "Ta'mir turi" => [
            ['Ремонт', null, ['Евроремонт', 'Хороший ремонт', 'Средний', 'Без ремонта (коробка)']],
            ['Renovation', null, ['Euro renovation', 'Well renovated', 'Average', 'Unfinished (shell)']],
        ],
        'Jihozlanganligi' => [
            ['Меблировка', null, ['Полностью (мебель + техника)', 'Частично', 'Без мебели']],
            ['Furnishing', null, ['Fully furnished (furniture + appliances)', 'Partly furnished', 'Unfurnished']],
        ],
        'Yer maydoni' => [['Площадь участка', 'сот.'], ['Plot size', 'ares']],
        'Yer maqsadi' => [
            ['Назначение земли', null, ['Под жилое строительство', 'Сельское хозяйство', 'Коммерция / производство']],
            ['Land use', null, ['Residential construction', 'Agriculture', 'Commercial / industrial']],
        ],
        'Bino turi' => [
            ['Тип здания', null, ['Бизнес-центр / ТЦ', 'Отдельное здание', '1-й этаж многоэтажки']],
            ['Building type', null, ['Business / shopping center', 'Detached building', 'Ground floor of an apartment block']],
        ],
        'Ishlab chiqarilgan yili' => [['Год выпуска', 'г.'], ['Year', '']],
        'Yurgan masofasi' => [['Пробег', 'км'], ['Mileage', 'km']],
        'Kuzov turi' => [
            ['Тип кузова', null, ['Седан', 'Хэтчбек', 'Кроссовер / SUV', 'Универсал', 'Купе', 'Пикап', 'Минивэн']],
            ['Body type', null, ['Sedan', 'Hatchback', 'Crossover / SUV', 'Wagon', 'Coupe', 'Pickup', 'Minivan']],
        ],
        'Uzatish qutisi (Korobka)' => [
            ['Коробка передач', null, ['Механика', 'Автомат', 'Вариатор', 'Робот']],
            ['Transmission', null, ['Manual', 'Automatic', 'CVT', 'Robotic']],
        ],
        "Yoqilg'i turi" => [
            ['Тип топлива', null, ['Бензин', 'Газ (метан)', 'Газ (пропан)', 'Дизель', 'Гибрид', 'Электро']],
            ['Fuel', null, ['Petrol', 'Gas (methane)', 'Gas (propane)', 'Diesel', 'Hybrid', 'Electric']],
        ],
        'Avto holati' => [
            ['Состояние авто', null, ['Отличное', 'Хорошее', 'Среднее', 'Требует ремонта']],
            ['Car condition', null, ['Excellent', 'Good', 'Average', 'Needs repair']],
        ],
        'Avto rangi' => [
            ['Цвет авто', null, ['Белый', 'Чёрный', 'Серый (мокрый асфальт)', 'Серебристый', 'Синий', 'Красный', 'Другой']],
            ['Car color', null, ['White', 'Black', 'Grey (wet asphalt)', 'Silver', 'Blue', 'Red', 'Other']],
        ],
        'Mototsikl turi' => [
            ['Тип мотоцикла', null, ['Скутер', 'Мопед', 'Спортбайк', 'Классика / дорожный', 'Кросс / эндуро', 'Квадроцикл', 'Электроскутер']],
            ['Motorcycle type', null, ['Scooter', 'Moped', 'Sport bike', 'Classic / road', 'Cross / enduro', 'ATV', 'Electric scooter']],
        ],
        'Dvigatel hajmi' => [['Объём двигателя', 'см³'], ['Engine size', 'cc']],
        'Yuk mashinasi turi' => [
            ['Тип грузовика', null, ['Грузовик (фура)', 'Самосвал', 'Газель / бортовой', 'Микроавтобус', 'Автобус', 'Спецтехника']],
            ['Truck type', null, ['Truck (semi)', 'Dump truck', 'Gazelle / flatbed', 'Minibus', 'Bus', 'Special machinery']],
        ],
        "Yuk ko'tarish quvvati" => [
            ['Грузоподъёмность', null, ['до 1,5 т', '1,5–3 т', '3–5 т', '5–10 т', 'более 10 т']],
            ['Payload', null, ['up to 1.5 t', '1.5–3 t', '3–5 t', '5–10 t', 'over 10 t']],
        ],
        'Qism turi' => [
            ['Тип запчасти', null, ['Кузовные детали', 'Двигатель и трансмиссия', 'Ходовая часть', 'Шины и диски', 'Электроника и оптика', 'Аксессуары', 'Масла и жидкости']],
            ['Part type', null, ['Body parts', 'Engine & transmission', 'Suspension', 'Tires & wheels', 'Electronics & lights', 'Accessories', 'Oils & fluids']],
        ],
        'Telefon markasi' => [
            ['Марка телефона', null, ['Apple (iPhone)', 'Samsung', 'Xiaomi (Redmi/Poco)', 'Honor', 'Huawei', 'Vivo', 'Realme', 'Infinix', 'Tecno', 'Другая']],
            ['Phone brand', null, ['Apple (iPhone)', 'Samsung', 'Xiaomi (Redmi/Poco)', 'Honor', 'Huawei', 'Vivo', 'Realme', 'Infinix', 'Tecno', 'Other']],
        ],
        'Doimiy xotira (ROM)' => [['Встроенная память (ROM)'], ['Storage (ROM)']],
        'Tezkor xotira (RAM)' => [['Оперативная память (RAM)'], ['Memory (RAM)']],
        'Telefon holati' => [
            ['Состояние телефона', null, ['Новый (в коробке)', 'Идеальное (как новый)', 'Хорошее', 'Требует ремонта']],
            ['Phone condition', null, ['New (boxed)', 'Mint (like new)', 'Good', 'Needs repair']],
        ],
        'Telefon rangi' => [
            ['Цвет телефона', null, ['Чёрный', 'Белый / серебристый', 'Синий', 'Серый', 'Золотой (Gold)', 'Фиолетовый', 'Зелёный', 'Другой']],
            ['Phone color', null, ['Black', 'White / silver', 'Blue', 'Grey', 'Gold', 'Purple', 'Green', 'Other']],
        ],
        'Kompyuter turi' => [
            ['Тип компьютера', null, ['Ноутбук', 'Настольный ПК', 'Моноблок (All-in-One)', 'Нетбук']],
            ['Computer type', null, ['Laptop', 'Desktop PC', 'All-in-One', 'Netbook']],
        ],
        'Protsessor' => [
            ['Процессор', null, ['Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9', 'AMD Ryzen 3', 'AMD Ryzen 5', 'AMD Ryzen 7', 'Apple M1 / M2 / M3', 'Другой']],
            ['Processor', null, ['Intel Core i3', 'Intel Core i5', 'Intel Core i7', 'Intel Core i9', 'AMD Ryzen 3', 'AMD Ryzen 5', 'AMD Ryzen 7', 'Apple M1 / M2 / M3', 'Other']],
        ],
        'Kompyuter RAM' => [['Оперативная память'], ['Memory (RAM)']],
        'Xotira turi va hajmi (SSD/HDD)' => [['Накопитель (SSD/HDD)'], ['Storage (SSD/HDD)']],
        'Videokarta' => [
            ['Видеокарта', null, ['Встроенная (Intel/AMD/Apple)', 'NVIDIA GeForce GTX', 'NVIDIA GeForce RTX (игровая)', 'AMD Radeon']],
            ['Graphics card', null, ['Integrated (Intel/AMD/Apple)', 'NVIDIA GeForce GTX', 'NVIDIA GeForce RTX (gaming)', 'AMD Radeon']],
        ],
        'Kompyuter holati' => [
            ['Состояние компьютера', null, ['Новый', 'Отличное (идеал)', 'Среднее', 'Требует ремонта']],
            ['Computer condition', null, ['New', 'Excellent (mint)', 'Average', 'Needs repair']],
        ],
        'Maishiy texnika turi' => [
            ['Вид техники', null, ['Холодильник', 'Стиральная машина', 'Кондиционер', 'Газовая плита / духовка', 'Пылесос', 'Микроволновка', 'Посудомоечная машина', 'Другое']],
            ['Appliance type', null, ['Refrigerator', 'Washing machine', 'Air conditioner', 'Gas stove / oven', 'Vacuum cleaner', 'Microwave', 'Dishwasher', 'Other']],
        ],
        'TV-Audio turi' => [
            ['Тип ТВ / аудио', null, ['Телевизор / Smart TV', 'Акустика / колонка', 'Наушники', 'Музыкальный центр', 'Проектор']],
            ['TV / audio type', null, ['TV / Smart TV', 'Speakers', 'Headphones', 'Stereo system', 'Projector']],
        ],
        'Ekran diagonali' => [
            ['Диагональ экрана', null, ['до 32"', '40–43"', '50–55"', '65" и больше']],
            ['Screen size', null, ['up to 32"', '40–43"', '50–55"', '65" and larger']],
        ],
        'Texnika holati' => [
            ['Состояние техники', null, ['Новое', 'Б/у (отличное)', 'Хорошее', 'Требует ремонта']],
            ['Condition', null, ['New', 'Used (excellent)', 'Good', 'Needs repair']],
        ],
        'Mebel materiali' => [
            ['Материал мебели', null, ['МДФ', 'ЛДСП', 'Натуральное дерево', 'Металл', 'Ткань / кожа', 'Другой']],
            ['Furniture material', null, ['MDF', 'Chipboard', 'Solid wood', 'Metal', 'Fabric / leather', 'Other']],
        ],
        'Mebel holati' => [
            ['Состояние мебели', null, ['Новая (на заказ)', 'Новая (готовая)', 'Б/у (отличное)', 'Б/у (среднее)']],
            ['Furniture condition', null, ['New (made to order)', 'New (ready-made)', 'Used (excellent)', 'Used (average)']],
        ],
        'Mebel rangi' => [
            ['Цвет мебели', null, ['Коричневый (дерево)', 'Белый', 'Серый', 'Чёрный', 'Бежевый', 'Другой']],
            ['Furniture color', null, ['Brown (wood)', 'White', 'Grey', 'Black', 'Beige', 'Other']],
        ],
        'Bandlik turi' => [
            ['Тип занятости', null, ['Полная ставка', 'Неполная ставка', 'Сменный график', 'Свободный график', 'Удалённо']],
            ['Employment type', null, ['Full-time', 'Part-time', 'Shift work', 'Flexible hours', 'Remote']],
        ],
        'Ish tajribasi' => [
            ['Опыт работы', null, ['Без опыта', 'От 1 до 3 лет', 'От 3 до 5 лет', 'Более 5 лет']],
            ['Experience', null, ['No experience', '1–3 years', '3–5 years', 'Over 5 years']],
        ],
        "Maosh to'lash davriyligi" => [
            ['Периодичность выплат', null, ['Ежемесячно', 'Еженедельно', 'Ежедневно', 'По договорённости']],
            ['Pay frequency', null, ['Monthly', 'Weekly', 'Daily', 'Negotiable']],
        ],
        "Xizmat ko'rsatish joyi" => [
            ['Место оказания услуг', null, ['С выездом к клиенту', 'В мастерской / офисе', 'Удалённо (онлайн)']],
            ['Service location', null, ["At the client's address", 'In a workshop / office', 'Remote (online)']],
        ],
        'Xizmat tajribasi' => [
            ['Опыт', null, ['1–3 года', '3–5 лет', 'Специалист с опытом 5+ лет']],
            ['Experience', null, ['1–3 years', '3–5 years', 'Expert, 5+ years']],
        ],
        "To'lov shakli" => [
            ['Форма оплаты', null, ['Наличные', 'Картой (Click/Payme)', 'Перечислением', 'По договорённости']],
            ['Payment method', null, ['Cash', 'Card (Click/Payme)', 'Bank transfer', 'Negotiable']],
        ],
        'Dars formati' => [
            ['Формат занятий', null, ['Индивидуально', 'В малой группе', 'Онлайн (Zoom / Telegram)', 'Офлайн (в учебном центре)']],
            ['Lesson format', null, ['One-to-one', 'Small group', 'Online (Zoom / Telegram)', 'In person (training center)']],
        ],
        'Dars darajasi' => [
            ['Уровень', null, ['Начальный (с нуля)', 'Средний', 'Продвинутый / подготовка к сертификату']],
            ['Level', null, ['Beginner (from zero)', 'Intermediate', 'Advanced / exam preparation']],
        ],
        'Yoshi / Oyligi' => [
            ['Возраст', null, ['Молодняк', '6–12 месяцев', '1–2 года', '3 года и старше']],
            ['Age', null, ['Young', '6–12 months', '1–2 years', '3 years and older']],
        ],
        'Zoti' => [
            ['Порода', null, ['Местная', 'Гиссарская', 'Голштинская / швицкая', 'Ангус', 'Породистая / чистокровная']],
            ['Breed', null, ['Local', 'Hissar', 'Holstein / Swiss', 'Angus', 'Pedigree / purebred']],
        ],
        'Kim uchun' => [
            ['Для кого', null, ['Мужское', 'Женское', 'Детское', 'Унисекс']],
            ['For', null, ['Men', 'Women', 'Kids', 'Unisex']],
        ],
        "Kiyim o'lchami" => [
            ['Размер одежды', null, ['XS (40-42)', 'S (42-44)', 'M (46-48)', 'L (50-52)', 'XL (54-56)', 'XXL (58+)', 'Стандарт']],
            ['Clothing size', null, ['XS (40-42)', 'S (42-44)', 'M (46-48)', 'L (50-52)', 'XL (54-56)', 'XXL (58+)', 'Standard']],
        ],
        "Poyabzal o'lchami" => [['Размер обуви'], ['Shoe size']],
        'Buyum holati' => [
            ['Состояние вещи', null, ['Новое (с биркой)', 'Как новое', 'Хорошее']],
            ['Item condition', null, ['New (with tags)', 'Like new', 'Good']],
        ],
    ],
];
