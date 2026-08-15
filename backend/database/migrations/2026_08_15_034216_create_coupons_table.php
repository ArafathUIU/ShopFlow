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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('type', 20);
            $table->integer('value');
            $table->integer('min_order_amount')->nullable();
            $table->integer('max_discount_amount')->nullable();
            $table->integer('usage_limit')->nullable();
            $table->integer('per_user_limit')->nullable();
            $table->integer('times_used')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'expires_at']);
        });

        $this->addCheck('coupons', 'coupons_type_check', "type in ('percent', 'fixed')");
        $this->addCheck('coupons', 'coupons_value_check', 'value >= 0');
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
