<?php

use App\Models\Product;
use App\Models\User;
use App\Services\TelegramProductService;

uses(Tests\TestCase::class);

function captionFor(?string $phone, ?string $ownerUsername = null): string
{
    $product = new Product(['title' => 'Lacetti 2012', 'phone' => $phone]);
    $product->setRelation('parameter_values', collect());
    $product->setRelation('user', new User(['username' => $ownerUsername]));

    $method = new ReflectionMethod(TelegramProductService::class, 'makeCaption');

    return $method->invoke(new TelegramProductService, $product);
}

test('a nick instead of a phone becomes a clickable mention, not a phone number', function () {
    expect(captionFor('@muhtasham_hr'))
        ->toContain('💬 @muhtasham_hr')
        ->not->toContain('<code>@muhtasham_hr</code>');
});

test('a phone number stays copyable', function () {
    expect(captionFor('998901234567'))->toContain('📱 <code>998901234567</code>');
});

test("without a phone the owner's username is shown", function () {
    expect(captionFor(null, 'seller'))->toContain('💬 @seller');
});
