<?php

// Ilovada uchraydigan qoidalar. Laravel'ning inglizcha to'liq ro'yxati: vendor/.../Translation/lang/en/validation.php
return [
    'required' => ':attribute maydonini to\'ldiring.',
    'required_with' => ':attribute maydonini to\'ldiring.',
    'string' => ':attribute matn bo\'lishi kerak.',
    'integer' => ':attribute butun son bo\'lishi kerak.',
    'numeric' => ':attribute son bo\'lishi kerak.',
    'boolean' => ':attribute noto\'g\'ri qiymat.',
    'array' => ':attribute ro\'yxat bo\'lishi kerak.',
    'in' => ':attribute noto\'g\'ri tanlangan.',
    'exists' => ':attribute topilmadi.',
    'unique' => 'Bunday :attribute allaqachon mavjud.',
    'image' => ':attribute rasm bo\'lishi kerak.',
    'mimes' => ':attribute quyidagi formatda bo\'lishi kerak: :values.',
    'regex' => ':attribute formati noto\'g\'ri.',
    'max' => [
        'numeric' => ':attribute :max dan oshmasligi kerak.',
        'file' => ':attribute :max KB dan katta bo\'lmasligi kerak.',
        'string' => ':attribute :max belgidan oshmasligi kerak.',
        'array' => ':attribute :max tadan ko\'p bo\'lmasligi kerak.',
    ],
    'min' => [
        'numeric' => ':attribute kamida :min bo\'lishi kerak.',
        'file' => ':attribute kamida :min KB bo\'lishi kerak.',
        'string' => ':attribute kamida :min belgidan iborat bo\'lishi kerak.',
        'array' => ':attribute kamida :min ta bo\'lishi kerak.',
    ],

    'attributes' => [
        'title' => 'Sarlavha',
        'description' => 'Izoh',
        'price' => 'Narx',
        'phone' => 'Telefon',
        'category_id' => 'Bo\'lim',
        'district_id' => 'Shahar',
        'images' => 'Rasmlar',
        'images.*.file' => 'Rasm',
        'query' => 'So\'rov',
        'reason' => 'Sabab',
        'comment' => 'Izoh',
    ],
];
