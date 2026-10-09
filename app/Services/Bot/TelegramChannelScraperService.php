<?php

namespace App\Services\Bot;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannelScraperService
{
    /**
     * Fetch the latest public posts from a Telegram channel.
     *
     * @param string $channel e.g. "navoiy_bozor" or "@navoiy_bozor"
     * @param int $limit Maximum number of posts to return
     * @return array List of array items: ['channel', 'message_id', 'text', 'images', 'date']
     */
    public function fetchChannelPosts(string $channel, int $limit = 20): array
    {
        $cleanChannel = ltrim($channel, '@');
        $cleanChannel = preg_replace('#^https?://t\.me/#i', '', $cleanChannel);

        if (empty($cleanChannel)) {
            return [];
        }

        $url = "https://t.me/s/{$cleanChannel}";

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept-Language' => 'uz,ru;q=0.9,en;q=0.8',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning("Telegram kanalni yuklab bo'lmadi: {$cleanChannel}, status: " . $response->status());
                return [];
            }

            return $this->parseHtml($response->body(), $cleanChannel, $limit);
        } catch (\Throwable $e) {
            Log::error("Telegram kanalni skrap qilishda xatolik: {$cleanChannel}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Parse the Telegram web preview HTML.
     */
    public function parseHtml(string $html, string $channel, int $limit = 20): array
    {
        $posts = [];

        // Split HTML by message blocks
        $blocks = preg_split('/<div[^>]+class="[^"]*tgme_widget_message\b/i', $html);

        // Remove the header before the first message
        array_shift($blocks);

        foreach ($blocks as $block) {
            // Extract message ID
            if (!preg_match('/data-post="[^"]*\/(\d+)"/i', $block, $idMatch)) {
                continue;
            }
            $messageId = (int) $idMatch[1];

            // Extract text
            $text = '';
            if (preg_match('/<div[^>]+class="[^"]*tgme_widget_message_text[^"]*"[^>]*>(.*?)<\/div>/is', $block, $textMatch)) {
                $rawText = $textMatch[1];
                // Replace <br> and <br/> with newline
                $rawText = preg_replace('/<br\s*\/?>/i', "\n", $rawText);
                // Strip tags and decode entities
                $text = trim(html_entity_decode(strip_tags($rawText), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            }

            // Extract actual post images from photo wraps (never channel/user profile avatars)
            $images = [];
            if (preg_match_all('/class="[^"]*tgme_widget_message_photo_wrap[^"]*"[^>]*style="[^"]*background-image:\s*url\([\'"]?([^\'")\s]+)[\'"]?\)/i', $block, $photoMatches)) {
                foreach ($photoMatches[1] as $imgUrl) {
                    if (str_starts_with($imgUrl, 'http') && !str_contains($imgUrl, 'emoji') && !in_array($imgUrl, $images, true)) {
                        $images[] = $imgUrl;
                    }
                }
            }

            // Also check for video preview thumbnails if any
            if (preg_match_all('/class="[^"]*tgme_widget_message_video_thumb[^"]*"[^>]*style="[^"]*background-image:\s*url\([\'"]?([^\'")\s]+)[\'"]?\)/i', $block, $videoMatches)) {
                foreach ($videoMatches[1] as $imgUrl) {
                    if (str_starts_with($imgUrl, 'http') && !str_contains($imgUrl, 'emoji') && !in_array($imgUrl, $images, true)) {
                        $images[] = $imgUrl;
                    }
                }
            }

            // If empty text and no images, skip
            if (empty($text) && empty($images)) {
                continue;
            }

            // Extract post date
            $date = null;
            if (preg_match('/<time[^>]+datetime="([^"]+)"/i', $block, $dateMatch)) {
                $date = $dateMatch[1];
            }

            $posts[] = [
                'channel' => $channel,
                'message_id' => $messageId,
                'text' => $text,
                'images' => array_values(array_unique($images)),
                'date' => $date,
            ];
        }

        // Return latest posts (up to limit)
        return array_slice(array_reverse($posts), 0, $limit);
    }
}
