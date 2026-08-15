<?php

use App\Support\Concerns\AddsCheckConstraints;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use AddsCheckConstraints;

    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('sku', 100);
            $table->integer('unit_price');
            $table->integer('quantity');
            $table->integer('total');
            $table->timestamps();

            $table->index('product_id');
        });

        $this->addCheck('order_items', 'order_items_quantity_check', 'quantity > 0');
        $this->addCheck('order_items', 'order_items_unit_price_check', 'unit_price >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
