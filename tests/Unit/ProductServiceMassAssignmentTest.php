<?php

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(Tests\TestCase::class);

test('product update ignores client supplied system fields', function () {
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->unsignedBigInteger('user_id');
        $table->timestamp('expires_at')->nullable();
        $table->unsignedInteger('views_count')->default(0);
        $table->unsignedBigInteger('district_id')->nullable();
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('product_images', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('product_id');
    });

    try {
        $id = DB::table('products')->insertGetId([
            'title' => 'Original title',
            'user_id' => 10,
            'expires_at' => '2026-10-10 00:00:00',
            'views_count' => 4,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $product = new Product;
        $product->setRawAttributes([
            'id' => $id,
            'title' => 'Original title',
            'user_id' => 10,
            'expires_at' => '2026-10-10 00:00:00',
            'views_count' => 4,
        ], true);
        $product->exists = true;

        app(ProductService::class)->update(
            Request::create('/api/products/'.$id, 'POST', [
                'title' => 'Updated title',
                'user_id' => 99,
                'expires_at' => '2036-10-10 00:00:00',
                'views_count' => 9000,
                'images' => [],
                'parameters' => [],
            ]),
            $product,
        );

        $savedProduct = DB::table('products')->where('id', $id)->first();

        expect($savedProduct->title)->toBe('Updated title')
            ->and((int) $savedProduct->user_id)->toBe(10)
            ->and((int) $savedProduct->views_count)->toBe(4)
            ->and($savedProduct->expires_at)->toBe('2026-10-10 00:00:00');
    } finally {
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
});
