-- =====================================================================
--  WebGIS Kuliner Nusantara — Skema database PostgreSQL (culinary_dev)
-- ---------------------------------------------------------------------
--  Isi file ini identik dengan migrasi Laravel di database/migrations.
--  Pilih SALAH SATU cara:
--    A. php artisan migrate            (disarankan)
--    B. Jalankan file ini di psql / pgAdmin / DBeaver:
--         psql -h localhost -U postgres -d culinary_dev -f database/sql/culinary_dev_schema.sql
--
--  Aman dijalankan berulang (IF NOT EXISTS). Di bagian akhir, nama migrasi
--  dicatat ke tabel "migrations" agar `php artisan migrate` tidak membuat
--  ulang tabel yang sama.
--
--  Akun admin awal TIDAK dibuat di sini (password harus di-hash bcrypt).
--  Buat lewat:  php artisan db:seed --class=AdminUserSeeder
--          atau php artisan make:filament-user
-- =====================================================================

BEGIN;

-- ---------------------------------------------------------------------
-- 0. Tabel sistem Laravel
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS migrations (
    id        serial PRIMARY KEY,
    migration varchar(255) NOT NULL,
    batch     integer NOT NULL
);

CREATE TABLE IF NOT EXISTS users (
    id                bigserial PRIMARY KEY,
    name              varchar(255) NOT NULL,
    email             varchar(255) NOT NULL,
    email_verified_at timestamp(0) without time zone NULL,
    password          varchar(255) NOT NULL,
    remember_token    varchar(100) NULL,
    created_at        timestamp(0) without time zone NULL,
    updated_at        timestamp(0) without time zone NULL,
    CONSTRAINT users_email_unique UNIQUE (email)
);

CREATE TABLE IF NOT EXISTS password_reset_tokens (
    email      varchar(255) PRIMARY KEY,
    token      varchar(255) NOT NULL,
    created_at timestamp(0) without time zone NULL
);

CREATE TABLE IF NOT EXISTS sessions (
    id            varchar(255) PRIMARY KEY,
    user_id       bigint NULL,
    ip_address    varchar(45) NULL,
    user_agent    text NULL,
    payload       text NOT NULL,
    last_activity integer NOT NULL
);
CREATE INDEX IF NOT EXISTS sessions_user_id_index       ON sessions (user_id);
CREATE INDEX IF NOT EXISTS sessions_last_activity_index ON sessions (last_activity);

CREATE TABLE IF NOT EXISTS cache (
    key        varchar(255) PRIMARY KEY,
    value      text NOT NULL,
    expiration bigint NOT NULL
);
CREATE INDEX IF NOT EXISTS cache_expiration_index ON cache (expiration);

CREATE TABLE IF NOT EXISTS cache_locks (
    key        varchar(255) PRIMARY KEY,
    owner      varchar(255) NOT NULL,
    expiration bigint NOT NULL
);
CREATE INDEX IF NOT EXISTS cache_locks_expiration_index ON cache_locks (expiration);

CREATE TABLE IF NOT EXISTS jobs (
    id           bigserial PRIMARY KEY,
    queue        varchar(255) NOT NULL,
    payload      text NOT NULL,
    attempts     smallint NOT NULL,
    reserved_at  integer NULL,
    available_at integer NOT NULL,
    created_at   integer NOT NULL
);
CREATE INDEX IF NOT EXISTS jobs_queue_index ON jobs (queue);

CREATE TABLE IF NOT EXISTS job_batches (
    id             varchar(255) PRIMARY KEY,
    name           varchar(255) NOT NULL,
    total_jobs     integer NOT NULL,
    pending_jobs   integer NOT NULL,
    failed_jobs    integer NOT NULL,
    failed_job_ids text NOT NULL,
    options        text NULL,
    cancelled_at   integer NULL,
    created_at     integer NOT NULL,
    finished_at    integer NULL
);

CREATE TABLE IF NOT EXISTS failed_jobs (
    id         bigserial PRIMARY KEY,
    uuid       varchar(255) NOT NULL,
    connection varchar(255) NOT NULL,
    queue      varchar(255) NOT NULL,
    payload    text NOT NULL,
    exception  text NOT NULL,
    failed_at  timestamp(0) without time zone NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid)
);
CREATE INDEX IF NOT EXISTS failed_jobs_connection_queue_failed_at_index ON failed_jobs (connection, queue, failed_at);

-- ---------------------------------------------------------------------
-- 1. Administrator (CMS > Administrator)
-- ---------------------------------------------------------------------
ALTER TABLE users ADD COLUMN IF NOT EXISTS role          varchar(20) NOT NULL DEFAULT 'admin'; -- super_admin | admin
ALTER TABLE users ADD COLUMN IF NOT EXISTS is_active     boolean     NOT NULL DEFAULT true;
ALTER TABLE users ADD COLUMN IF NOT EXISTS last_login_at timestamp(0) without time zone NULL;

-- ---------------------------------------------------------------------
-- 2. Wilayah (CMS > Manajemen Wilayah)
--    Kode mengikuti kode wilayah Kemendagri.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS provinces (
    id          bigserial PRIMARY KEY,
    code        varchar(2)   NOT NULL,           -- mis. '51' (Bali)
    name        varchar(255) NOT NULL,
    slug        varchar(255) NOT NULL,
    description text NULL,
    image       varchar(255) NULL,
    latitude    numeric(10, 7) NULL,             -- WGS84 / EPSG:4326
    longitude   numeric(10, 7) NULL,
    created_at  timestamp(0) without time zone NULL,
    updated_at  timestamp(0) without time zone NULL,
    CONSTRAINT provinces_code_unique UNIQUE (code),
    CONSTRAINT provinces_slug_unique UNIQUE (slug)
);

CREATE TABLE IF NOT EXISTS regencies (
    id          bigserial PRIMARY KEY,
    province_id bigint NOT NULL,
    code        varchar(4)   NOT NULL,           -- mis. '5171' (Kota Denpasar)
    type        varchar(10)  NOT NULL DEFAULT 'kabupaten', -- kabupaten | kota
    name        varchar(255) NOT NULL,
    slug        varchar(255) NOT NULL,
    description text NULL,
    image       varchar(255) NULL,
    latitude    numeric(10, 7) NULL,
    longitude   numeric(10, 7) NULL,
    created_at  timestamp(0) without time zone NULL,
    updated_at  timestamp(0) without time zone NULL,
    CONSTRAINT regencies_code_unique UNIQUE (code),
    CONSTRAINT regencies_slug_unique UNIQUE (slug),
    CONSTRAINT regencies_province_id_foreign FOREIGN KEY (province_id)
        REFERENCES provinces (id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 3. Kategori (CMS > Kategori)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id          bigserial PRIMARY KEY,
    type        varchar(20)  NOT NULL,           -- culinary | destination
    name        varchar(255) NOT NULL,
    slug        varchar(255) NOT NULL,
    description varchar(255) NULL,
    created_at  timestamp(0) without time zone NULL,
    updated_at  timestamp(0) without time zone NULL,
    CONSTRAINT categories_type_slug_unique UNIQUE (type, slug)
);

-- ---------------------------------------------------------------------
-- 4. Kuliner (CMS > Manajemen Kuliner)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS culinaries (
    id           bigserial PRIMARY KEY,
    category_id  bigint NULL,
    province_id  bigint NOT NULL,                -- asal daerah
    regency_id   bigint NULL,
    name         varchar(255) NOT NULL,
    slug         varchar(255) NOT NULL,
    summary      text NULL,
    description  text NULL,
    image        varchar(255) NULL,              -- path foto utama (storage/app/public)
    latitude     numeric(10, 7) NULL,            -- titik asal kuliner di peta
    longitude    numeric(10, 7) NULL,
    status       varchar(20) NOT NULL DEFAULT 'draft', -- draft | published
    published_at timestamp(0) without time zone NULL,
    created_at   timestamp(0) without time zone NULL,
    updated_at   timestamp(0) without time zone NULL,
    CONSTRAINT culinaries_slug_unique UNIQUE (slug),
    CONSTRAINT culinaries_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
    CONSTRAINT culinaries_province_id_foreign FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE RESTRICT,
    CONSTRAINT culinaries_regency_id_foreign  FOREIGN KEY (regency_id)  REFERENCES regencies (id)  ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS culinaries_status_index ON culinaries (status);
ALTER TABLE culinaries ADD COLUMN IF NOT EXISTS gallery jsonb NULL; -- array path foto galeri

-- ---------------------------------------------------------------------
-- 5. Wisata (CMS > Manajemen Wisata)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS destinations (
    id           bigserial PRIMARY KEY,
    category_id  bigint NULL,
    province_id  bigint NOT NULL,
    regency_id   bigint NULL,
    name         varchar(255) NOT NULL,
    slug         varchar(255) NOT NULL,
    summary      text NULL,
    description  text NULL,
    address      varchar(255) NULL,
    image        varchar(255) NULL,
    latitude     numeric(10, 7) NULL,
    longitude    numeric(10, 7) NULL,
    status       varchar(20) NOT NULL DEFAULT 'draft',
    published_at timestamp(0) without time zone NULL,
    created_at   timestamp(0) without time zone NULL,
    updated_at   timestamp(0) without time zone NULL,
    CONSTRAINT destinations_slug_unique UNIQUE (slug),
    CONSTRAINT destinations_category_id_foreign FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL,
    CONSTRAINT destinations_province_id_foreign FOREIGN KEY (province_id) REFERENCES provinces (id) ON DELETE RESTRICT,
    CONSTRAINT destinations_regency_id_foreign  FOREIGN KEY (regency_id)  REFERENCES regencies (id)  ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS destinations_status_index ON destinations (status);
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS gallery       jsonb NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS opening_hours varchar(255) NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS ticket_price  varchar(255) NULL;
ALTER TABLE destinations ADD COLUMN IF NOT EXISTS contact       varchar(255) NULL;

-- ---------------------------------------------------------------------
-- 6. Resep (CMS > Manajemen Resep): resep -> bahan & langkah
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recipes (
    id           bigserial PRIMARY KEY,
    culinary_id  bigint NOT NULL,
    title        varchar(255) NOT NULL,
    description  text NULL,
    servings     smallint NULL,                  -- porsi
    prep_minutes smallint NULL,
    cook_minutes smallint NULL,
    difficulty   varchar(10) NULL,               -- mudah | sedang | sulit
    notes        text NULL,
    status       varchar(20) NOT NULL DEFAULT 'draft',
    published_at timestamp(0) without time zone NULL,
    created_at   timestamp(0) without time zone NULL,
    updated_at   timestamp(0) without time zone NULL,
    CONSTRAINT recipes_culinary_id_foreign FOREIGN KEY (culinary_id) REFERENCES culinaries (id) ON DELETE CASCADE
);
CREATE INDEX IF NOT EXISTS recipes_status_index ON recipes (status);

CREATE TABLE IF NOT EXISTS recipe_ingredients (
    id         bigserial PRIMARY KEY,
    recipe_id  bigint NOT NULL,
    name       varchar(255) NOT NULL,            -- bahan
    quantity   varchar(50) NULL,                 -- takaran ("500", "1/2")
    unit       varchar(50) NULL,                 -- satuan (gram, sdm, buah)
    note       varchar(255) NULL,
    sort       smallint NOT NULL DEFAULT 0,
    created_at timestamp(0) without time zone NULL,
    updated_at timestamp(0) without time zone NULL,
    CONSTRAINT recipe_ingredients_recipe_id_foreign FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS recipe_steps (
    id          bigserial PRIMARY KEY,
    recipe_id   bigint NOT NULL,
    sort        smallint NOT NULL DEFAULT 0,
    instruction text NOT NULL,
    image       varchar(255) NULL,
    created_at  timestamp(0) without time zone NULL,
    updated_at  timestamp(0) without time zone NULL,
    CONSTRAINT recipe_steps_recipe_id_foreign FOREIGN KEY (recipe_id) REFERENCES recipes (id) ON DELETE CASCADE
);

-- ---------------------------------------------------------------------
-- 7. Media Manager
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS media (
    id            bigserial PRIMARY KEY,
    title         varchar(255) NOT NULL,
    disk          varchar(50)  NOT NULL DEFAULT 'public',
    path          varchar(255) NOT NULL,
    original_name varchar(255) NULL,
    mime_type     varchar(100) NULL,
    size          bigint NULL,                   -- bytes
    alt           varchar(255) NULL,
    folder        varchar(50)  NOT NULL DEFAULT 'umum', -- kuliner | wisata | wilayah | umum
    uploaded_by   bigint NULL,
    created_at    timestamp(0) without time zone NULL,
    updated_at    timestamp(0) without time zone NULL,
    CONSTRAINT media_uploaded_by_foreign FOREIGN KEY (uploaded_by) REFERENCES users (id) ON DELETE SET NULL
);
CREATE INDEX IF NOT EXISTS media_folder_index ON media (folder);

-- ---------------------------------------------------------------------
-- 8. Pengaturan Website (key-value)
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id         bigserial PRIMARY KEY,
    key        varchar(255) NOT NULL,
    value      text NULL,
    created_at timestamp(0) without time zone NULL,
    updated_at timestamp(0) without time zone NULL,
    CONSTRAINT settings_key_unique UNIQUE (key)
);

-- ---------------------------------------------------------------------
-- 9. Catat sebagai migrasi yang sudah dijalankan (sinkron dengan Laravel)
-- ---------------------------------------------------------------------
INSERT INTO migrations (migration, batch)
SELECT m.name, COALESCE((SELECT MAX(batch) FROM migrations), 0) + 1
FROM (VALUES
    ('0001_01_01_000000_create_users_table'),
    ('0001_01_01_000001_create_cache_table'),
    ('0001_01_01_000002_create_jobs_table'),
    ('2026_11_10_000001_add_admin_fields_to_users_table'),
    ('2026_11_10_000002_create_region_tables'),
    ('2026_11_10_000003_create_content_tables'),
    ('2026_11_11_000001_create_cms_support_tables')
) AS m(name)
WHERE NOT EXISTS (SELECT 1 FROM migrations x WHERE x.migration = m.name);

COMMIT;
