'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ShoppingBag } from 'lucide-react';
import { useCartStore } from '@/lib/stores/cart-store';
import { CartItemRow } from '@/components/cart/cart-item';
import { CartSummary } from '@/components/cart/cart-summary';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';

export default function CartPage() {
  const { items, fetchCart, isLoading, coupon, applyCoupon, removeCoupon, error } = useCartStore();

  useEffect(() => {
    fetchCart();
  }, [fetchCart]);

  const [couponCode, setCouponCode] = useState('');
  const [couponError, setCouponError] = useState('');

  const handleApplyCoupon = async () => {
    if (!couponCode.trim()) return;
    setCouponError('');
    const success = await applyCoupon(couponCode.trim());
    if (!success) {
      setCouponError('Failed to apply coupon');
    }
  };

  if (!isLoading && items.length === 0) {
    return (
      <div className="container mx-auto px-4 py-16">
        <div className="flex flex-col items-center justify-center text-center">
          <ShoppingBag className="h-16 w-16 text-muted-foreground mb-4" />
          <h1 className="text-2xl font-bold mb-2">Your cart is empty</h1>
          <p className="text-muted-foreground mb-6">
            Looks like you haven&apos;t added any items to your cart yet.
          </p>
          <Link href="/products">
            <Button size="lg">Start Shopping</Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-8">Shopping Cart</h1>
      <div className="grid lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2">
          <div className="rounded-lg border bg-background">
            {items.map((item) => (
              <CartItemRow key={item.id} item={item} />
            ))}
          </div>

          {coupon && (
            <div className="mt-4 p-4 rounded-lg border bg-green-50 dark:bg-green-950">
              <p className="text-sm font-medium text-green-700 dark:text-green-300">
                Coupon &quot;{coupon.code}&quot; applied! You saved {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(coupon.discount_value / 100)}.
              </p>
              <Button
                variant="ghost"
                size="sm"
                className="mt-2 text-green-700 dark:text-green-300"
                onClick={removeCoupon}
              >
                Remove coupon
              </Button>
            </div>
          )}

          {!coupon && (
            <div className="mt-4 rounded-lg border bg-background p-4">
              <Label className="text-sm font-medium mb-2 block">Have a coupon?</Label>
              <div className="flex gap-2">
                <Input
                  placeholder="Enter coupon code"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && handleApplyCoupon()}
                />
                <Button onClick={handleApplyCoupon} disabled={isLoading || !couponCode.trim()}>
                  Apply
                </Button>
              </div>
              {couponError && (
                <p className="text-sm text-destructive mt-2">{couponError}</p>
              )}
            </div>
          )}
        </div>

        <div className="lg:col-span-1">
          <CartSummary />
        </div>
      </div>
    </div>
  );
}
