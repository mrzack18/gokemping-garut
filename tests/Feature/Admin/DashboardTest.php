<?php

namespace Tests\Feature\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Business;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_admin_can_visit_the_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/dashboard')
                ->where('business.name', $user->business->name)
                ->where('business.slug', $user->business->slug)
                ->where('business.bookingCodePrefix', $user->business->booking_code_prefix)
                ->has('stats')
            );
    }

    public function test_dashboard_stats_only_count_the_owning_business(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $category = Category::factory()->forBusiness($business)->create();
        $otherCategory = Category::factory()->forBusiness($otherBusiness)->create();

        Product::factory()
            ->forBusiness($business)
            ->withCategory($category)
            ->create();

        Product::factory()
            ->forBusiness($otherBusiness)
            ->withCategory($otherCategory)
            ->create();

        // Produk nonaktif tidak boleh dihitung.
        Product::factory()->forBusiness($business)->inactive()->create();

        $booking = Booking::factory()
            ->forBusiness($business)
            ->state(['booking_status' => BookingStatus::SedangDisewa])
            ->create();

        Booking::factory()
            ->forBusiness($otherBusiness)
            ->state(['booking_status' => BookingStatus::SedangDisewa])
            ->create();

        Payment::factory()
            ->forBusiness($business)
            ->state([
                'booking_id' => $booking->id,
                'method' => PaymentMethodType::Qris,
                'status' => PaymentStatus::MenungguVerifikasi,
            ])
            ->create();

        Payment::factory()
            ->forBusiness($otherBusiness)
            ->state([
                'method' => PaymentMethodType::Qris,
                'status' => PaymentStatus::MenungguVerifikasi,
            ])
            ->create();

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('stats.totalProducts', 1)
                ->where('stats.bookingsToday', 1)
                ->where('stats.rented', 1)
                ->where('stats.awaitingConfirmation', 0)
                ->where('stats.pendingPayments', 1)
            );
    }

    public function test_dashboard_uses_the_business_of_the_logged_in_admin(): void
    {
        $business = Business::factory()->create();
        $otherBusiness = Business::factory()->create();

        $this->actingAs(User::factory()->forBusiness($business)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('business.slug', $business->slug)
                ->where('business.name', $business->name)
                ->etc()
            );

        $this->actingAs(User::factory()->forBusiness($otherBusiness)->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('business.slug', $otherBusiness->slug)
                ->where('business.name', $otherBusiness->name)
                ->etc()
            );
    }
}
