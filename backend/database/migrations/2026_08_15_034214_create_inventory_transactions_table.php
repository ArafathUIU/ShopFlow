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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('quantity_change');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->string('type', 20);
            $table->string('reason', 500)->nullable();
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->timestamps();

            $table->index(['inventory_id', 'created_at']);
            $table->index(['reference_type', 'reference_id']);
        });

        $this->addCheck(
            'inventory_transactions',
            'inventory_transactions_type_check',
            "type in ('sale', 'purchase', 'adjustment', 'reservation', 'release', 'return', 'cancellation')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
