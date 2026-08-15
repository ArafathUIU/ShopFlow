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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 20)->default('stripe');
            $table->string('event_type', 100);
            $table->string('event_id', 255);
            $table->jsonb('payload');
            $table->string('status', 20)->default('received');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'event_id']);
            $table->index(['status', 'created_at']);
        });

        $this->addCheck(
            'webhook_events',
            'webhook_events_status_check',
            "status in ('received', 'processing', 'processed', 'failed')"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
