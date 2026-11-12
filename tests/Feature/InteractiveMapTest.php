<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Filament\Pages\InteractiveMap;
use App\Filament\Resources\Culinaries\CulinaryResource;
use App\Models\Culinary;
use App\Models\Province;
use App\Models\User;
use Database\Seeders\SampleContentSeeder;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class InteractiveMapTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->seed(SampleContentSeeder::class);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(InteractiveMap::getUrl())->assertRedirect('/admin/login');
    }

    public function test_admin_can_open_the_map_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(InteractiveMap::getUrl())
            ->assertOk()
            ->assertSee('webgisMap', false)
            ->assertSee('EPSG:4326');
    }

    public function test_map_data_only_contains_points_with_coordinates(): void
    {
        Culinary::query()->create([
            'name' => 'Kuliner Tanpa Titik',
            'slug' => 'kuliner-tanpa-titik',
            'province_id' => Province::query()->value('id'),
            'status' => PublishStatus::Published,
        ]);

        $data = Livewire::actingAs(User::factory()->create())
            ->test(InteractiveMap::class)
            ->instance()
            ->loadMapData();

        $this->assertCount(8, $data['culinaries']);
        $this->assertNotContains('Kuliner Tanpa Titik', array_column($data['culinaries'], 'name'));
        $this->assertCount(8, $data['destinations']);
        $this->assertCount(8, $data['provinces']);

        $rendang = collect($data['culinaries'])->firstWhere('name', 'Rendang');
        $this->assertSame('Sumatera Barat', $rendang['province']);
        $this->assertSame('Kota Padang', $rendang['regency']);
        $this->assertSame('published', $rendang['status']);
        $this->assertSame(
            CulinaryResource::getUrl('edit', ['record' => Culinary::query()->where('slug', 'rendang')->first()]),
            $rendang['edit_url'],
        );
    }
}
