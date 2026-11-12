<?php

namespace Tests\Feature;

use App\Enums\PublishStatus;
use App\Enums\UserRole;
use App\Filament\Pages\InteractiveMap;
use App\Filament\Pages\WebsiteSettings;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Culinaries\CulinaryResource;
use App\Filament\Resources\Culinaries\Pages\CreateCulinary;
use App\Filament\Resources\Culinaries\Pages\ListCulinaries;
use App\Filament\Resources\Destinations\DestinationResource;
use App\Filament\Resources\Destinations\Pages\EditDestination;
use App\Filament\Resources\Media\MediaResource;
use App\Filament\Resources\Provinces\Pages\ListProvinces;
use App\Filament\Resources\Provinces\ProvinceResource;
use App\Filament\Resources\Recipes\Pages\CreateRecipe;
use App\Filament\Resources\Recipes\RecipeResource;
use App\Filament\Resources\Regencies\RegencyResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\Culinary;
use App\Models\Destination;
use App\Models\Province;
use App\Models\Recipe;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SampleContentSeeder;
use Filament\Actions\DeleteAction;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminCmsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('admin');
        $this->seed(SampleContentSeeder::class);
        $this->admin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $this->actingAs($this->admin);
    }

    public function test_all_cms_pages_render(): void
    {
        $urls = [
            CulinaryResource::getUrl('index'),
            CulinaryResource::getUrl('create'),
            CulinaryResource::getUrl('edit', ['record' => Culinary::query()->first()]),
            RecipeResource::getUrl('index'),
            RecipeResource::getUrl('create'),
            RecipeResource::getUrl('edit', ['record' => Recipe::query()->first()]),
            DestinationResource::getUrl('index'),
            DestinationResource::getUrl('create'),
            DestinationResource::getUrl('edit', ['record' => Destination::query()->first()]),
            CategoryResource::getUrl('index'),
            ProvinceResource::getUrl('index'),
            ProvinceResource::getUrl('edit', ['record' => Province::query()->first()]),
            RegencyResource::getUrl('index'),
            MediaResource::getUrl('index'),
            UserResource::getUrl('index'),
            WebsiteSettings::getUrl(),
            InteractiveMap::getUrl(),
        ];

        foreach ($urls as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_create_culinary_with_coordinates(): void
    {
        $province = Province::query()->where('code', '51')->first();

        Livewire::test(CreateCulinary::class)
            ->fillForm([
                'name' => 'Sate Lilit',
                'slug' => 'sate-lilit',
                'province_id' => $province->id,
                'latitude' => -8.65,
                'longitude' => 115.21,
                'status' => PublishStatus::Published,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $culinary = Culinary::query()->where('slug', 'sate-lilit')->first();
        $this->assertNotNull($culinary);
        $this->assertSame(-8.65, $culinary->latitude);
        $this->assertNotNull($culinary->published_at, 'published_at harus terisi otomatis');
    }

    public function test_slug_must_be_unique(): void
    {
        Livewire::test(CreateCulinary::class)
            ->fillForm([
                'name' => 'Rendang',
                'slug' => 'rendang',
                'province_id' => Province::query()->first()->id,
                'status' => PublishStatus::Draft,
            ])
            ->call('create')
            ->assertHasFormErrors(['slug' => 'unique']);
    }

    public function test_create_recipe_with_ingredients_and_steps(): void
    {
        $culinary = Culinary::query()->where('slug', 'gudeg')->first();

        Livewire::test(CreateRecipe::class)
            ->fillForm([
                'culinary_id' => $culinary->id,
                'title' => 'Gudeg Kering Jogja',
                'status' => PublishStatus::Draft,
                'ingredients' => [
                    ['name' => 'Nangka muda', 'quantity' => '1', 'unit' => 'kg', 'note' => null],
                    ['name' => 'Gula jawa', 'quantity' => '200', 'unit' => 'gram', 'note' => 'sisir'],
                ],
                'steps' => [
                    ['instruction' => 'Rebus nangka muda bersama daun jati.'],
                    ['instruction' => 'Masak dengan santan dan gula jawa hingga kering.'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $recipe = Recipe::query()->where('title', 'Gudeg Kering Jogja')->first();
        $this->assertCount(2, $recipe->ingredients);
        $this->assertCount(2, $recipe->steps);
        $this->assertSame('Nangka muda', $recipe->ingredients->first()->name);
    }

    public function test_bulk_publish_from_table(): void
    {
        $drafts = Culinary::query()->where('status', PublishStatus::Draft)->get();
        $this->assertNotEmpty($drafts);

        Livewire::test(ListCulinaries::class)
            ->selectTableRecords($drafts->modelKeys())
            ->callAction(TestAction::make('publish')->table()->bulk());

        foreach ($drafts as $draft) {
            $this->assertSame(PublishStatus::Published, $draft->fresh()->status);
        }
    }

    public function test_toggle_publish_on_edit_page(): void
    {
        $destination = Destination::query()->where('status', PublishStatus::Draft)->first();

        Livewire::test(EditDestination::class, ['record' => $destination->getRouteKey()])
            ->callAction('togglePublish')
            ->assertSchemaStateSet(['status' => PublishStatus::Published]);

        $this->assertSame(PublishStatus::Published, $destination->fresh()->status);
    }

    public function test_province_in_use_cannot_be_deleted(): void
    {
        $province = Province::query()->where('code', '13')->first(); // dipakai Rendang

        Livewire::test(ListProvinces::class)
            ->callAction(TestAction::make(DeleteAction::class)->table($province))
            ->assertNotified('Wilayah tidak dapat dihapus');

        $this->assertModelExists($province);
    }

    public function test_website_settings_are_saved(): void
    {
        Livewire::test(WebsiteSettings::class)
            ->fillForm(['site_name' => 'Kuliner Nusantara', 'meta_keywords' => 'kuliner, wisata'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Kuliner Nusantara', Setting::getValue('site_name'));
        $this->assertSame('kuliner, wisata', Setting::getValue('meta_keywords'));
    }

    public function test_regular_admin_cannot_manage_administrators(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(UserResource::getUrl('index'))->assertForbidden();
        $this->actingAs($admin)->get(CulinaryResource::getUrl('index'))->assertOk();
    }
}
