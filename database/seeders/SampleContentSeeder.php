<?php

namespace Database\Seeders;

use App\Enums\CategoryType;
use App\Enums\PublishStatus;
use App\Models\Category;
use App\Models\Culinary;
use App\Models\Destination;
use App\Models\Province;
use App\Models\Regency;
use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Data contoh untuk demo dashboard & peta.
 * Data resmi dari UGM akan diimpor melalui mekanisme impor terpisah.
 */
class SampleContentSeeder extends Seeder
{
    public function run(): void
    {
        $provinces = [
            ['12', 'Sumatera Utara', 2.1154, 99.5451],
            ['13', 'Sumatera Barat', -0.7399, 100.8000],
            ['31', 'DKI Jakarta', -6.2088, 106.8456],
            ['33', 'Jawa Tengah', -7.1510, 110.1403],
            ['34', 'DI Yogyakarta', -7.8754, 110.4262],
            ['35', 'Jawa Timur', -7.5361, 112.2384],
            ['51', 'Bali', -8.3405, 115.0920],
            ['73', 'Sulawesi Selatan', -3.6688, 119.9741],
        ];

        foreach ($provinces as [$code, $name, $lat, $lng]) {
            Province::query()->updateOrCreate(['code' => $code], [
                'name' => $name,
                'slug' => Str::slug($name),
                'latitude' => $lat,
                'longitude' => $lng,
            ]);
        }

        $regencies = [
            ['1212', '12', 'kabupaten', 'Toba', 2.3333, 99.0667],
            ['1275', '12', 'kota', 'Medan', 3.5952, 98.6722],
            ['1371', '13', 'kota', 'Padang', -0.9471, 100.4172],
            ['1375', '13', 'kota', 'Bukittinggi', -0.3051, 100.3692],
            ['3171', '31', 'kota', 'Jakarta Pusat', -6.1865, 106.8341],
            ['3308', '33', 'kabupaten', 'Magelang', -7.5961, 110.2172],
            ['3374', '33', 'kota', 'Semarang', -6.9667, 110.4167],
            ['3404', '34', 'kabupaten', 'Sleman', -7.7167, 110.3500],
            ['3471', '34', 'kota', 'Yogyakarta', -7.7956, 110.3695],
            ['3513', '35', 'kabupaten', 'Probolinggo', -7.8718, 113.4776],
            ['3578', '35', 'kota', 'Surabaya', -7.2575, 112.7521],
            ['5103', '51', 'kabupaten', 'Badung', -8.5819, 115.1771],
            ['5104', '51', 'kabupaten', 'Gianyar', -8.5446, 115.3253],
            ['7371', '73', 'kota', 'Makassar', -5.1477, 119.4327],
        ];

        foreach ($regencies as [$code, $provinceCode, $type, $name, $lat, $lng]) {
            Regency::query()->updateOrCreate(['code' => $code], [
                'province_id' => Province::query()->where('code', $provinceCode)->value('id'),
                'type' => $type,
                'name' => ($type === 'kota' ? 'Kota ' : 'Kabupaten ') . $name,
                'slug' => Str::slug(($type === 'kota' ? 'kota-' : 'kabupaten-') . $name),
                'latitude' => $lat,
                'longitude' => $lng,
            ]);
        }

        $categories = [
            [CategoryType::Culinary, 'Makanan Berat'],
            [CategoryType::Culinary, 'Jajanan & Kue'],
            [CategoryType::Culinary, 'Sup & Soto'],
            [CategoryType::Destination, 'Wisata Alam'],
            [CategoryType::Destination, 'Wisata Budaya & Sejarah'],
            [CategoryType::Destination, 'Wisata Pantai'],
        ];

        foreach ($categories as [$type, $name]) {
            Category::query()->updateOrCreate(
                ['type' => $type, 'slug' => Str::slug($name)],
                ['name' => $name],
            );
        }

        $cat = fn (CategoryType $type, string $name) => Category::query()
            ->where('type', $type)->where('slug', Str::slug($name))->value('id');
        $reg = fn (string $code) => Regency::query()->where('code', $code)->first();

        // [nama, kategori, kode kab/kota, lat, lng, status, ringkasan]
        $culinaries = [
            ['Rendang', 'Makanan Berat', '1371', -0.9471, 100.4172, PublishStatus::Published, 'Olahan daging sapi bumbu rempah yang dimasak lama hingga kering.'],
            ['Gudeg', 'Makanan Berat', '3471', -7.8014, 110.3647, PublishStatus::Published, 'Nangka muda yang dimasak dengan santan dan gula aren.'],
            ['Rawon', 'Sup & Soto', '3578', -7.2575, 112.7521, PublishStatus::Published, 'Sup daging berkuah hitam dari kluwek.'],
            ['Ayam Betutu', 'Makanan Berat', '5104', -8.5446, 115.3253, PublishStatus::Published, 'Ayam utuh berbumbu base genep yang dipanggang atau dikukus.'],
            ['Coto Makassar', 'Sup & Soto', '7371', -5.1355, 119.4238, PublishStatus::Published, 'Soto jeroan sapi dengan kuah kacang yang kaya rempah.'],
            ['Bika Ambon', 'Jajanan & Kue', '1275', 3.5952, 98.6722, PublishStatus::Draft, 'Kue berongga khas Medan dengan aroma pandan dan serai.'],
            ['Kerak Telor', 'Jajanan & Kue', '3171', -6.1754, 106.8272, PublishStatus::Published, 'Telur dan beras ketan yang dimasak di wajan tanpa minyak.'],
            ['Lumpia Semarang', 'Jajanan & Kue', '3374', -6.9667, 110.4167, PublishStatus::Draft, 'Lumpia berisi rebung, telur, dan udang.'],
        ];

        foreach ($culinaries as [$name, $category, $regencyCode, $lat, $lng, $status, $summary]) {
            $regency = $reg($regencyCode);
            Culinary::query()->updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'category_id' => $cat(CategoryType::Culinary, $category),
                'province_id' => $regency->province_id,
                'regency_id' => $regency->id,
                'summary' => $summary,
                'latitude' => $lat,
                'longitude' => $lng,
                'status' => $status,
                'published_at' => $status === PublishStatus::Published ? now() : null,
            ]);
        }

        $this->seedRendangRecipe();

        $destinations = [
            ['Jam Gadang', 'Wisata Budaya & Sejarah', '1375', -0.3051, 100.3692, PublishStatus::Published, 'Menara jam ikonik di pusat Kota Bukittinggi.'],
            ['Danau Toba', 'Wisata Alam', '1212', 2.6845, 98.8756, PublishStatus::Published, 'Danau vulkanik terbesar di Asia Tenggara.'],
            ['Monumen Nasional', 'Wisata Budaya & Sejarah', '3171', -6.1754, 106.8272, PublishStatus::Published, 'Tugu peringatan perjuangan kemerdekaan Indonesia.'],
            ['Candi Borobudur', 'Wisata Budaya & Sejarah', '3308', -7.6079, 110.2038, PublishStatus::Published, 'Candi Buddha terbesar di dunia, warisan dunia UNESCO.'],
            ['Candi Prambanan', 'Wisata Budaya & Sejarah', '3404', -7.7520, 110.4915, PublishStatus::Published, 'Kompleks candi Hindu terbesar di Indonesia.'],
            ['Gunung Bromo', 'Wisata Alam', '3513', -7.9425, 112.9530, PublishStatus::Published, 'Gunung api aktif dengan lautan pasir yang luas.'],
            ['Pantai Kuta', 'Wisata Pantai', '5103', -8.7180, 115.1686, PublishStatus::Draft, 'Pantai populer dengan panorama matahari terbenam.'],
            ['Pantai Losari', 'Wisata Pantai', '7371', -5.1440, 119.4077, PublishStatus::Published, 'Ikon pesisir Kota Makassar.'],
        ];

        foreach ($destinations as [$name, $category, $regencyCode, $lat, $lng, $status, $summary]) {
            $regency = $reg($regencyCode);
            Destination::query()->updateOrCreate(['slug' => Str::slug($name)], [
                'name' => $name,
                'category_id' => $cat(CategoryType::Destination, $category),
                'province_id' => $regency->province_id,
                'regency_id' => $regency->id,
                'summary' => $summary,
                'address' => $regency->name,
                'latitude' => $lat,
                'longitude' => $lng,
                'status' => $status,
                'published_at' => $status === PublishStatus::Published ? now() : null,
            ]);
        }

        Setting::putMany([
            'site_name' => 'WebGIS Kuliner Nusantara',
            'site_tagline' => 'Jelajahi kuliner khas dan destinasi wisata Indonesia',
            'meta_description' => 'Informasi kuliner khas, resep, dan destinasi wisata dari berbagai wilayah di Indonesia.',
        ]);
    }

    private function seedRendangRecipe(): void
    {
        $rendang = Culinary::query()->where('slug', 'rendang')->first();

        if (! $rendang || $rendang->recipes()->exists()) {
            return;
        }

        $recipe = $rendang->recipes()->create([
            'title' => 'Rendang Daging Sapi Khas Minang',
            'description' => 'Resep rendang tradisional dengan santan kental dan bumbu rempah lengkap.',
            'servings' => 6,
            'prep_minutes' => 30,
            'cook_minutes' => 240,
            'difficulty' => 'sulit',
            'status' => PublishStatus::Published,
        ]);

        $ingredients = [
            ['Daging sapi', '1', 'kg', 'potong sesuai selera'],
            ['Santan kental', '2', 'liter', 'dari 3 butir kelapa'],
            ['Cabai merah keriting', '150', 'gram', 'haluskan'],
            ['Bawang merah', '12', 'siung', 'haluskan'],
            ['Bawang putih', '8', 'siung', 'haluskan'],
            ['Jahe', '1', 'ruas', 'haluskan'],
            ['Lengkuas', '2', 'ruas', 'memarkan'],
            ['Serai', '2', 'batang', 'memarkan'],
            ['Daun jeruk', '5', 'lembar', null],
            ['Daun kunyit', '1', 'lembar', 'ikat simpul'],
            ['Garam', null, 'secukupnya', null],
        ];

        foreach ($ingredients as $i => [$name, $qty, $unit, $note]) {
            $recipe->ingredients()->create(['name' => $name, 'quantity' => $qty, 'unit' => $unit, 'note' => $note, 'sort' => $i + 1]);
        }

        $steps = [
            'Masukkan santan, bumbu halus, lengkuas, serai, daun jeruk, dan daun kunyit ke dalam wajan besar.',
            'Masak dengan api sedang sambil terus diaduk hingga santan mendidih dan mengeluarkan minyak.',
            'Masukkan potongan daging, kecilkan api, aduk sesekali agar tidak gosong.',
            'Masak selama ±4 jam hingga kuah mengering dan bumbu berwarna coklat kehitaman. Tambahkan garam.',
        ];

        foreach ($steps as $i => $instruction) {
            $recipe->steps()->create(['instruction' => $instruction, 'sort' => $i + 1]);
        }
    }
}
