<?php

namespace App\Console\Commands;

use App\Models\ProductImage;
use App\Support\ProductThumbnail;
use Illuminate\Console\Command;

class GenerateProductThumbnails extends Command
{
    protected $signature = 'products:thumbnails {--all : Bor kichik nusxalarni ham qayta yasash}';

    protected $description = "E'lon rasmlari uchun ro'yxat kartalaridagi kichik nusxalarni yasaydi";

    public function handle(): int
    {
        $query = ProductImage::query()->when(!$this->option('all'), fn ($q) => $q->whereNull('thumb_src'));
        $total = $query->count();
        $made = 0;

        $this->withProgressBar($query->lazyById(200), function (ProductImage $image) use (&$made): void {
            $thumb = ProductThumbnail::make($image->src);
            if ($thumb) {
                $image->update(['thumb_src' => $thumb]);
                $made++;
            }
        });

        $this->newLine();
        $this->info("{$made} / {$total} ta kichik rasm yasaldi.");

        return self::SUCCESS;
    }
}
