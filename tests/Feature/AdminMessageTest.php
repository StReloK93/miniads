<?php

use App\Models\AdminMessage;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function messageAuthorProduct(): Product
{
    $author = User::create(['name' => 'Muallif', 'role' => 'user']);
    $category = Category::create(['name' => 'Telefonlar', 'is_page' => true]);

    return Product::create(['title' => 'iPhone 13', 'category_id' => $category->id, 'user_id' => $author->id]);
}

test('admin sends a warning about an ad and the author sees it once in the app', function () {
    $product = messageAuthorProduct();
    Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));

    $this->postJson('/api/admin/messages', [
        'product_id' => $product->id,
        'type' => 'warning',
        'body' => "Rasmlar e'longa mos emas, almashtiring.",
    ])->assertOk()->assertJsonPath('item.user_id', $product->user_id);

    $this->getJson("/api/admin/users/{$product->user_id}/messages")->assertOk()->assertJsonCount(1);

    Sanctum::actingAs($product->user);
    $unread = $this->getJson('/api/messages')
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.type', 'warning')
        ->assertJsonPath('0.product.title', 'iPhone 13')
        ->json();

    expect($unread[0]['read_at'])->toBeNull();
    $this->postJson('/api/messages/read', ['ids' => [$unread[0]['id']]])->assertNoContent();
    expect($this->getJson('/api/messages')->json('0.read_at'))->not->toBeNull();
});

test('admin can write to a user directly, without an ad', function () {
    $user = User::create(['name' => 'Foydalanuvchi', 'role' => 'user']);
    Sanctum::actingAs(User::create(['name' => 'Admin', 'role' => 'admin']));

    $this->postJson('/api/admin/messages', ['user_id' => $user->id, 'type' => 'info', 'body' => 'Salom!'])->assertOk();

    expect(AdminMessage::where('user_id', $user->id)->whereNull('product_id')->exists())->toBeTrue();
});

test('users cannot read or mark other users messages, nor send admin messages', function () {
    $product = messageAuthorProduct();
    $message = AdminMessage::create(['user_id' => $product->user_id, 'type' => 'info', 'body' => 'Maxfiy']);
    $stranger = User::create(['name' => 'Begona', 'role' => 'user']);
    Sanctum::actingAs($stranger);

    $this->getJson('/api/messages')->assertJsonCount(0);
    $this->postJson('/api/messages/read', ['ids' => [$message->id]])->assertNoContent();
    expect($message->fresh()->read_at)->toBeNull();

    $this->postJson('/api/admin/messages', ['user_id' => $product->user_id, 'type' => 'info', 'body' => 'Salom'])->assertForbidden();
});
