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
 * Admin paneldan kanal(lar)ga ixtiyoriy post yuborish: matn, ixtiyoriy rasm va ixtiyoriy tugma.
 */
class ChannelPostService
{
    // Telegram cheklovlariga sig'ishi uchun (eni + bo'yi <= 10000px, 10MB)
    private const MAX_IMAGE_DIMENSION = 2048;

    /**
     * @return array<string, int> [kanal_id => xabar_id]
     */
    public function send(string $text, ?UploadedFile $photo = null, ?string $buttonText = null, ?string $buttonUrl = null): array
    {
        $html = nl2br(e(trim($text)), false);
        // nl2br <br> qo'shadi, Telegram HTML esa uni tanimaydi — oddiy qator tashlashni qoldiramiz
        $html = str_replace('<br>', '', $html);

        $keyboard = filled($buttonText) && filled($buttonUrl)
            ? Keyboard::make()->buttons([Button::make($buttonText)->url($buttonUrl)])
            : null;

        $photoPath = $photo ? $this->preparePhoto($photo) : null;
        $sent = [];

        try {
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
        } finally {
            if ($photoPath) {
                File::delete($photoPath);
            }
        }

        return $sent;
    }

    private function preparePhoto(UploadedFile $photo): string
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
