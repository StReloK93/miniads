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

test('product deletion removes images from disk and database while soft deleting product', function () {
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->timestamps();
        $table->softDeletes();
    });
    Schema::create('product_images', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('product_id');
        $table->string('src');
        $table->string('crop_src')->nullable();
    });

    $fakeImageName = 'test_delete_img_'.uniqid().'.webp';
    $fakeImagePath = public_path('storage/products/'.$fakeImageName);
    if (!File::isDirectory(dirname($fakeImagePath))) {
        File::makeDirectory(dirname($fakeImagePath), 0755, true);
    }
    File::put($fakeImagePath, 'dummy-image-content');

    try {
        $productId = DB::table('products')->insertGetId([
            'title' => 'Product to be deleted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $imageId = DB::table('product_images')->insertGetId([
            'product_id' => $productId,
            'src' => 'products/'.$fakeImageName,
            'crop_src' => null,
        ]);

        $product = Product::withOnly('images')->find($productId);

        expect(File::exists($fakeImagePath))->toBeTrue();

        app(ProductService::class)->delete($product);

        // Product row should still exist in DB for stats, but soft-deleted (deleted_at is set)
        $rawProduct = DB::table('products')->where('id', $productId)->first();
        expect($rawProduct)->not->toBeNull()
            ->and($rawProduct->deleted_at)->not->toBeNull();

        // ProductImage row is deleted
        $rawImage = DB::table('product_images')->where('id', $imageId)->first();
        expect($rawImage)->toBeNull();

        // Physical file is deleted from disk
        expect(File::exists($fakeImagePath))->toBeFalse();
    } finally {
        if (File::exists($fakeImagePath)) {
            File::delete($fakeImagePath);
        }
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
});
