<x-filament-widgets::widget>
    <x-filament::section
        heading="Peta Sebaran Kuliner & Wisata"
        description="Pratinjau titik asal kuliner dan destinasi wisata"
        icon="heroicon-o-map"
    >
        <x-slot name="afterHeader">
            <x-filament::button
                tag="a"
                :href="\App\Filament\Pages\InteractiveMap::getUrl()"
                icon="heroicon-o-arrows-pointing-out"
                color="gray"
                size="sm"
            >
                Buka Peta Interaktif
            </x-filament::button>
        </x-slot>

        <div wire:ignore x-data="webgisMap({ mode: 'mini' })" class="wg-root wg-root-mini">
            <div class="wg-body">
                <div x-ref="map" class="wg-map"></div>

                <div class="wg-mini-legend">
                    @foreach (['kuliner', 'wisata'] as $type)
                        <button type="button" class="wg-chip" x-bind:class="{ 'is-off': ! layers.{{ $type }}.visible }"
                                x-on:click="toggleLayer('{{ $type }}')" x-bind:style="'--swatch:' + layerMeta.{{ $type }}.color">
                            <i></i>
                            <span x-text="layerMeta.{{ $type }}.label"></span>
                            <span x-text="'(' + count('{{ $type }}') + ')'"></span>
                        </button>
                    @endforeach
                </div>

                <div class="wg-loading" x-show="loading" x-transition.opacity>
                    <div class="wg-spinner"></div>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
