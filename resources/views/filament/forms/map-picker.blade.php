@php
    // Path state sibling: data.map_picker -> data.latitude / data.longitude
    $base = \Illuminate\Support\Str::beforeLast($getStatePath(), '.');
    $latPath = $base . '.latitude';
    $lngPath = $base . '.longitude';
@endphp

<div
    wire:ignore
    x-data="{
        map: null,
        marker: null,
        init() {
            const boot = () => {
                if (! window.L) { return setTimeout(boot, 100) }

                const lat = parseFloat($wire.$get(@js($latPath)))
                const lng = parseFloat($wire.$get(@js($lngPath)))
                const hasPoint = ! isNaN(lat) && ! isNaN(lng)

                this.map = L.map(this.$refs.map).setView(hasPoint ? [lat, lng] : [-2.5, 118], hasPoint ? 12 : 5)

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(this.map)

                if (hasPoint) { this.place(lat, lng, false) }

                this.map.on('click', (e) => this.place(e.latlng.lat, e.latlng.lng, true))

                // Sinkron balik saat koordinat diketik manual
                $wire.$watch(@js($latPath), () => this.syncFromInputs())
                $wire.$watch(@js($lngPath), () => this.syncFromInputs())

                setTimeout(() => this.map.invalidateSize(), 250)
            }
            boot()
        },
        place(lat, lng, write) {
            if (! this.marker) {
                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map)
                this.marker.on('dragend', () => {
                    const p = this.marker.getLatLng()
                    this.write(p.lat, p.lng)
                })
            } else {
                this.marker.setLatLng([lat, lng])
            }
            if (write) { this.write(lat, lng) }
        },
        write(lat, lng) {
            $wire.$set(@js($latPath), Number(lat.toFixed(7)), false)
            $wire.$set(@js($lngPath), Number(lng.toFixed(7)), false)
        },
        syncFromInputs() {
            const lat = parseFloat($wire.$get(@js($latPath)))
            const lng = parseFloat($wire.$get(@js($lngPath)))
            if (isNaN(lat) || isNaN(lng) || ! this.map) { return }
            const current = this.marker?.getLatLng()
            if (current && Math.abs(current.lat - lat) < 1e-7 && Math.abs(current.lng - lng) < 1e-7) { return }
            this.place(lat, lng, false)
            this.map.setView([lat, lng], Math.max(this.map.getZoom(), 10))
        },
    }"
>
    <div x-ref="map" class="w-full overflow-hidden rounded-lg border border-gray-200" style="height: 340px; z-index: 0;"></div>
</div>
