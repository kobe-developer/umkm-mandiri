<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\ShopFixtures;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase, ShopFixtures;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpShop();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/pos')->assertRedirect('/login');
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_login_redirects_by_role_and_rejects_inactive_users(): void
    {
        $owner = User::factory()->owner()->create(['username' => 'bos']);
        $cashier = User::factory()->create(['username' => 'kasir1']);
        User::factory()->inactive()->create(['username' => 'mantan']);

        $this->post('/login', ['username' => 'bos', 'password' => 'password'])->assertRedirect('/admin');
        $this->post('/logout');
        $this->post('/login', ['username' => 'kasir1', 'password' => 'password'])->assertRedirect('/pos');
        $this->post('/logout');
        $this->post('/login', ['username' => 'mantan', 'password' => 'password'])->assertSessionHasErrors('username');
        $this->post('/login', ['username' => 'kasir1', 'password' => 'salah'])->assertSessionHasErrors('username');

        $this->assertNotNull($owner->fresh()->last_login_at);
        $this->assertNotNull($cashier->fresh()->last_login_at);
    }

    public function test_cashier_cannot_access_back_office(): void
    {
        $cashier = $this->cashier();

        foreach (['/admin', '/admin/produk', '/admin/user', '/admin/toko', '/admin/log', '/admin/transaksi/export/xlsx'] as $url) {
            $this->actingAs($cashier)->get($url)->assertForbidden();
        }
        $this->actingAs($cashier)->put('/admin/toko/status', ['is_open' => true])->assertForbidden();
        $this->actingAs($cashier)->put('/akun/pin', ['pin' => '1234'])->assertForbidden();
    }

    public function test_cashier_and_owner_can_access_pos(): void
    {
        foreach ([$this->cashier(), $this->owner()] as $user) {
            $this->actingAs($user)->get('/pos')->assertOk();
            $this->actingAs($user)->get('/pos/online')->assertOk();
            $this->actingAs($user)->get('/pos/produk')->assertOk();
        }
    }

    public function test_deactivated_user_is_logged_out_on_next_request(): void
    {
        $cashier = $this->cashier();
        $this->actingAs($cashier)->get('/pos')->assertOk();

        $cashier->update(['is_active' => false]);

        $this->get('/pos')->assertRedirect('/login');
        $this->assertGuest();
    }
}
