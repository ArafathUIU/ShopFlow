<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('disk', 20)->default('s3');
            $table->string('alt_text')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();

            $table->index(['product_id', 'sort_order']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX product_images_primary_unique '
            .'ON product_images (product_id) WHERE is_primary = true;'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
