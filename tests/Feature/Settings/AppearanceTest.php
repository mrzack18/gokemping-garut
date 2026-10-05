<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Halaman pengaturan tampilan (ROADMAP 4.8).
 *
 * Pilihan terang/gelap/sistem disimpan di browser, jadi yang diuji di sini
 * adalah halaman dan route-nya: kedua hal itu yang dimiliki backend, sedangkan
 * perpindahan temanya sendiri sudah ditangani hook frontend.
 */
class AppearanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('appearance.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_open_the_appearance_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('appearance.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/appearance'),
            );
    }
}
