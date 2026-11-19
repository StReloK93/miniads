<?php

namespace App\Services;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use RuntimeException;

/**
 * Admin paneldan kanal(lar)ga ixtiyoriy post yuborish: tayyor Telegram HTML (TelegramMarkdown), ixtiyoriy rasm va tugma.
 */
class ChannelPostService
{
    // Telegram cheklovlariga sig'ishi uchun (eni + bo'yi <= 10000px, 10MB)
    private const MAX_IMAGE_DIMENSION = 2048;

    /**
     * @return array<string, int> [kanal_id => xabar_id]
     */
    public function send(string $html, ?string $photoPath = null, ?string $buttonText = null, ?string $buttonUrl = null): array
    {
        $keyboard = filled($buttonText) && filled($buttonUrl)
            ? Keyboard::make()->buttons([Button::make($buttonText)->url($buttonUrl)->style('success')])
            : null;

        $sent = [];

        foreach (TelegramProductService::channels() as $channelId) {
            $message = Telegraph::chat($channelId);
            if ($photoPath) {
                $message = $message->photo($photoPath, 'post.jpg');
            }
            $message = $message->html($html);
            if ($keyboard) {
                $message = $message->keyboard($keyboard);
            }

            $response = $message->send();

            if (! $response->telegraphOk()) {
                $description = (string) ($response->json('description') ?: "Telegram javob bermadi");
                Log::error('Kanalga post yuborilmadi', ['channel_id' => $channelId, 'description' => $description]);
                throw new RuntimeException($description);
            }

            $sent[$channelId] = (int) $response->telegraphMessageId();
        }

        return $sent;
    }

    /** Rasm Telegram cheklovlariga moslab JPEG qilinadi (vaqtinchalik fayl — chaqiruvchi o'chiradi) */
    public function preparePhoto(UploadedFile $photo): string
    {
        $path = storage_path('app/tmp/' . Str::uuid() . '.jpg');
        File::ensureDirectoryExists(dirname($path));

        ImageManager::usingDriver(Driver::class)
            ->decode($photo->get())
            ->scaleDown(self::MAX_IMAGE_DIMENSION, self::MAX_IMAGE_DIMENSION)
            ->encode(new JpegEncoder(quality: 85))
            ->save($path);

        return $path;
    }
}
