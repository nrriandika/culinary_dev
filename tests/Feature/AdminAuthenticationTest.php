<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\ContentMapWidget;
use App\Filament\Widgets\ContentStatsOverview;
use App\Models\User;
use Database\Seeders\SampleContentSeeder;
use Filament\Auth\Pages\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
    }

    public function test_root_redirects_to_admin(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('WebGIS Kuliner Nusantara');
    }

    public function test_active_admin_can_login_and_last_login_is_recorded(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'rahasia123'])
            ->call('authenticate')
            ->assertHasNoFormErrors()
            ->assertRedirect(Dashboard::getUrl());

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123']);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'salah'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_inactive_admin_cannot_login(): void
    {
        $user = User::factory()->create(['password' => 'rahasia123', 'is_active' => false]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $user->email, 'password' => 'rahasia123'])
            ->call('authenticate')
            ->assertHasFormErrors(['email']);

        $this->assertGuest();
    }

    public function test_inactive_admin_with_existing_session_is_forbidden(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)->get('/admin')->assertForbidden();
    }

    public function test_dashboard_shows_summary_and_map(): void
    {
        $this->seed(SampleContentSeeder::class);
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertOk()->assertSee('Dashboard');

        Livewire::actingAs($user)->test(ContentStatsOverview::class)
            ->assertSee('Kuliner')
            ->assertSee('Destinasi Wisata')
            ->assertSee('8 provinsi')
            ->assertSee('14 kabupaten/kota');

        Livewire::actingAs($user)->test(ContentMapWidget::class)
            ->assertSee('Buka Peta Interaktif');
    }

    public function test_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/logout')->assertRedirect();
        $this->assertGuest();
    }
}
