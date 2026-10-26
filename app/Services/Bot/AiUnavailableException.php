<?php

namespace App\Services\Bot;

use RuntimeException;

/**
 * AI javob bera olmadi (xato, limit yoki kalit yo'q). Post "ko'rildi" deb belgilanmaydi —
 * AI qayta ishlaganda keyingi importda tahlil qilinadi.
 */
class AiUnavailableException extends RuntimeException
{
}
