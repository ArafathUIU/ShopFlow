'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ShoppingBag, Tag } from 'lucide-react';
import { useCartStore } from '@/lib/stores/cart-store';
import { CartItemRow } from '@/components/cart/cart-item';
import { CartSummary } from '@/components/cart/cart-summary';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

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
      <div className="container mx-auto px-4 sm:px-6 py-16 md:py-24">
        <div className="flex flex-col items-center justify-center text-center max-w-md mx-auto">
          <div className="h-20 w-20 rounded-full bg-slate-100 flex items-center justify-center mb-6">
            <ShoppingBag className="h-10 w-10 text-slate-400" />
          </div>
          <h1 className="text-2xl font-bold text-slate-900 mb-2">Your cart is empty</h1>
          <p className="text-slate-500 mb-8">
            Looks like you haven&apos;t added any items to your cart yet. Start shopping to fill it up!
          </p>
          <Link href="/products">
            <Button size="lg" className="rounded-xl shadow-lg shadow-indigo-600/20">
              Start Shopping
            </Button>
          </Link>
        </div>
      </div>
    );
  }

  return (
    <div className="container mx-auto px-4 sm:px-6 py-8 md:py-12">
      <div className="flex items-center justify-between mb-8">
        <div>
          <h1 className="text-3xl font-bold text-slate-900 tracking-tight">Shopping Cart</h1>
          <p className="text-slate-500 mt-1">{items.length} {items.length === 1 ? 'item' : 'items'} in your cart</p>
        </div>
      </div>
      <div className="grid lg:grid-cols-3 gap-8">
        <div className="lg:col-span-2 space-y-4">
          <div className="rounded-2xl border border-slate-200 bg-white overflow-hidden">
            {items.map((item) => (
              <CartItemRow key={item.id} item={item} />
            ))}
          </div>

          {coupon && (
            <div className="mt-4 p-4 rounded-xl border border-emerald-200 bg-emerald-50 flex items-center gap-3">
              <Tag className="h-5 w-5 text-emerald-600" />
              <div className="flex-1">
                 <p className="text-sm font-semibold text-emerald-800">
                  Coupon &quot;{coupon.code}&quot; applied!
                </p>
                <p className="text-xs text-emerald-600">
                  You saved {new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(coupon.discount_value / 100)}.
                </p>
              </div>
              <Button
                variant="ghost"
                size="sm"
                className="text-emerald-700 hover:text-emerald-800 hover:bg-emerald-100"
                onClick={removeCoupon}
              >
                Remove
              </Button>
            </div>
          )}

          {!coupon && (
            <div className="mt-4 rounded-xl border border-slate-200 bg-white p-5">
              <Label className="text-sm font-semibold text-slate-700 mb-2.5 block">Have a coupon?</Label>
              <div className="flex gap-3">
                <Input
                  placeholder="Enter coupon code"
                  value={couponCode}
                  onChange={(e) => setCouponCode(e.target.value)}
                  onKeyDown={(e) => e.key === 'Enter' && handleApplyCoupon()}
                  className="rounded-lg"
                />
                <Button onClick={handleApplyCoupon} disabled={isLoading || !couponCode.trim()} className="rounded-lg">
                  Apply
                </Button>
              </div>
              {couponError && (
                <p className="text-sm text-red-600 mt-2">{couponError}</p>
              )}
            </div>
          )}
        </div>

        <div className="lg:col-span-1">
          <div className="sticky top-24">
            <CartSummary />
          </div>
        </div>
      </div>
    </div>
  );
}
