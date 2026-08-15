<?php

namespace App\Models;

use App\Enums\WebhookEventStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['provider', 'event_type', 'event_id', 'payload', 'status', 'processed_at'])]
class WebhookEvent extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'status' => WebhookEventStatus::class,
            'processed_at' => 'datetime',
        ];
    }

    public function markProcessing(): void
    {
        $this->status = WebhookEventStatus::Processing;
        $this->save();
    }

    public function markProcessed(): void
    {
        $this->status = WebhookEventStatus::Processed;
        $this->processed_at = now();
        $this->save();
    }

    public function markFailed(): void
    {
        $this->status = WebhookEventStatus::Failed;
        $this->save();
    }
}
