<?php

use App\Support\TelegramMarkdown;

test('telegram-style marks become telegram html', function () {
    expect(TelegramMarkdown::toHtml('**Qalin** __qiya__ ~~eski~~ ||sir|| `kod`'))
        ->toBe('<b>Qalin</b> <i>qiya</i> <s>eski</s> <tg-spoiler>sir</tg-spoiler> <code>kod</code>');
});

test('links keep underscores in the url and allow formatting in the label', function () {
    expect(TelegramMarkdown::toHtml('[**E\'lon joylash**](https://t.me/my__bot?startapp=create)'))
        ->toBe('<a href="https://t.me/my__bot?startapp=create"><b>E\'lon joylash</b></a>');
});

test('plain html characters are escaped and code is not formatted', function () {
    expect(TelegramMarkdown::toHtml("1 < 2 & <b>x</b>\n`**a**`"))
        ->toBe("1 &lt; 2 &amp; &lt;b&gt;x&lt;/b&gt;\n<code>**a**</code>");
});

test('consecutive quote lines become one blockquote, long ones are expandable', function () {
    expect(TelegramMarkdown::toHtml("Sarlavha\n> bir\n> ikki\nOxiri"))
        ->toBe("Sarlavha\n<blockquote>bir\nikki</blockquote>\nOxiri");

    expect(TelegramMarkdown::toHtml(implode("\n", array_fill(0, 5, '> qator'))))
        ->toStartWith('<blockquote expandable>');
});

test('visible length ignores tags', function () {
    expect(TelegramMarkdown::visibleLength(TelegramMarkdown::toHtml('**ab** & [c](https://x.uz)')))->toBe(6);
});
