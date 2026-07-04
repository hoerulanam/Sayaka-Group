<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatInterfaceTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_chat_page_renders_modern_room_interface(): void
    {
        $customer = User::factory()->create(['name' => 'Customer Sayaka']);
        $admin = User::factory()->create([
            'name' => 'Admin Sayaka',
            'is_admin' => true,
        ]);

        Message::create([
            'from_user_id' => $admin->id,
            'to_user_id' => $customer->id,
            'message' => 'Halo, ada yang bisa kami bantu?',
        ]);

        $this->actingAs($customer)
            ->get(route('chat.index'))
            ->assertOk()
            ->assertSee('Ruang Chat SAYAKA')
            ->assertSee('customer-chat-panel', false)
            ->assertSee('data-chat-form', false)
            ->assertSee('Status pesanan');
    }

    public function test_admin_chat_room_renders_modern_room_interface(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin Sayaka',
            'is_admin' => true,
        ]);
        $customer = User::factory()->create(['name' => 'Customer Sayaka']);

        Message::create([
            'from_user_id' => $customer->id,
            'to_user_id' => $admin->id,
            'message' => 'Saya ingin bertanya tentang pesanan.',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.chat.show', $customer))
            ->assertOk()
            ->assertSee('admin-chat-window', false)
            ->assertSee('data-admin-chat-form', false)
            ->assertSee('Cek pesanan')
            ->assertSee('Customer Sayaka');
    }
}
