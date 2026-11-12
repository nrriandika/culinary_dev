<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Spesifikasi CMS > Pengaturan Website: informasi dasar website dan metadata.
 *
 * @property-read Schema $form
 */
class WebsiteSettings extends Page
{
    protected static ?string $slug = 'pengaturan-website';

    protected static ?string $title = 'Pengaturan Website';

    protected static ?string $navigationLabel = 'Pengaturan Website';

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string | UnitEnum | null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 1;

    /** Semua key yang dikelola halaman ini. */
    public const KEYS = [
        'site_name', 'site_tagline', 'site_description', 'logo', 'favicon',
        'meta_title', 'meta_description', 'meta_keywords', 'og_image', 'google_analytics_id',
        'contact_email', 'contact_phone', 'contact_address',
        'social_instagram', 'social_facebook', 'social_youtube', 'social_tiktok',
        'footer_text',
    ];

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $values = Setting::query()->whereIn('key', self::KEYS)->pluck('value', 'key')->all();

        $this->form->fill(array_merge(
            ['site_name' => config('app.name')],
            array_filter($values, fn ($v) => $v !== null),
        ));
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make()->persistTabInQueryString()->tabs([
                    Tab::make('Informasi Dasar')
                        ->icon('heroicon-o-information-circle')
                        ->columns(2)
                        ->schema([
                            TextInput::make('site_name')->label('Nama website')->required()->maxLength(100),
                            TextInput::make('site_tagline')->label('Tagline')->maxLength(150),
                            Textarea::make('site_description')->label('Deskripsi website')->rows(3)->columnSpanFull(),
                            FileUpload::make('logo')->label('Logo')->image()->disk('public')->directory('pengaturan')->maxSize(2048),
                            FileUpload::make('favicon')->label('Favicon')->image()->disk('public')->directory('pengaturan')->maxSize(512)
                                ->helperText('Disarankan PNG persegi 512×512'),
                            Textarea::make('footer_text')->label('Teks footer')->rows(2)->columnSpanFull(),
                        ]),

                    Tab::make('SEO & Metadata')
                        ->icon('heroicon-o-magnifying-glass')
                        ->columns(2)
                        ->schema([
                            TextInput::make('meta_title')->label('Meta title default')->maxLength(70)
                                ->helperText('Maks. ±60 karakter agar tidak terpotong di hasil pencarian'),
                            TextInput::make('google_analytics_id')->label('Google Analytics ID')->placeholder('G-XXXXXXXXXX'),
                            Textarea::make('meta_description')->label('Meta description default')->rows(2)->maxLength(170)->columnSpanFull(),
                            TextInput::make('meta_keywords')->label('Meta keywords')->placeholder('kuliner, wisata, indonesia')->columnSpanFull(),
                            FileUpload::make('og_image')->label('Gambar share (Open Graph)')->image()->disk('public')->directory('pengaturan')->maxSize(2048)
                                ->helperText('Tampil saat link dibagikan ke media sosial. Disarankan 1200×630'),
                        ]),

                    Tab::make('Kontak & Media Sosial')
                        ->icon('heroicon-o-chat-bubble-left-right')
                        ->schema([
                            Section::make('Kontak')->columns(2)->schema([
                                TextInput::make('contact_email')->label('Email')->email(),
                                TextInput::make('contact_phone')->label('Telepon')->tel(),
                                Textarea::make('contact_address')->label('Alamat')->rows(2)->columnSpanFull(),
                            ]),
                            Section::make('Media Sosial')->columns(2)->schema([
                                TextInput::make('social_instagram')->label('Instagram')->url()->prefixIcon('heroicon-o-link'),
                                TextInput::make('social_facebook')->label('Facebook')->url()->prefixIcon('heroicon-o-link'),
                                TextInput::make('social_youtube')->label('YouTube')->url()->prefixIcon('heroicon-o-link'),
                                TextInput::make('social_tiktok')->label('TikTok')->url()->prefixIcon('heroicon-o-link'),
                            ]),
                        ]),
                ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make([
                        Action::make('save')->label('Simpan Pengaturan')->submit('save')->keyBindings(['mod+s']),
                    ]),
                ]),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        Setting::putMany(array_intersect_key($data, array_flip(self::KEYS)));

        Notification::make()->title('Pengaturan disimpan')->success()->send();
    }
}
