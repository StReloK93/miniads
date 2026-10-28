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
     * @param int $limit Maximum number of posts to return (e.g. 35)
     * @return array List of array items: ['channel', 'message_id', 'text', 'images', 'date']
     */
    public function fetchChannelPosts(string $channel, int $limit = 50): array
    {
        $cleanChannel = ltrim($channel, '@');
        $cleanChannel = preg_replace('#^https?://t\.me/#i', '', $cleanChannel);

        if (empty($cleanChannel)) {
            return [];
        }

        try {
            $posts = [];
            $url = "https://t.me/s/{$cleanChannel}";
            $maxPages = (int) min(6, ceil($limit / 18));
            $page = 0;

            while ($page < $maxPages && count($posts) < $limit) {
                $page++;
                $html = $this->fetchUrl($url);
                if (empty($html)) {
                    break;
                }

                $pagePosts = $this->parseHtml($html, $cleanChannel, 25);
                if (empty($pagePosts)) {
                    break;
                }

                $existingIds = array_column($posts, 'message_id');
                $newAdded = 0;
                foreach ($pagePosts as $p) {
                    if (!in_array($p['message_id'], $existingIds, true)) {
                        $posts[] = $p;
                        $newAdded++;
                    }
                }

                if ($newAdded === 0) {
                    break;
                }

                $minId = min(array_column($posts, 'message_id'));
                if ($minId <= 1) {
                    break;
                }
                $url = "https://t.me/s/{$cleanChannel}?before={$minId}";
            }

            // Sort by message_id descending (newest first)
            usort($posts, fn($a, $b) => $b['message_id'] <=> $a['message_id']);

            return array_slice($posts, 0, $limit);
        } catch (\Throwable $e) {
            Log::error("Telegram kanalni skrap qilishda xatolik: {$cleanChannel}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Post havolasidan kanal va xabar raqami: t.me/kanal/123, t.me/s/kanal/123, telegram.me/kanal/123?single.
     * Yopiq kanal (t.me/c/...) va guruh havolalarini o'qib bo'lmaydi — null.
     *
     * @return array{0: string, 1: int}|null
     */
    public static function parsePostUrl(string $url): ?array
    {
        $url = trim($url);
        if (!preg_match('~^(?:https?://)?(?:www\.)?(?:t|telegram)\.me/(?:s/)?([A-Za-z][A-Za-z0-9_]{3,})/(\d+)(?:[/?#].*)?$~i', $url, $m)) {
            return null;
        }
        if (strtolower($m[1]) === 'c') {
            return null;
        }

        return [$m[1], (int) $m[2]];
    }

    /**
     * Ochiq kanaldagi bitta post (embed sahifasi orqali; rasmlari va albomi bilan).
     */
    public function fetchPost(string $channel, int $messageId): ?array
    {
        $html = $this->fetchUrl("https://t.me/{$channel}/{$messageId}?embed=1&mode=tme");
        if (empty($html)) {
            return null;
        }

        foreach ($this->parseHtml($html, $channel, 5) as $post) {
            if ((int) $post['message_id'] === $messageId) {
                return $post;
            }
        }

        return null;
    }

    /**
     * Fetch HTML with timeout, headers and proxy if configured.
     */
    protected function fetchUrl(string $url): ?string
    {
        try {
            $options = [
                'curl' => [
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
                'verify' => false,
            ];

            $proxy = env('TELEGRAM_PROXY') ?: env('HTTPS_PROXY') ?: env('HTTP_PROXY');
            if (!empty($proxy)) {
                $options['proxy'] = $proxy;
            }

            $response = Http::timeout(20)
                ->withOptions($options)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Accept-Language' => 'uz,ru;q=0.9,en;q=0.8',
                ])
                ->get($url);

            if (!$response->successful()) {
                Log::warning("Telegram sahifani yuklab bo'lmadi: {$url}, status: " . $response->status());
                return null;
            }

            return $response->body();
        } catch (\Throwable $e) {
            Log::warning("Telegram URL yuklashda xatolik: {$url}: " . $e->getMessage());
            return null;
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
