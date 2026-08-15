'use client';

import Link from 'next/link';
import { useCartStore } from '@/lib/stores/cart-store';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

function formatPrice(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(cents / 100);
}

export function CartSummary() {
  const { subtotal, discount, total, itemCount } = useCartStore();

  if (itemCount === 0) return null;

  return (
    <div className="rounded-lg border bg-background p-6 space-y-4">
      <h3 className="text-lg font-semibold">Order Summary</h3>
      <div className="space-y-2">
        <div className="flex justify-between text-sm">
          <span className="text-muted-foreground">Subtotal</span>
          <span>{formatPrice(subtotal)}</span>
        </div>
        {discount > 0 && (
          <div className="flex justify-between text-sm">
            <span className="text-muted-foreground">Discount</span>
            <span className="text-green-600">-{formatPrice(discount)}</span>
          </div>
        )}
        <div className="flex justify-between text-base font-semibold pt-2 border-t">
          <span>Total</span>
          <span>{formatPrice(total)}</span>
        </div>
      </div>
      <Link href="/checkout">
        <Button className="w-full" size="lg">
          Proceed to Checkout
        </Button>
      </Link>
      <Link href="/products">
        <Button variant="outline" className="w-full">
          Continue Shopping
        </Button>
      </Link>
    </div>
  );
}
