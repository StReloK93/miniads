<?php

return [
    'required' => 'Заполните поле «:attribute».',
    'required_with' => 'Заполните поле «:attribute».',
    'string' => 'Поле «:attribute» должно быть текстом.',
    'integer' => 'Поле «:attribute» должно быть целым числом.',
    'numeric' => 'Поле «:attribute» должно быть числом.',
    'boolean' => 'Неверное значение поля «:attribute».',
    'array' => 'Поле «:attribute» должно быть списком.',
    'in' => 'Выбрано неверное значение поля «:attribute».',
    'exists' => 'Значение поля «:attribute» не найдено.',
    'unique' => 'Такое значение поля «:attribute» уже существует.',
    'image' => 'Поле «:attribute» должно быть изображением.',
    'mimes' => 'Поле «:attribute» должно быть в формате: :values.',
    'regex' => 'Неверный формат поля «:attribute».',
    'max' => [
        'numeric' => 'Поле «:attribute» не может быть больше :max.',
        'file' => 'Файл «:attribute» не может быть больше :max КБ.',
        'string' => 'Поле «:attribute» не может быть длиннее :max символов.',
        'array' => 'В поле «:attribute» не может быть больше :max элементов.',
    ],
    'min' => [
        'numeric' => 'Поле «:attribute» должно быть не меньше :min.',
        'file' => 'Файл «:attribute» должен быть не меньше :min КБ.',
        'string' => 'Поле «:attribute» должно содержать не менее :min символов.',
        'array' => 'В поле «:attribute» должно быть не менее :min элементов.',
    ],

    'attributes' => [
        'title' => 'Заголовок',
        'description' => 'Описание',
        'price' => 'Цена',
        'phone' => 'Телефон',
        'category_id' => 'Рубрика',
        'district_id' => 'Город',
        'images' => 'Фото',
        'images.*.file' => 'Фото',
        'query' => 'Запрос',
        'reason' => 'Причина',
        'comment' => 'Комментарий',
    ],
];
