{{-- Tema light ala geoportal: latar abu kebiruan, panel putih, aksen teal --}}
<style>
    :root {
        --geo-bg: #f2f5f7;
        --geo-surface: #ffffff;
        --geo-border: #e2e8ee;
        --geo-accent: #0aa5b8;
        --geo-accent-soft: #e6f6f8;
    }

    .fi-body {
        background-color: var(--geo-bg);
    }

    /* Topbar putih dengan garis bawah tipis */
    .fi-topbar {
        background-color: var(--geo-surface);
        border-bottom: 1px solid var(--geo-border);
        box-shadow: none;
    }

    /* Sidebar putih seperti panel layer di geoportal */
    .fi-sidebar,
    .fi-sidebar-header {
        background-color: var(--geo-surface);
    }

    .fi-sidebar {
        border-inline-end: 1px solid var(--geo-border);
    }

    .fi-sidebar-group-label {
        text-transform: uppercase;
        letter-spacing: .04em;
        font-size: .7rem;
        color: #64748b;
    }

    .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: var(--geo-accent-soft);
        box-shadow: inset 3px 0 0 var(--geo-accent);
    }

    /* Kartu/section: putih, border tipis, bayangan halus */
    .fi-section,
    .fi-ta-ctn,
    .fi-wi-stats-overview-stat {
        border: 1px solid var(--geo-border);
        box-shadow: 0 1px 2px rgb(15 23 42 / .04);
    }

    /* Kontrol peta Leaflet senada */
    .leaflet-bar a {
        color: #334155;
    }

    .leaflet-container {
        font-family: inherit;
    }
</style>
