/**
 * WebGIS Kuliner Nusantara — komponen peta (Alpine.js + Leaflet).
 *
 * Dipakai lewat: x-data="webgisMap({ mode: 'full' | 'mini' })"
 * Komponen Livewire induk wajib punya method `loadMapData()` (#[Renderless]).
 */
(() => {
    if (window.webgisMap) {
        return;
    }

    const svg = (paths) =>
        `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${paths}</svg>`;

    /** Ikon garis (gaya Tabler) untuk tiap layer. */
    const ICONS = {
        kuliner: svg('<path d="M4 11h16a1 1 0 0 1 1 1v.5c0 1.5-2.5 5.6-4 6.5v1a1 1 0 0 1-1 1H8a1 1 0 0 1-1-1v-1c-1.7-1-4-5-4-6.5V12a1 1 0 0 1 1-1z"/><path d="M12 4a2.4 2.4 0 0 0-1 2 2.4 2.4 0 0 0 1 2"/><path d="M16 4a2.4 2.4 0 0 0-1 2 2.4 2.4 0 0 0 1 2"/><path d="M8 4a2.4 2.4 0 0 0-1 2 2.4 2.4 0 0 0 1 2"/>'),
        wisata: svg('<path d="M3 20l5.5-9 3.5 5.5 2.5-3.5L21 20z"/><circle cx="16" cy="6" r="2"/>'),
        wilayah: svg('<path d="M5 21V4"/><path d="M5 4h12l-2.5 4L17 12H5"/>'),
    };

    const LAYERS = {
        kuliner: { label: 'Kuliner', subtitle: 'Titik asal kuliner', color: '#a0522d' },
        wisata: { label: 'Destinasi Wisata', subtitle: 'Lokasi wisata', color: '#0e9f8e' },
        wilayah: { label: 'Pusat Provinsi', subtitle: 'Titik tengah wilayah', color: '#475569' },
    };

    /**
     * Semua basemap di bawah dapat dipakai tanpa API key.
     * (CARTO basemaps sekarang mewajibkan API key, jadi tidak dipakai.)
     */
    const ESRI = 'https://server.arcgisonline.com/ArcGIS/rest/services';
    const BASEMAPS = [
        { id: 'osm', name: 'OpenStreetMap', url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png', maxZoom: 19, attribution: '&copy; OpenStreetMap contributors' },
        { id: 'light', name: 'Light Gray', url: `${ESRI}/Canvas/World_Light_Gray_Base/MapServer/tile/{z}/{y}/{x}`, maxZoom: 19, maxNativeZoom: 16, attribution: 'Tiles &copy; Esri &mdash; Esri, HERE, Garmin, &copy; OpenStreetMap contributors' },
        { id: 'street', name: 'Jalan (Esri)', url: `${ESRI}/World_Street_Map/MapServer/tile/{z}/{y}/{x}`, maxZoom: 19, attribution: 'Tiles &copy; Esri &mdash; Esri, HERE, Garmin, &copy; OpenStreetMap contributors' },
        { id: 'satellite', name: 'Citra Satelit', url: `${ESRI}/World_Imagery/MapServer/tile/{z}/{y}/{x}`, maxZoom: 19, attribution: 'Tiles &copy; Esri, Maxar, Earthstar Geographics' },
        { id: 'topo', name: 'Topografi', url: `${ESRI}/World_Topo_Map/MapServer/tile/{z}/{y}/{x}`, maxZoom: 19, attribution: 'Tiles &copy; Esri' },
        { id: 'dark', name: 'Dark Gray', url: `${ESRI}/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}`, maxZoom: 19, maxNativeZoom: 16, attribution: 'Tiles &copy; Esri &mdash; Esri, HERE, Garmin, &copy; OpenStreetMap contributors' },
    ];

    /** Tile z5 yang mencakup Indonesia bagian tengah, untuk thumbnail basemap. */
    const thumbnail = (b) => b.url.replace('{s}', 'a').replace('{z}', '5').replace('{x}', '26').replace('{y}', '16').replace('{r}', '');

    const INDONESIA = { center: [-2.5, 118], zoom: 5 };
    const STORAGE_KEY = 'webgis.basemap';

    const storage = {
        get(key) {
            try { return window.localStorage.getItem(key); } catch (e) { return null; }
        },
        set(key, value) {
            try { window.localStorage.setItem(key, value); } catch (e) { /* abaikan */ }
        },
    };

    const waitForLeaflet = () => new Promise((resolve) => {
        const check = () => (window.L ? resolve() : setTimeout(check, 50));
        check();
    });

    const pinIcon = (type, selected = false) => L.divIcon({
        className: 'wg-pin-wrap',
        html: `<div class="wg-pin${selected ? ' is-selected' : ''}" style="--pin:${LAYERS[type].color}"><span class="wg-pin-icon">${ICONS[type]}</span></div>`,
        iconSize: [34, 42],
        iconAnchor: [17, 40],
        tooltipAnchor: [0, -36],
    });

    const formatDistance = (meters) => (meters >= 1000 ? `${(meters / 1000).toFixed(2)} km` : `${Math.round(meters)} m`);

    window.webgisMap = (options = {}) => {
        /** Objek Leaflet disimpan di luar state Alpine (Leaflet tidak boleh di-proxy). */
        const g = {
            map: null,
            base: null,
            groups: {},
            markers: {},
            selectedKey: null,
            measureLine: null,
            measurePoints: [],
            measureMarkers: null,
            gotoMarker: null,
            locateMarker: null,
            onResize: null,
            onKey: null,
            onFullscreen: null,
        };

        return {
            mode: options.mode ?? 'full',
            icons: ICONS,
            layerMeta: LAYERS,
            basemaps: BASEMAPS.map((b) => ({ ...b, thumb: thumbnail(b) })),

            loading: true,
            error: null,
            data: { culinaries: [], destinations: [], provinces: [], categories: [], provinceOptions: [] },

            layers: {
                kuliner: { visible: true, expanded: false },
                wisata: { visible: true, expanded: false },
                wilayah: { visible: false, expanded: false },
            },
            hiddenCategories: {},
            filters: { search: '', province: '', status: '' },
            showLabels: false,

            leftOpen: true,
            rightTab: null,
            activeBasemap: storage.get(STORAGE_KEY) || 'osm',
            basemapOpacity: 1,

            tool: 'identify',
            selected: null,
            cursor: null,
            zoom: INDONESIA.zoom,
            measureDistance: 0,
            gotoInput: '',
            gotoError: null,
            isFullscreen: false,
            notice: null,

            async init() {
                await waitForLeaflet();
                this.setupMap();
                await this.reload(true);
            },

            destroy() {
                window.removeEventListener('resize', g.onResize);
                document.removeEventListener('keydown', g.onKey);
                document.removeEventListener('fullscreenchange', g.onFullscreen);
                g.map?.remove();
            },

            /* ------------------------------------------------------------------ */
            /* Setup                                                              */
            /* ------------------------------------------------------------------ */

            setupMap() {
                const isFull = this.mode === 'full';

                g.map = L.map(this.$refs.map, {
                    zoomControl: false,
                    attributionControl: true,
                    minZoom: 3,
                    worldCopyJump: true,
                    scrollWheelZoom: isFull,
                }).setView(INDONESIA.center, INDONESIA.zoom);

                g.map.attributionControl.setPrefix(false);
                L.control.zoom({ position: isFull ? 'topright' : 'topleft', zoomInTitle: 'Perbesar', zoomOutTitle: 'Perkecil' }).addTo(g.map);
                L.control.scale({ position: 'bottomleft', imperial: false }).addTo(g.map);

                Object.keys(LAYERS).forEach((type) => {
                    g.groups[type] = L.featureGroup();
                    if (this.layers[type].visible) {
                        g.groups[type].addTo(g.map);
                    }
                });
                g.measureMarkers = L.layerGroup().addTo(g.map);

                this.setBasemap(this.activeBasemap);

                let frame = null;
                g.map.on('mousemove', (e) => {
                    if (frame) { return; }
                    frame = requestAnimationFrame(() => {
                        this.cursor = { lat: e.latlng.lat, lng: e.latlng.lng };
                        frame = null;
                        if (this.tool === 'measure' && g.measurePoints.length) { this.drawMeasure(e.latlng); }
                    });
                });
                g.map.on('mouseout', () => { this.cursor = null; });
                g.map.on('zoomend', () => { this.zoom = g.map.getZoom(); });
                g.map.on('click', (e) => this.onMapClick(e));
                g.map.on('dblclick', () => { if (this.tool === 'measure') { this.finishMeasure(); } });

                g.onKey = (e) => {
                    if (e.key === 'Escape') {
                        if (this.tool === 'measure') { this.finishMeasure(); } else { this.clearSelection(); }
                    }
                };
                document.addEventListener('keydown', g.onKey);

                g.onFullscreen = () => {
                    this.isFullscreen = document.fullscreenElement === this.$root;
                    this.refreshSize();
                };
                document.addEventListener('fullscreenchange', g.onFullscreen);

                if (isFull) {
                    g.onResize = () => this.refreshSize();
                    window.addEventListener('resize', g.onResize);
                }
                this.refreshSize();
            },

            refreshSize() {
                if (this.mode === 'full' && ! this.isFullscreen) {
                    const top = this.$root.getBoundingClientRect().top + window.scrollY;
                    this.$root.style.height = `${Math.max(420, window.innerHeight - top)}px`;
                } else if (this.isFullscreen) {
                    this.$root.style.height = '100vh';
                }
                setTimeout(() => g.map?.invalidateSize(), 60);
            },

            /* ------------------------------------------------------------------ */
            /* Data                                                               */
            /* ------------------------------------------------------------------ */

            async reload(fit = false) {
                this.loading = true;
                this.error = null;
                try {
                    this.data = await this.$wire.loadMapData();
                    this.render();
                    if (fit) { this.zoomToData(false); }
                } catch (e) {
                    this.error = 'Gagal memuat data peta. Coba muat ulang.';
                } finally {
                    this.loading = false;
                }
            },

            source(type) {
                return { kuliner: this.data.culinaries, wisata: this.data.destinations, wilayah: this.data.provinces }[type] ?? [];
            },

            features(type) {
                const term = this.filters.search.trim().toLowerCase();
                return this.source(type).filter((f) => {
                    if (this.filters.province && String(f.province_id) !== String(this.filters.province)) { return false; }
                    if (type !== 'wilayah') {
                        if (this.filters.status && f.status !== this.filters.status) { return false; }
                        if (f.category_id && this.hiddenCategories[f.category_id]) { return false; }
                    }
                    if (term) {
                        const haystack = `${f.name} ${f.province ?? ''} ${f.regency ?? ''} ${f.category ?? ''}`.toLowerCase();
                        if (! haystack.includes(term)) { return false; }
                    }
                    return true;
                });
            },

            count(type) {
                return this.features(type).length;
            },

            total(type) {
                return this.source(type).length;
            },

            categoriesFor(type) {
                const key = type === 'kuliner' ? 'culinary' : 'destination';
                return this.data.categories.filter((c) => c.type === key);
            },

            get visiblePoints() {
                return Object.keys(LAYERS).reduce((sum, type) => sum + (this.layers[type].visible ? this.count(type) : 0), 0);
            },

            get visibleLayerCount() {
                return Object.values(this.layers).filter((l) => l.visible).length;
            },

            get hasActiveFilter() {
                return this.filters.search !== '' || this.filters.province !== '' || this.filters.status !== '' || Object.values(this.hiddenCategories).some(Boolean);
            },

            resetFilters() {
                this.filters = { search: '', province: '', status: '' };
                this.hiddenCategories = {};
                this.render();
            },

            /* ------------------------------------------------------------------ */
            /* Render                                                             */
            /* ------------------------------------------------------------------ */

            render() {
                if (! g.map) { return; }
                g.markers = {};

                Object.keys(LAYERS).forEach((type) => {
                    const group = g.groups[type];
                    group.clearLayers();

                    this.features(type).forEach((f) => {
                        const key = `${type}:${f.id}`;
                        const marker = L.marker([f.lat, f.lng], {
                            icon: pinIcon(type, key === g.selectedKey),
                            title: f.name,
                            riseOnHover: true,
                            keyboard: true,
                        });

                        const label = document.createElement('span');
                        label.textContent = f.name;
                        marker.bindTooltip(label, this.showLabels
                            ? { permanent: true, direction: 'right', offset: [14, -22], className: 'wg-label' }
                            : { direction: 'top', className: 'wg-tooltip' });

                        marker.on('click', (e) => {
                            if (this.tool !== 'identify') { return; }
                            L.DomEvent.stopPropagation(e);
                            this.select(type, f);
                        });

                        g.markers[key] = marker;
                        group.addLayer(marker);
                    });
                });
            },

            toggleLayer(type) {
                this.layers[type].visible = ! this.layers[type].visible;
                if (this.layers[type].visible) { g.groups[type].addTo(g.map); } else { g.groups[type].remove(); }
            },

            setAllLayers(visible) {
                Object.keys(LAYERS).forEach((type) => {
                    if (this.layers[type].visible !== visible) { this.toggleLayer(type); }
                });
            },

            toggleCategory(id) {
                this.hiddenCategories = { ...this.hiddenCategories, [id]: ! this.hiddenCategories[id] };
                this.render();
            },

            zoomToLayer(type) {
                const bounds = g.groups[type].getLayers().length ? g.groups[type].getBounds() : null;
                if (bounds?.isValid()) { g.map.flyToBounds(bounds, { padding: [60, 60], maxZoom: 11, duration: 0.8 }); }
            },

            zoomToData(animate = true) {
                const bounds = L.latLngBounds([]);
                Object.keys(LAYERS).forEach((type) => {
                    if (this.layers[type].visible && g.groups[type].getLayers().length) { bounds.extend(g.groups[type].getBounds()); }
                });
                if (bounds.isValid()) {
                    const opts = { padding: [60, 60], maxZoom: 9 };
                    animate ? g.map.flyToBounds(bounds, { ...opts, duration: 0.8 }) : g.map.fitBounds(bounds, opts);
                } else {
                    g.map.setView(INDONESIA.center, INDONESIA.zoom);
                }
            },

            /* ------------------------------------------------------------------ */
            /* Identify                                                           */
            /* ------------------------------------------------------------------ */

            select(type, feature) {
                this.setMarkerSelected(g.selectedKey, false);
                g.selectedKey = `${type}:${feature.id}`;
                this.setMarkerSelected(g.selectedKey, true);
                this.selected = { ...feature, type };
                if (this.mode === 'full') { this.rightTab = 'info'; }
            },

            setMarkerSelected(key, selected) {
                if (! key || ! g.markers[key]) { return; }
                g.markers[key].setIcon(pinIcon(key.split(':')[0], selected));
                g.markers[key].setZIndexOffset(selected ? 1000 : 0);
            },

            clearSelection() {
                this.setMarkerSelected(g.selectedKey, false);
                g.selectedKey = null;
                this.selected = null;
            },

            focusSelected() {
                if (this.selected) { g.map.flyTo([this.selected.lat, this.selected.lng], Math.max(g.map.getZoom(), 13), { duration: 0.8 }); }
            },

            copyCoordinates(lat, lng) {
                const text = `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;
                navigator.clipboard?.writeText(text).then(() => this.flash('Koordinat disalin'));
            },

            onMapClick(e) {
                if (this.tool === 'measure') {
                    this.addMeasurePoint(e.latlng);
                } else if (this.tool === 'identify') {
                    this.clearSelection();
                }
            },

            /* ------------------------------------------------------------------ */
            /* Basemap                                                            */
            /* ------------------------------------------------------------------ */

            setBasemap(id) {
                const basemap = BASEMAPS.find((b) => b.id === id) ?? BASEMAPS[0];
                g.base?.remove();
                g.base = L.tileLayer(basemap.url, {
                    attribution: basemap.attribution,
                    maxZoom: basemap.maxZoom,
                    maxNativeZoom: basemap.maxNativeZoom ?? basemap.maxZoom,
                    subdomains: basemap.subdomains ?? 'abc',
                    opacity: this.basemapOpacity,
                }).addTo(g.map);
                g.base.bringToBack();
                this.activeBasemap = basemap.id;
                storage.set(STORAGE_KEY, basemap.id);
            },

            setBasemapOpacity(value) {
                this.basemapOpacity = Number(value);
                g.base?.setOpacity(this.basemapOpacity);
            },

            get activeBasemapName() {
                return BASEMAPS.find((b) => b.id === this.activeBasemap)?.name ?? '';
            },

            /* ------------------------------------------------------------------ */
            /* Tools                                                              */
            /* ------------------------------------------------------------------ */

            setTool(tool) {
                if (this.tool === 'measure' && tool !== 'measure') { this.clearMeasure(); }
                this.tool = tool;
                g.map.getContainer().classList.toggle('wg-crosshair', tool === 'measure');
                if (tool === 'measure') {
                    g.map.doubleClickZoom.disable();
                    this.clearSelection();
                    this.flash('Klik peta untuk menambah titik ukur. Klik ganda / Esc untuk selesai.');
                } else {
                    g.map.doubleClickZoom.enable();
                }
            },

            addMeasurePoint(latlng) {
                g.measurePoints.push(latlng);
                L.circleMarker(latlng, { radius: 4, color: '#0aa5b8', weight: 2, fillColor: '#fff', fillOpacity: 1 }).addTo(g.measureMarkers);
                this.drawMeasure();
            },

            drawMeasure(hover = null) {
                const points = hover ? [...g.measurePoints, hover] : g.measurePoints;
                if (! g.measureLine) {
                    g.measureLine = L.polyline(points, { color: '#0aa5b8', weight: 3, dashArray: '6 6' }).addTo(g.map);
                } else {
                    g.measureLine.setLatLngs(points);
                }
                let meters = 0;
                for (let i = 1; i < points.length; i++) { meters += points[i - 1].distanceTo(points[i]); }
                this.measureDistance = meters;
            },

            finishMeasure() {
                this.drawMeasure();
                if (g.measurePoints.length > 1) {
                    const last = g.measurePoints[g.measurePoints.length - 1];
                    const label = document.createElement('span');
                    label.textContent = formatDistance(this.measureDistance);
                    L.tooltip({ permanent: true, direction: 'right', className: 'wg-measure-label' })
                        .setLatLng(last).setContent(label).addTo(g.measureMarkers);
                }
                g.measurePoints = [];
                g.measureLine = null;
            },

            clearMeasure() {
                g.measureMarkers.clearLayers();
                g.measureLine?.remove();
                g.measureLine = null;
                g.measurePoints = [];
                this.measureDistance = 0;
                g.map.eachLayer((layer) => {
                    if (layer instanceof L.Polyline && layer.options.dashArray === '6 6') { layer.remove(); }
                });
            },

            formatDistance,

            goTo() {
                this.gotoError = null;
                const match = this.gotoInput.trim().match(/^(-?\d+(?:\.\d+)?)\s*[,;\s]\s*(-?\d+(?:\.\d+)?)$/);
                if (! match) { this.gotoError = 'Format: lintang, bujur — mis. -8.6705, 115.2126'; return; }
                const lat = Number(match[1]);
                const lng = Number(match[2]);
                if (lat < -90 || lat > 90 || lng < -180 || lng > 180) { this.gotoError = 'Koordinat di luar jangkauan'; return; }
                g.gotoMarker?.remove();
                g.gotoMarker = L.circleMarker([lat, lng], { radius: 9, color: '#e11d48', weight: 3, fillOpacity: 0.15 }).addTo(g.map);
                g.map.flyTo([lat, lng], 14, { duration: 0.8 });
            },

            locate() {
                if (! navigator.geolocation) { this.flash('Browser tidak mendukung geolokasi'); return; }
                this.flash('Mencari lokasi Anda…');
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const latlng = [pos.coords.latitude, pos.coords.longitude];
                        g.locateMarker?.remove();
                        g.locateMarker = L.circleMarker(latlng, { radius: 8, color: '#fff', weight: 3, fillColor: '#2563eb', fillOpacity: 1 }).addTo(g.map);
                        g.map.flyTo(latlng, 14, { duration: 0.8 });
                        this.notice = null;
                    },
                    () => this.flash('Lokasi tidak dapat diakses'),
                    { enableHighAccuracy: true, timeout: 10000 },
                );
            },

            toggleFullscreen() {
                if (document.fullscreenElement) { document.exitFullscreen(); } else { this.$root.requestFullscreen?.(); }
            },

            toggleLabels() {
                this.showLabels = ! this.showLabels;
                this.render();
            },

            openTab(tab) {
                this.rightTab = this.rightTab === tab ? null : tab;
            },

            flash(message) {
                this.notice = message;
                clearTimeout(this._noticeTimer);
                this._noticeTimer = setTimeout(() => { this.notice = null; }, 3500);
            },

            formatCoord(value, digits = 5) {
                return value === null || value === undefined ? '-' : Number(value).toFixed(digits);
            },
        };
    };
})();
