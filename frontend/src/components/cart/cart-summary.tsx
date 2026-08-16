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
    <div className="rounded-2xl border border-slate-200 bg-white p-6 md:p-8 space-y-5 shadow-sm">
      <h3 className="text-lg font-bold text-slate-900">Order Summary</h3>
      <div className="space-y-3">
        <div className="flex justify-between text-sm">
          <span className="text-slate-500">Subtotal</span>
          <span className="font-medium text-slate-900">{formatPrice(subtotal)}</span>
        </div>
        {discount > 0 && (
          <div className="flex justify-between text-sm">
            <span className="text-slate-500">Discount</span>
            <span className="font-medium text-emerald-600">-{formatPrice(discount)}</span>
          </div>
        )}
        <div className="flex justify-between text-base font-bold pt-3 border-t border-slate-200">
          <span>Total</span>
          <span>{formatPrice(total)}</span>
        </div>
      </div>
      <Link href="/checkout">
        <Button className="w-full rounded-xl shadow-lg shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30" size="lg">
          Proceed to Checkout
        </Button>
      </Link>
      <Link href="/products">
        <Button variant="outline" className="w-full rounded-xl border-slate-200 hover:bg-slate-50">
          Continue Shopping
        </Button>
      </Link>
    </div>
  );
}
