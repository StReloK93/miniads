<?php

use App\Jobs\DeleteTelegramMessage;
use App\Models\TelegramGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

const GATE_GROUP_ID = -100500;

/**
 * Telegram javoblari: getChatMember kanal va guruh uchun alohida holat qaytaradi.
 * Http::fake bir marta o'rnatiladi; holat keyingi chaqiruvlarda shu o'zgaruvchi orqali almashadi.
 */
function fakeGateTelegram(string $channelStatus = 'left', string $groupStatus = 'member'): void
{
    $GLOBALS['gateStatus'] = [$channelStatus, $groupStatus];
    if (! ($GLOBALS['gateFaked'] ?? false)) {
        Http::fake(function (Request $request) {
            [$channel, $group] = $GLOBALS['gateStatus'];
            if (str_ends_with($request->url(), '/getChatMember')) {
                $isChannel = (string) $request->data()['chat_id'] === '@navoiy_elonlaruz';

                return Http::response(['ok' => true, 'result' => ['status' => $isChannel ? $channel : $group]]);
            }

            return Http::response(['ok' => true, 'result' => ['message_id' => 77]]);
        });
        $GLOBALS['gateFaked'] = true;
    }
    $GLOBALS['gateFrom'] = Http::recorded()->count();
}

function gateGroupMessage(array $from = [], array $extra = []): array
{
    return ['message' => array_merge([
        'message_id' => 10,
        'text' => 'Sotaman',
        'chat' => ['id' => GATE_GROUP_ID, 'type' => 'supergroup', 'title' => 'Navoiy bozor'],
        'from' => array_merge(['id' => 42, 'first_name' => 'Ali', 'language_code' => 'uz'], $from),
    ], $extra)];
}

function gateSent(string $method)
{
    return Http::recorded()->slice($GLOBALS['gateFrom'] ?? 0)->map(fn ($pair) => $pair[0])->filter(fn (Request $r) => str_ends_with($r->url(), "/{$method}"))->values();
}

beforeEach(function () {
    config(['services.telegram.channel_url' => 'https://t.me/navoiy_elonlaruz', 'cache.default' => 'array']);
    Queue::fake();
    $GLOBALS['gateFaked'] = false;
    $GLOBALS['gateFrom'] = 0;
});

test('the group is registered only when an app admin adds the bot; otherwise the bot leaves', function () {
    fakeGateTelegram();
    User::forceCreate(['name' => 'Admin', 'telegram_user_id' => 900, 'role' => 'admin']);
    $update = fn (int $from) => ['my_chat_member' => [
        'chat' => ['id' => GATE_GROUP_ID, 'type' => 'supergroup', 'title' => 'Navoiy bozor'],
        'from' => ['id' => $from],
        'new_chat_member' => ['status' => 'administrator'],
    ]];

    $this->postJson('/api/telegram/webhook', $update(123))->assertOk();
    expect(TelegramGroup::count())->toBe(0)->and(gateSent('leaveChat'))->toHaveCount(1);

    $this->postJson('/api/telegram/webhook', $update(900))->assertOk();
    expect(TelegramGroup::where('chat_id', GATE_GROUP_ID)->value('title'))->toBe('Navoiy bozor');
});

test('a non-subscriber: message deleted, muted for ~30s, one short warning that disappears', function () {
    fakeGateTelegram('left');
    TelegramGroup::create(['chat_id' => GATE_GROUP_ID]);

    $this->travelTo(now()->startOfMinute());
    $this->postJson('/api/telegram/webhook', gateGroupMessage())->assertOk();
    $this->postJson('/api/telegram/webhook', gateGroupMessage(extra: ['message_id' => 11]))->assertOk();

    expect(gateSent('deleteMessage'))->toHaveCount(2);

    $mute = gateSent('restrictChatMember')[0]->data();
    expect((string) $mute['chat_id'])->toBe((string) GATE_GROUP_ID)
        ->and((string) $mute['user_id'])->toBe('42')
        ->and($mute['permissions']['can_send_messages'])->toBeFalse()
        ->and($mute['until_date'])->toBe(now()->addSeconds(35)->timestamp);

    $warnings = gateSent('sendMessage');
    expect($warnings)->toHaveCount(1);
    $warning = $warnings[0]->data();
    expect($warning['text'])->toBe("<a href=\"tg://user?id=42\">Ali</a>, guruhda yozish uchun @navoiy_elonlaruz kanaliga a'zo bo'ling.")
        ->and($warning)->not->toHaveKey('reply_markup');

    Queue::assertPushed(DeleteTelegramMessage::class, fn ($job) => $job->messageId === 77);
});

test('subscribers, group admins, channel posts and untracked groups are left alone', function () {
    TelegramGroup::create(['chat_id' => GATE_GROUP_ID]);

    fakeGateTelegram('member');
    $this->postJson('/api/telegram/webhook', gateGroupMessage())->assertOk();
    expect(gateSent('deleteMessage'))->toHaveCount(0);

    fakeGateTelegram('left', 'administrator');
    $this->postJson('/api/telegram/webhook', gateGroupMessage(['id' => 43]))->assertOk();
    expect(gateSent('deleteMessage'))->toHaveCount(0);

    fakeGateTelegram('left');
    $this->postJson('/api/telegram/webhook', gateGroupMessage(['id' => 44], ['sender_chat' => ['id' => -1001]]))->assertOk();
    $other = gateGroupMessage(['id' => 45]);
    $other['message']['chat']['id'] = -999;
    $this->postJson('/api/telegram/webhook', $other)->assertOk();
    expect(gateSent('deleteMessage'))->toHaveCount(0)->and(gateSent('sendMessage'))->toHaveCount(0);
});
