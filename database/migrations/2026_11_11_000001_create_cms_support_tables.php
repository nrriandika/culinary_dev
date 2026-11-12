<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel pendukung CMS: resep (bahan & langkah), media, pengaturan website,
 * serta galeri foto untuk kuliner & wisata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('culinaries', function (Blueprint $table) {
            $table->jsonb('gallery')->nullable()->after('image');
        });

        Schema::table('destinations', function (Blueprint $table) {
            $table->jsonb('gallery')->nullable()->after('image');
            $table->string('opening_hours')->nullable()->after('address');
            $table->string('ticket_price')->nullable()->after('opening_hours');
            $table->string('contact')->nullable()->after('ticket_price');
        });

        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('culinary_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('servings')->nullable();      // porsi
            $table->unsignedSmallInteger('prep_minutes')->nullable();  // waktu persiapan
            $table->unsignedSmallInteger('cook_minutes')->nullable();  // waktu memasak
            $table->string('difficulty', 10)->nullable();              // mudah | sedang | sulit
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });

        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->string('name');                  // nama bahan
            $table->string('quantity', 50)->nullable(); // takaran, mis. "500" / "1/2"
            $table->string('unit', 50)->nullable();     // satuan, mis. gram, sdm, buah
            $table->string('note')->nullable();         // mis. "haluskan"
            $table->unsignedSmallInteger('sort')->default(0);
            $table->timestamps();
        });

        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort')->default(0);
            $table->text('instruction');
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('disk', 50)->default('public');
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size')->nullable(); // bytes
            $table->string('alt')->nullable();
            $table->string('folder', 50)->default('umum')->index(); // kuliner | wisata | wilayah | umum
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('media');
        Schema::dropIfExists('recipe_steps');
        Schema::dropIfExists('recipe_ingredients');
        Schema::dropIfExists('recipes');

        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn(['gallery', 'opening_hours', 'ticket_price', 'contact']);
        });

        Schema::table('culinaries', function (Blueprint $table) {
            $table->dropColumn('gallery');
        });
    }
};
