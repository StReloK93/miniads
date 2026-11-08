<?php

use App\Support\Phone;

test('phones are always written as 99-999-99-99 without +998', function (?string $input, ?string $expected) {
    expect(Phone::format($input))->toBe($expected);
})->with([
    ['+998 90 123 45 67', '90-123-45-67'],
    ['998901234567', '90-123-45-67'],
    ['(93) 550-44-33', '93-550-44-33'],
    ['935504433', '93-550-44-33'],
    ['8 93 550 44 33', '93-550-44-33'],
    ['93-550-44-33', '93-550-44-33'],
    ['@muhtasham_hr', '@muhtasham_hr'],
    ['', null],
    [null, null],
]);

test('phones inside ad text are reformatted, prices are left alone', function () {
    $text = "• Narxi: 150 000 000 so'm\n• Bog'lanish uchun: +998 (90) 123 45 67, 998931112233\n• Narxi: 901234567 so'm";

    expect(Phone::formatInText($text))->toBe(
        "• Narxi: 150 000 000 so'm\n• Bog'lanish uchun: 90-123-45-67, 93-111-22-33\n• Narxi: 901234567 so'm"
    );
});
