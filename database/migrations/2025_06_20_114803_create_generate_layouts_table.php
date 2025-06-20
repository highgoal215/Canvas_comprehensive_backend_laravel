<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('generate_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('content_type');
            $table->text('content_description');
            $table->string('style');
            $table->string('aspect_ratio');
            $table->text('layout_url'); // To store the generated layout/image URL
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generate_layouts');
    }
};
