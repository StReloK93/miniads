<?php

use App\Services\ProductService;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(Tests\TestCase::class);

test('product images can store crop metadata', function () {
    Schema::create('product_images', function (Blueprint $table) {
        $table->id();
        $table->string('src');
        $table->foreignId('product_id');
    });

    try {
        $migration = require database_path('migrations/2026_10_07_000000_add_crop_metadata_to_product_images_table.php');
        $migration->up();

        expect(Schema::hasColumns('product_images', ['crop_src', 'crop_x', 'crop_y', 'crop_scale']))->toBeTrue();

        $migration->down();

        expect(Schema::hasColumns('product_images', ['crop_src', 'crop_x', 'crop_y', 'crop_scale']))->toBeFalse();
    } finally {
        Schema::dropIfExists('product_images');
    }
});

test('uploaded product image is stored as webp without upscaling', function () {
    $source = imagecreatetruecolor(1200, 675);
    $temporaryPath = tempnam(sys_get_temp_dir(), 'miniads-small-image-');
    imagepng($source, $temporaryPath);
    imagedestroy($source);
    $uploadedImage = UploadedFile::fake()->createWithContent('original.png', file_get_contents($temporaryPath));
    expect(getimagesize($uploadedImage->getRealPath())[0])->toBe(1200)
        ->and(getimagesize($uploadedImage->getRealPath())[1])->toBe(675);
    $method = new ReflectionMethod(ProductService::class, 'storeProductImage');
    $path = $method->invoke(new ProductService, $uploadedImage);
    $storedPath = public_path('storage/'.$path);

    try {
        $imageInfo = getimagesize($storedPath);

        expect(pathinfo($storedPath, PATHINFO_EXTENSION))->toBe('webp')
            ->and($imageInfo[0])->toBe(1024)
            ->and($imageInfo[1])->toBe(576)
            ->and($imageInfo['mime'])->toBe('image/webp');
    } finally {
        File::delete($temporaryPath);
        File::delete($storedPath);
    }
});

test('large uploaded product images are scaled down within the storage limit', function () {
    $source = imagecreatetruecolor(3000, 2000);
    $temporaryPath = tempnam(sys_get_temp_dir(), 'miniads-large-image-');
    imagepng($source, $temporaryPath);
    imagedestroy($source);
    $uploadedImage = UploadedFile::fake()->createWithContent('large.png', file_get_contents($temporaryPath));
    expect(getimagesize($uploadedImage->getRealPath())[0])->toBe(3000)
        ->and(getimagesize($uploadedImage->getRealPath())[1])->toBe(2000);
    $method = new ReflectionMethod(ProductService::class, 'storeProductImage');
    $path = $method->invoke(new ProductService, $uploadedImage);
    $storedPath = public_path('storage/'.$path);

    try {
        $imageInfo = getimagesize($storedPath);

        expect($imageInfo[0])->toBe(1024)
            ->and($imageInfo[1])->toBe(683)
            ->and(max($imageInfo[0], $imageInfo[1]))->toBeLessThanOrEqual(1024)
            ->and($imageInfo['mime'])->toBe('image/webp');
    } finally {
        File::delete($temporaryPath);
        File::delete($storedPath);
    }
});

test('saving an existing image crop only updates metadata and clears its legacy crop file', function () {
    Schema::create('product_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id');
        $table->string('src');
        $table->string('crop_src')->nullable();
        $table->unsignedTinyInteger('crop_x')->default(50);
        $table->unsignedTinyInteger('crop_y')->default(50);
        $table->decimal('crop_scale', 4, 2)->default(1);
    });

    $directory = 'products/crop-update-tests/'.Str::uuid();
    $sourceRelativePath = "{$directory}/source.png";
    $oldCropRelativePath = "{$directory}/old-crop.webp";
    $sourcePath = public_path('storage/'.$sourceRelativePath);
    $oldCropPath = public_path('storage/'.$oldCropRelativePath);
    File::ensureDirectoryExists(dirname($sourcePath));

    $source = imagecreatetruecolor(1600, 900);
    imagepng($source, $sourcePath);
    imagedestroy($source);
    File::put($oldCropPath, 'old crop');

    try {
        $image = ProductImage::create([
            'product_id' => 1,
            'src' => $sourceRelativePath,
            'crop_src' => $oldCropRelativePath,
            'crop_x' => 50,
            'crop_y' => 50,
            'crop_scale' => 1,
        ]);
        $product = new Product;
        $product->setAttribute('id', 1);

        $method = new ReflectionMethod(ProductService::class, 'syncProductImages');
        $method->invoke(
            new ProductService,
            $product,
            [[
                'id' => $image->id,
                'crop_x' => 50,
                'crop_y' => 50,
                'crop_scale' => 1,
                'crop_changed' => true,
            ]],
        );

        $image->refresh();

        expect($image->crop_src)->toBeNull()
            ->and($image->crop_x)->toBe(50)
            ->and($image->crop_y)->toBe(50)
            ->and($image->crop_scale)->toBe(1.0)
            ->and(File::exists($oldCropPath))->toBeFalse();
    } finally {
        File::deleteDirectory(public_path('storage/'.$directory));
        Schema::dropIfExists('product_images');
    }
});

test('adding an image stores its original and crop metadata without a derivative file', function () {
    Schema::create('product_images', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id');
        $table->string('src');
        $table->string('crop_src')->nullable();
        $table->unsignedTinyInteger('crop_x')->default(50);
        $table->unsignedTinyInteger('crop_y')->default(50);
        $table->decimal('crop_scale', 4, 2)->default(1);
    });

    $product = new Product;
    $product->setAttribute('id', 1);
    $product->exists = true;
    $productsDirectory = public_path('storage/products');
    $existingProductFiles = File::exists($productsDirectory) ? count(File::files($productsDirectory)) : 0;

    try {
        $method = new ReflectionMethod(ProductService::class, 'syncProductImages');
        $method->invoke(
            new ProductService,
            $product,
            [[
                'file' => UploadedFile::fake()->image('original.png', 1200, 675),
                'crop_x' => 70,
                'crop_y' => 25,
                'crop_scale' => 1.5,
            ]],
        );

        $image = ProductImage::firstOrFail();

        expect($image->crop_src)->toBeNull()
            ->and($image->crop_x)->toBe(70)
            ->and($image->crop_y)->toBe(25)
            ->and($image->crop_scale)->toBe(1.5)
            ->and(File::exists(public_path('storage/'.$image->src)))->toBeTrue()
            ->and(count(File::files($productsDirectory)))->toBe($existingProductFiles + 1);
    } finally {
        foreach (ProductImage::all() as $image) {
            File::delete(public_path('storage/'.$image->src));
        }

        Schema::dropIfExists('product_images');
    }
});
