<?php

use App\Models\Category;
use App\Models\Product;
use App\Services\TelegramProductService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('telegram publish integration sends a message to the dedicated test chat', function () {
    if (! filter_var(env('TELEGRAM_INTEGRATION_TEST', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('Set TELEGRAM_INTEGRATION_TEST=true to send a real Telegram message.');
    }

    $botToken = env('TELEGRAM_BOT_TOKEN');
    $chatId = env('TELEGRAM_TEST_CHAT_ID');

    expect($botToken)->not->toBeEmpty()
        ->and($chatId)->not->toBeEmpty()
        ->and(preg_match('/^-?\d+$/', (string) $chatId))->toBe(1)
        ->and($chatId)->not->toBe('-1003997124291');

    Schema::create('product_telegram_ids', function (Blueprint $table) {
        $table->id();
        $table->bigInteger('product_id');
        $table->bigInteger('channel_id');
        $table->bigInteger('message_id');
    });

    try {
        $product = new Product([
            'title' => 'TELEGRAM INTEGRATION TEST',
            'description' => 'Telegram e’lon yuborish integratsiya testi.',
            'price' => 1000,
            'phone' => '0000000000',
        ]);
        $product->setAttribute('id', random_int(1, PHP_INT_MAX));
        $product->setRelation('images', new Illuminate\Database\Eloquent\Collection);
        $product->setRelation('price_type', null);
        $product->setRelation('district', null);
        $product->setRelation('category', new Category);
        $product->setRelation('parameter_values', new Illuminate\Database\Eloquent\Collection);

        app(TelegramProductService::class)->publishToChannel($product, $chatId, $botToken);

        expect(DB::table('product_telegram_ids')->count())->toBe(1);
    } finally {
        Schema::dropIfExists('product_telegram_ids');
    }
});
