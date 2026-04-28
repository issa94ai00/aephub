<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('home_carousel_slides', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->string('title');
            $table->string('title_en')->nullable();
            $table->text('subtitle')->nullable();
            $table->text('subtitle_en')->nullable();
            $table->string('image', 2048)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('home_carousel_slides');
    }
};
