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
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20)->default('shipping');
            $table->string('line1');
            $table->string('line2')->nullable();
            $table->string('city');
            $table->string('state')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2);
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'type']);
        });

        $this->addCheck('addresses', 'addresses_type_check', "type in ('shipping', 'billing')");
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};
