<?php

namespace App\Console\Commands;

use App\Models\Cart;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:cleanup-expired-carts {--days=30 : Number of days without updates to consider expired}')]
#[Description('Remove old abandoned carts and their items')]
class CleanupExpiredCarts extends Command
{
    public function handle(): void
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $count = Cart::query()
            ->where('updated_at', '<', $cutoff)
            ->whereDoesntHave('items')
            ->count();

        if ($count === 0) {
            $this->info('No expired carts found.');

            return;
        }

        Cart::query()
            ->where('updated_at', '<', $cutoff)
            ->whereDoesntHave('items')
            ->chunkById(100, function ($carts): void {
                foreach ($carts as $cart) {
                    DB::transaction(function () use ($cart): void {
                        $cart->items()->delete();
                        $cart->delete();
                    });
                }
            });

        $this->info("Cleaned up {$count} expired carts.");
    }
}
