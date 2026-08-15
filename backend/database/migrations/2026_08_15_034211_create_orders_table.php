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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 32)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('pending');
            $table->string('payment_status', 20)->default('pending');
            $table->string('currency', 3)->default('USD');
            $table->integer('subtotal');
            $table->integer('discount')->default(0);
            $table->integer('tax')->default(0);
            $table->integer('shipping_fee')->default(0);
            $table->integer('total');
            $table->jsonb('shipping_address');
            $table->jsonb('billing_address');
            $table->text('customer_note')->nullable();
            $table->timestamp('placed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('status');
            $table->index('payment_status');
        });

        $this->addCheck(
            'orders',
            'orders_status_check',
            "status in ('pending', 'paid', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded')"
        );
        $this->addCheck('orders', 'orders_payment_status_check', "payment_status in ('pending', 'paid', 'failed', 'refunded')");
        $this->addCheck('orders', 'orders_subtotal_check', 'subtotal >= 0');
        $this->addCheck('orders', 'orders_total_check', 'total >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
