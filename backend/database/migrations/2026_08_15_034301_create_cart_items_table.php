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
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->integer('unit_price');
            $table->timestamps();

            $table->unique(['cart_id', 'product_id']);
            $table->index('product_id');
        });

        $this->addCheck('cart_items', 'cart_items_quantity_check', 'quantity > 0');
        $this->addCheck('cart_items', 'cart_items_unit_price_check', 'unit_price >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
