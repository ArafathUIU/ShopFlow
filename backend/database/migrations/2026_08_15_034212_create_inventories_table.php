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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->integer('reserved_quantity')->default(0);
            $table->integer('low_stock_threshold')->default(5);
            $table->timestamps();

            $table->index('quantity');
        });

        $this->addCheck('inventories', 'inventories_quantity_check', 'quantity >= 0');
        $this->addCheck('inventories', 'inventories_reserved_quantity_check', 'reserved_quantity >= 0');
        $this->addCheck('inventories', 'inventories_low_stock_threshold_check', 'low_stock_threshold >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
