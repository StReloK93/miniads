<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductTelegramId;
use DefStudio\Telegraph\Client\TelegraphResponse;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class TelegramProductService
{
    private const MAX_CAPTION_LENGTH = 900;

    private const CHANNELS = [
        '-1003997124291',
    ];

    private const MINI_APP_URL = 'https://t.me/NavoiyElonBot';

    public function publish(Product $product): void
    {
        $this->loadProductRelations($product);

        foreach (self::CHANNELS as $channelId) {
            $this->publishToChannel($product, $channelId);
        }
    }

    public function publishToChannel(Product $product, string $channelId, ?string $botToken = null): void
    {
        $messageId = $this->sendToChannel($product, $channelId, $botToken);

        ProductTelegramId::create([
            'product_id' => $product->id,
            'channel_id' => $channelId,
            'message_id' => $messageId,
        ]);
    }

    public function synchronize(Product $product, bool $previouslyHadImage): void
    {
        $this->loadProductRelations($product);
        $hasImage = $this->getMainImage($product) !== null;

        foreach (self::CHANNELS as $channelId) {
            $telegramId = ProductTelegramId::query()
                ->where('product_id', $product->id)
                ->where('channel_id', $channelId)
                ->first();

            if (! $telegramId) {
                $this->publishToChannel($product, $channelId);

                continue;
            }

            if ($previouslyHadImage !== $hasImage) {
                $newMessageId = $this->sendToChannel($product, $channelId);
                $oldMessageId = (int) $telegramId->message_id;
                $telegramId->update(['message_id' => $newMessageId]);

                $response = Telegraph::chat($channelId)
                    ->deleteMessage($oldMessageId)
                    ->send();

                if (! $response->telegraphOk()) {
                    $description = mb_strtolower((string) $response->json('description'));
                    if (! str_contains($description, 'message to delete not found')) {
                        $this->throwTelegramError($response, $product, $channelId, "o'chirish");
                    }
                }

                continue;
            }

            $this->editChannelMessage($product, $telegramId, $channelId, $hasImage);
        }
    }

    private function makeCaption(Product $product): string
    {
        $price = $product->price === null
            ? 'Kelishiladi'
            : number_format((float) $product->price, 0, '.', ' ')
                .($product->price_type?->type ? ' '.e($product->price_type->type) : '');

        $hashtags = [$this->makeHashtag($product->district?->name, 'Navoiy')];

        if (filled($product->category?->name)) {
            $hashtags[] = $this->makeHashtag($product->category->name, "E'lon");
        }

        $title = mb_strtoupper(Str::limit($product->title, 120), 'UTF-8');
        $caption = implode(' ', $hashtags)."\n\n";
        $caption .= '<b>'.e($title)."</b>\n";
        $caption .= '<b>'.$price.'</b>';

        if ($product->description) {
            $caption .= "\n\n<i>".e(Str::limit(trim($product->description), 180)).'</i>';
        }

        $parameterLines = $product->parameter_values
            ->filter(fn ($item) => filled($item->value) && $item->parameter)
            ->map(function ($item): string {
                $title = e(Str::limit($item->parameter->title, 45));
                $value = e(Str::limit(trim((string) $item->value), 60));
                $unit = $item->parameter->unit
                    ? ' '.e(Str::limit($item->parameter->unit, 20))
                    : '';

                return $title.' - <b>'.$value.$unit.'</b>';
            });

        $footer = "\n\n📱 <code>".e($product->phone ?: "Raqam ko'rsatilmagan").'</code>';

        if ($parameterLines->isNotEmpty()) {
            $visibleLines = [];

            foreach ($parameterLines as $line) {
                $candidate = $caption."\n\n("
                    .implode(', ', [...$visibleLines, $line]).')'
                    .$footer;

                if ($this->captionLength($candidate) > self::MAX_CAPTION_LENGTH) {
                    break;
                }

                $visibleLines[] = $line;
            }

            if ($visibleLines !== []) {
                $caption .= "\n\n(".implode(', ', $visibleLines).')';
            }
        }

        $caption .= "\n\n".$footer;

        return $caption;
    }

    private function makeHashtag(?string $value, string $fallback): string
    {
        $slug = Str::slug($value ?: $fallback, '_');

        return '#'.Str::ucfirst($slug !== '' ? $slug : Str::slug($fallback, '_'));
    }

    private function editChannelMessage(
        Product $product,
        ProductTelegramId $telegramId,
        string $channelId,
        bool $hasImage,
    ): void {
        $message = Telegraph::chat($channelId);
        $caption = $this->makeCaption($product);
        $keyboard = $this->makeKeyboard($product);
        $messageId = (int) $telegramId->message_id;

        if ($hasImage) {
            $imageUrl = $this->getMainImage($product);

            if ($imageUrl === null) {
                throw new RuntimeException("Telegram uchun e'lon rasmi topilmadi.");
            }

            $media = $message->editMedia($messageId)->photo($imageUrl);
            $mediaData = json_decode($media->toArray()['payload']['media'], true, flags: JSON_THROW_ON_ERROR);
            $mediaData['caption'] = $caption;
            $mediaData['parse_mode'] = 'HTML';

            $response = $media
                ->withData('media', json_encode($mediaData, JSON_THROW_ON_ERROR))
                ->keyboard($keyboard)
                ->send();
        } else {
            $response = $message
                ->html($caption)
                ->keyboard($keyboard)
                ->edit($messageId)
                ->send();
        }

        if (! $response->telegraphOk()) {
            $description = mb_strtolower((string) $response->json('description'));

            if (str_contains($description, 'message is not modified')) {
                return;
            }

            if (str_contains($description, 'message to edit not found')) {
                $newMessageId = $this->sendToChannel($product, $channelId);
                $telegramId->update(['message_id' => $newMessageId]);

                return;
            }

            $this->throwTelegramError($response, $product, $channelId, 'yangilash');
        }
    }

    private function sendToChannel(Product $product, string $channelId, ?string $botToken = null): int
    {
        $message = $botToken
            ? Telegraph::bot($botToken)->chat($channelId)
            : Telegraph::chat($channelId);
        $imageUrl = $this->getMainImage($product);

        if ($imageUrl !== null) {
            $message = $message->photo($imageUrl);
        }

        $response = $message
            ->html($this->makeCaption($product))
            ->keyboard($this->makeKeyboard($product))
            ->send();

        $messageId = $response->telegraphMessageId();

        if ($messageId === null) {
            $this->throwTelegramError($response, $product, $channelId, 'yuborish');
        }

        return $messageId;
    }

    private function throwTelegramError(
        TelegraphResponse $response,
        Product $product,
        string $channelId,
        string $action,
    ): never
    {
        $description = $response->json('description') ?? "Telegram javobida xato sababi ko'rsatilmagan.";

        Log::error("Telegram e'lonni {$action}ni rad etdi.", [
            'product_id' => $product->id,
            'channel_id' => $channelId,
            'http_status' => $response->status(),
            'error_code' => $response->json('error_code'),
            'description' => $description,
        ]);

        throw new RuntimeException("Telegram e'lonni {$action}ni rad etdi: {$description}");
    }

    private function loadProductRelations(Product $product): void
    {
        $product->unsetRelation('images');
        $product->load([
            'images',
            'price_type',
            'district',
            'category',
            'parameter_values.parameter',
        ]);
    }

    private function captionLength(string $caption): int
    {
        $plainText = html_entity_decode(strip_tags($caption), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return mb_strlen($plainText, 'UTF-8');
    }

    private function makeKeyboard(Product $product): Keyboard
    {
        return Keyboard::make()
            ->buttons([
                Button::make('Batafsil')
                    ->url($this->makeMiniAppUrl($product)),
                Button::make("E'lon berish")
                    ->url(self::MINI_APP_URL.'?startapp=create'),
            ])
            ->chunk(2);
    }

    private function makeMiniAppUrl(Product $product): string
    {
        return self::MINI_APP_URL.'?startapp='.rawurlencode('product_'.$product->id);
    }

    private function getMainImage(Product $product): ?string
    {
        $image = $product->images->first();

        if (! $image) {
            return null;
        }

        $imagePath = $image->src;

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('public');

        if (config('filesystems.disks.public.driver') === 'local' && $disk->exists($imagePath)) {
            return $disk->path($imagePath);
        }

        return $disk->url($imagePath);
    }
}
