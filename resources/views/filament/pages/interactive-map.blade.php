@php
    $layerTypes = ['kuliner', 'wisata', 'wilayah'];
@endphp

<div
    wire:ignore
    x-data="webgisMap({ mode: 'full' })"
    class="wg-root wg-root-full"
    x-bind:class="{ 'has-left': leftOpen, 'has-right': rightTab !== null }"
>
    <div class="wg-body">
        <div x-ref="map" class="wg-map"></div>

        {{-- ================= Panel layer (kiri) ================= --}}
        <button type="button" class="wg-left-toggle" x-show="! leftOpen" x-cloak x-on:click="leftOpen = true">
            <x-filament::icon icon="heroicon-o-square-3-stack-3d" />
            Layer
            <span class="wg-badge" x-text="visibleLayerCount + '/3'"></span>
        </button>

        <aside class="wg-panel wg-left" x-show="leftOpen" x-transition.opacity>
            <div class="wg-panel-header">
                <x-filament::icon icon="heroicon-o-square-3-stack-3d" />
                <span>Layer di peta (<span x-text="visibleLayerCount"></span>)</span>
                <span class="wg-spacer"></span>
                <button type="button" class="wg-icon-btn" title="Sembunyikan panel" x-on:click="leftOpen = false">
                    <x-filament::icon icon="heroicon-o-chevron-double-left" />
                </button>
            </div>

            <div class="wg-panel-body">
                <div class="wg-search">
                    <x-filament::icon icon="heroicon-o-magnifying-glass" />
                    <input type="search" class="wg-input" placeholder="Cari kuliner, wisata, wilayah…"
                           x-model="filters.search" x-on:input.debounce.250ms="render()">
                </div>

                <div class="wg-filter-grid">
                    <select class="wg-select" x-model="filters.province" x-on:change="render()" title="Filter provinsi">
                        <option value="">Semua provinsi</option>
                        <template x-for="p in data.provinceOptions" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                    <select class="wg-select" x-model="filters.status" x-on:change="render()" title="Filter status">
                        <option value="">Semua status</option>
                        <option value="published">Published</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>

                <div class="wg-section-title">
                    Daftar layer
                    <span class="wg-spacer"></span>
                    <button type="button" class="wg-link" x-on:click="setAllLayers(visibleLayerCount < 3)"
                            x-text="visibleLayerCount < 3 ? 'Tampilkan semua' : 'Sembunyikan semua'"></button>
                </div>

                @foreach ($layerTypes as $type)
                    <div class="wg-layer" x-bind:class="{ 'is-off': ! layers.{{ $type }}.visible }">
                        <div class="wg-layer-row">
                            <button type="button" class="wg-icon-btn" x-bind:class="{ 'is-active': layers.{{ $type }}.visible }"
                                    x-on:click="toggleLayer('{{ $type }}')" title="Tampilkan / sembunyikan">
                                <span x-show="layers.{{ $type }}.visible"><x-filament::icon icon="heroicon-o-eye" /></span>
                                <span x-show="! layers.{{ $type }}.visible" x-cloak><x-filament::icon icon="heroicon-o-eye-slash" /></span>
                            </button>
                            <span class="wg-layer-swatch" x-bind:style="'--swatch:' + layerMeta.{{ $type }}.color" x-html="icons.{{ $type }}"></span>
                            <div class="wg-layer-name">
                                <strong x-text="layerMeta.{{ $type }}.label"></strong>
                                <small>
                                    Titik · <span x-text="count('{{ $type }}')"></span>
                                    <template x-if="count('{{ $type }}') !== total('{{ $type }}')">
                                        <span>dari <span x-text="total('{{ $type }}')"></span></span>
                                    </template>
                                </small>
                            </div>
                            <button type="button" class="wg-icon-btn" title="Zoom ke layer" x-on:click="zoomToLayer('{{ $type }}')">
                                <x-filament::icon icon="heroicon-o-viewfinder-circle" />
                            </button>
                            @if ($type !== 'wilayah')
                                <button type="button" class="wg-icon-btn" title="Kategori"
                                        x-on:click="layers.{{ $type }}.expanded = ! layers.{{ $type }}.expanded">
                                    <span x-show="! layers.{{ $type }}.expanded"><x-filament::icon icon="heroicon-o-chevron-down" /></span>
                                    <span x-show="layers.{{ $type }}.expanded" x-cloak><x-filament::icon icon="heroicon-o-chevron-up" /></span>
                                </button>
                            @endif
                        </div>

                        @if ($type !== 'wilayah')
                            <div class="wg-layer-legend" x-show="layers.{{ $type }}.expanded" x-cloak>
                                <template x-for="c in categoriesFor('{{ $type }}')" :key="c.id">
                                    <label class="wg-check">
                                        <input type="checkbox" x-bind:checked="! hiddenCategories[c.id]" x-on:change="toggleCategory(c.id)">
                                        <span x-text="c.name"></span>
                                        <span class="wg-count" x-text="source('{{ $type }}').filter(f => f.category_id === c.id).length"></span>
                                    </label>
                                </template>
                                <template x-if="categoriesFor('{{ $type }}').length === 0">
                                    <span class="wg-help">Belum ada kategori.</span>
                                </template>
                            </div>
                        @endif
                    </div>
                @endforeach

                <div class="wg-section-title">Tampilan</div>
                <div class="wg-toggle-row">
                    <span>Label nama titik</span>
                    <button type="button" class="wg-switch" x-bind:class="{ 'is-on': showLabels }" x-on:click="toggleLabels()" aria-label="Label nama"></button>
                </div>

                <template x-if="hasActiveFilter">
                    <button type="button" class="wg-btn" style="width:100%; margin-top:10px" x-on:click="resetFilters()">
                        <x-filament::icon icon="heroicon-o-funnel" /> Reset filter
                    </button>
                </template>
            </div>
        </aside>

        {{-- ================= Toolbar (atas tengah) ================= --}}
        <div class="wg-toolbar" role="toolbar" aria-label="Alat peta">
            <button type="button" class="wg-tool" x-bind:class="{ 'is-active': tool === 'identify' }" x-on:click="setTool('identify')" title="Identifikasi objek">
                <x-filament::icon icon="heroicon-o-information-circle" />
            </button>
            <button type="button" class="wg-tool" x-bind:class="{ 'is-active': tool === 'measure' }" x-on:click="setTool(tool === 'measure' ? 'identify' : 'measure'); if (tool === 'measure') rightTab = 'tools'" title="Ukur jarak">
                <x-filament::icon icon="heroicon-o-arrows-right-left" />
            </button>
            <span class="wg-tool-sep"></span>
            <button type="button" class="wg-tool" x-on:click="zoomToData()" title="Zoom ke seluruh data">
                <x-filament::icon icon="heroicon-o-viewfinder-circle" />
            </button>
            <button type="button" class="wg-tool" x-on:click="locate()" title="Lokasi saya">
                <x-filament::icon icon="heroicon-o-map-pin" />
            </button>
            <button type="button" class="wg-tool" x-on:click="reload()" title="Muat ulang data">
                <x-filament::icon icon="heroicon-o-arrow-path" />
            </button>
            <span class="wg-tool-sep"></span>
            <button type="button" class="wg-tool" x-on:click="toggleFullscreen()" x-bind:title="isFullscreen ? 'Keluar layar penuh' : 'Layar penuh'">
                <span x-show="! isFullscreen"><x-filament::icon icon="heroicon-o-arrows-pointing-out" /></span>
                <span x-show="isFullscreen" x-cloak><x-filament::icon icon="heroicon-o-arrows-pointing-in" /></span>
            </button>
        </div>

        {{-- ================= Panel kanan ================= --}}
        <aside class="wg-panel wg-right" x-show="rightTab !== null" x-transition.opacity x-cloak>
            {{-- Basemap --}}
            <template x-if="rightTab === 'basemap'">
                <div style="display:contents">
                    <div class="wg-panel-header">
                        <x-filament::icon icon="heroicon-o-globe-asia-australia" />
                        <span>Basemap</span>
                        <span class="wg-spacer"></span>
                        <button type="button" class="wg-icon-btn" x-on:click="rightTab = null" title="Tutup"><x-filament::icon icon="heroicon-o-x-mark" /></button>
                    </div>
                    <div class="wg-panel-body">
                        <div class="wg-basemaps">
                            <template x-for="b in basemaps" :key="b.id">
                                <button type="button" class="wg-basemap" x-bind:class="{ 'is-active': activeBasemap === b.id }" x-on:click="setBasemap(b.id)">
                                    <img x-bind:src="b.thumb" x-bind:alt="b.name" loading="lazy">
                                    <span x-text="b.name"></span>
                                </button>
                            </template>
                        </div>
                        <div class="wg-section-title">Transparansi basemap</div>
                        <input type="range" class="wg-range" min="0.2" max="1" step="0.05"
                               x-bind:value="basemapOpacity" x-on:input="setBasemapOpacity($event.target.value)">
                    </div>
                </div>
            </template>

            {{-- Info objek --}}
            <template x-if="rightTab === 'info'">
                <div style="display:contents">
                    <div class="wg-panel-header">
                        <x-filament::icon icon="heroicon-o-information-circle" />
                        <span>Info objek</span>
                        <span class="wg-spacer"></span>
                        <button type="button" class="wg-icon-btn" x-on:click="rightTab = null" title="Tutup"><x-filament::icon icon="heroicon-o-x-mark" /></button>
                    </div>
                    <div class="wg-panel-body">
                        <template x-if="! selected">
                            <div class="wg-empty">
                                <x-filament::icon icon="heroicon-o-cursor-arrow-rays" />
                                Klik salah satu titik di peta untuk melihat detailnya.
                            </div>
                        </template>

                        <template x-if="selected">
                            <div>
                                <template x-if="selected.image">
                                    <img class="wg-info-image" x-bind:src="selected.image" x-bind:alt="selected.name">
                                </template>
                                <template x-if="! selected.image">
                                    <div class="wg-info-placeholder" x-bind:style="'--swatch:' + layerMeta[selected.type].color" x-html="icons[selected.type]"></div>
                                </template>

                                <div class="wg-info-title" x-text="selected.name"></div>
                                <div class="wg-badges">
                                    <span class="wg-badge is-layer" x-bind:style="'--swatch:' + layerMeta[selected.type].color" x-text="layerMeta[selected.type].label"></span>
                                    <template x-if="selected.status">
                                        <span class="wg-badge" x-bind:class="selected.status === 'published' ? 'is-published' : 'is-draft'" x-text="selected.status_label"></span>
                                    </template>
                                    <template x-if="selected.category">
                                        <span class="wg-badge" x-text="selected.category"></span>
                                    </template>
                                </div>

                                <p class="wg-summary" x-show="selected.summary" x-text="selected.summary"></p>

                                <dl class="wg-dl">
                                    <template x-if="selected.type !== 'wilayah'">
                                        <dt>Wilayah</dt>
                                    </template>
                                    <template x-if="selected.type !== 'wilayah'">
                                        <dd x-text="[selected.regency, selected.province].filter(Boolean).join(', ') || '-'"></dd>
                                    </template>

                                    <template x-if="selected.type === 'wilayah'"><dt>Kode</dt></template>
                                    <template x-if="selected.type === 'wilayah'"><dd x-text="selected.code"></dd></template>
                                    <template x-if="selected.type === 'wilayah'"><dt>Kab/Kota</dt></template>
                                    <template x-if="selected.type === 'wilayah'"><dd x-text="selected.regencies_count"></dd></template>
                                    <template x-if="selected.type === 'wilayah'"><dt>Kuliner</dt></template>
                                    <template x-if="selected.type === 'wilayah'"><dd x-text="selected.culinaries_count"></dd></template>
                                    <template x-if="selected.type === 'wilayah'"><dt>Wisata</dt></template>
                                    <template x-if="selected.type === 'wilayah'"><dd x-text="selected.destinations_count"></dd></template>

                                    <template x-if="selected.type === 'kuliner'"><dt>Resep</dt></template>
                                    <template x-if="selected.type === 'kuliner'"><dd x-text="selected.recipes_count + ' resep'"></dd></template>

                                    <template x-if="selected.type === 'wisata' && selected.address"><dt>Alamat</dt></template>
                                    <template x-if="selected.type === 'wisata' && selected.address"><dd x-text="selected.address"></dd></template>
                                    <template x-if="selected.type === 'wisata' && selected.opening_hours"><dt>Jam buka</dt></template>
                                    <template x-if="selected.type === 'wisata' && selected.opening_hours"><dd x-text="selected.opening_hours"></dd></template>
                                    <template x-if="selected.type === 'wisata' && selected.ticket_price"><dt>Tiket</dt></template>
                                    <template x-if="selected.type === 'wisata' && selected.ticket_price"><dd x-text="selected.ticket_price"></dd></template>

                                    <dt>Koordinat</dt>
                                    <dd>
                                        <span x-text="formatCoord(selected.lat, 6) + ', ' + formatCoord(selected.lng, 6)"></span>
                                        <button type="button" class="wg-link" x-on:click="copyCoordinates(selected.lat, selected.lng)">Salin</button>
                                    </dd>
                                </dl>

                                <div class="wg-actions">
                                    <button type="button" class="wg-btn" x-on:click="focusSelected()">
                                        <x-filament::icon icon="heroicon-o-magnifying-glass-plus" /> Zoom
                                    </button>
                                    <a class="wg-btn wg-btn-primary" x-bind:href="selected.edit_url" wire:navigate>
                                        <x-filament::icon icon="heroicon-o-pencil-square" /> Edit data
                                    </a>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>

            {{-- Legenda --}}
            <template x-if="rightTab === 'legend'">
                <div style="display:contents">
                    <div class="wg-panel-header">
                        <x-filament::icon icon="heroicon-o-list-bullet" />
                        <span>Legenda</span>
                        <span class="wg-spacer"></span>
                        <button type="button" class="wg-icon-btn" x-on:click="rightTab = null" title="Tutup"><x-filament::icon icon="heroicon-o-x-mark" /></button>
                    </div>
                    <div class="wg-panel-body">
                        @foreach ($layerTypes as $type)
                            <div class="wg-legend-item">
                                <div class="wg-pin" x-bind:style="'--pin:' + layerMeta.{{ $type }}.color"><span class="wg-pin-icon" x-html="icons.{{ $type }}"></span></div>
                                <div class="wg-layer-name">
                                    <strong x-text="layerMeta.{{ $type }}.label"></strong>
                                    <small x-text="layerMeta.{{ $type }}.subtitle"></small>
                                </div>
                            </div>
                        @endforeach
                        <div class="wg-section-title">Sistem koordinat</div>
                        <p class="wg-help">WGS 84 (EPSG:4326). Tampilan peta Web Mercator (EPSG:3857).</p>
                        <div class="wg-section-title">Sumber basemap</div>
                        <p class="wg-help" x-text="activeBasemapName"></p>
                    </div>
                </div>
            </template>

            {{-- Alat --}}
            <template x-if="rightTab === 'tools'">
                <div style="display:contents">
                    <div class="wg-panel-header">
                        <x-filament::icon icon="heroicon-o-wrench-screwdriver" />
                        <span>Alat</span>
                        <span class="wg-spacer"></span>
                        <button type="button" class="wg-icon-btn" x-on:click="rightTab = null" title="Tutup"><x-filament::icon icon="heroicon-o-x-mark" /></button>
                    </div>
                    <div class="wg-panel-body">
                        <div class="wg-section-title" style="margin-top:0">Ukur jarak</div>
                        <p class="wg-help">Aktifkan, lalu klik titik-titik di peta. Klik ganda atau tekan Esc untuk selesai.</p>
                        <div class="wg-metric" x-text="formatDistance(measureDistance)"></div>
                        <div class="wg-actions">
                            <button type="button" class="wg-btn" x-bind:class="{ 'wg-btn-primary': tool === 'measure' }"
                                    x-on:click="setTool(tool === 'measure' ? 'identify' : 'measure')"
                                    x-text="tool === 'measure' ? 'Selesai mengukur' : 'Mulai mengukur'"></button>
                            <button type="button" class="wg-btn" x-on:click="clearMeasure()">Hapus</button>
                        </div>

                        <div class="wg-section-title">Lompat ke koordinat</div>
                        <form x-on:submit.prevent="goTo()">
                            <input type="text" class="wg-input" placeholder="-8.6705, 115.2126" x-model="gotoInput">
                            <p class="wg-error-text" x-show="gotoError" x-text="gotoError"></p>
                            <button type="submit" class="wg-btn wg-btn-primary" style="width:100%; margin-top:8px">
                                <x-filament::icon icon="heroicon-o-paper-airplane" /> Tuju lokasi
                            </button>
                        </form>
                    </div>
                </div>
            </template>
        </aside>

        {{-- ================= Rail ikon kanan ================= --}}
        <nav class="wg-rail" aria-label="Panel peta">
            <button type="button" class="wg-rail-btn" x-bind:class="{ 'is-active': rightTab === 'basemap' }" x-on:click="openTab('basemap')" title="Basemap">
                <x-filament::icon icon="heroicon-o-globe-asia-australia" />
            </button>
            <button type="button" class="wg-rail-btn" x-bind:class="{ 'is-active': rightTab === 'info' }" x-on:click="openTab('info')" title="Info objek">
                <x-filament::icon icon="heroicon-o-information-circle" />
            </button>
            <button type="button" class="wg-rail-btn" x-bind:class="{ 'is-active': rightTab === 'legend' }" x-on:click="openTab('legend')" title="Legenda">
                <x-filament::icon icon="heroicon-o-list-bullet" />
            </button>
            <span class="wg-rail-sep"></span>
            <button type="button" class="wg-rail-btn" x-bind:class="{ 'is-active': rightTab === 'tools' }" x-on:click="openTab('tools')" title="Alat ukur & koordinat">
                <x-filament::icon icon="heroicon-o-wrench-screwdriver" />
            </button>
        </nav>

        <div class="wg-notice" x-show="notice" x-transition.opacity x-text="notice" x-cloak></div>

        <div class="wg-loading" x-show="loading" x-transition.opacity>
            <div class="wg-spinner"></div>
        </div>

        <template x-if="error">
            <div class="wg-notice" style="background:#be123c">
                <span x-text="error"></span>
                <button type="button" class="wg-link" style="color:#fff !important; margin-left:8px" x-on:click="reload()">Coba lagi</button>
            </div>
        </template>
    </div>

    {{-- ================= Status bar ================= --}}
    <footer class="wg-status">
        <span class="wg-dot"></span>
        <span>EPSG:4326</span>
        <span class="wg-sep">|</span>
        <span x-text="cursor ? formatCoord(cursor.lat) + ', ' + formatCoord(cursor.lng) : '—'"></span>
        <span class="wg-sep">|</span>
        <span x-text="'z' + zoom"></span>
        <span class="wg-spacer"></span>
        <span class="wg-hide-sm"><span x-text="visibleLayerCount"></span> layer aktif · <span x-text="visiblePoints"></span> titik tampil</span>
        <span class="wg-sep wg-hide-sm">|</span>
        <span class="wg-hide-sm">Basemap: <span x-text="activeBasemapName"></span></span>
        <span class="wg-sep wg-hide-sm">|</span>
        <strong>WebGIS Kuliner Nusantara</strong>
    </footer>
</div>
