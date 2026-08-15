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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->restrictOnDelete();
            $table->string('provider', 20)->default('stripe');
            $table->string('provider_payment_id', 255)->nullable();
            $table->integer('amount');
            $table->string('currency', 3)->default('USD');
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->jsonb('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_payment_id']);
            $table->index('status');
        });

        $this->addCheck('payments', 'payments_status_check', "status in ('pending', 'succeeded', 'failed', 'refunded')");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
