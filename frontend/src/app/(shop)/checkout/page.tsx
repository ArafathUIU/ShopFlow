'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { orderService } from '@/lib/services/order-service';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { CartSummary } from '@/components/cart/cart-summary';
import { useCartStore } from '@/lib/stores/cart-store';
import { Loader2, Lock } from 'lucide-react';

export default function CheckoutPage() {
  const router = useRouter();
  const { isAuthenticated } = useAuthStore();
  const { items, clear } = useCartStore();
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const [formData, setFormData] = useState({
    street: '',
    city: '',
    state: '',
    postal_code: '',
    country: '',
    customer_note: '',
  });

  useEffect(() => {
    if (!isAuthenticated) {
      router.push('/auth/login');
    }
  }, [isAuthenticated, router]);

  if (!isAuthenticated) {
    return null;
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');

    try {
      const order = await orderService.createOrder({
        shipping_address: {
          street: formData.street,
          city: formData.city,
          state: formData.state,
          postal_code: formData.postal_code,
          country: formData.country,
        },
        billing_address: {
          street: formData.street,
          city: formData.city,
          state: formData.state,
          postal_code: formData.postal_code,
          country: formData.country,
        },
        customer_note: formData.customer_note || undefined,
      });

      if (!order) {
        setError('Failed to place order. Please try again.');
        setLoading(false);
        return;
      }

      await clear();
      router.push(`/dashboard/orders/${order.id}`);
    } catch {
      setError('Failed to place order. Please try again.');
      setLoading(false);
    }
  };

  return (
    <div className="container mx-auto px-4 sm:px-6 py-8 md:py-12">
      <div className="flex items-center gap-2 mb-8">
        <Lock className="h-5 w-5 text-indigo-600" />
        <h1 className="text-3xl font-bold text-slate-900 tracking-tight">Checkout</h1>
      </div>
      <form onSubmit={handleSubmit}>
        <div className="grid lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            <div className="rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
              <h2 className="text-lg font-semibold text-slate-900 mb-6">Shipping Address</h2>
              <div className="space-y-5">
                <div>
                  <Label htmlFor="street" className="text-sm font-medium text-slate-700 mb-2 block">Street Address</Label>
                  <Input
                    id="street"
                    name="street"
                    value={formData.street}
                    onChange={handleChange}
                    required
                    className="rounded-lg"
                  />
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                  <div>
                    <Label htmlFor="city" className="text-sm font-medium text-slate-700 mb-2 block">City</Label>
                    <Input
                      id="city"
                      name="city"
                      value={formData.city}
                      onChange={handleChange}
                      required
                      className="rounded-lg"
                    />
                  </div>
                  <div>
                    <Label htmlFor="state" className="text-sm font-medium text-slate-700 mb-2 block">State</Label>
                    <Input
                      id="state"
                      name="state"
                      value={formData.state}
                      onChange={handleChange}
                      required
                      className="rounded-lg"
                    />
                  </div>
                </div>
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                  <div>
                    <Label htmlFor="postal_code" className="text-sm font-medium text-slate-700 mb-2 block">Postal Code</Label>
                    <Input
                      id="postal_code"
                      name="postal_code"
                      value={formData.postal_code}
                      onChange={handleChange}
                      required
                      className="rounded-lg"
                    />
                  </div>
                  <div>
                    <Label htmlFor="country" className="text-sm font-medium text-slate-700 mb-2 block">Country</Label>
                    <Input
                      id="country"
                      name="country"
                      value={formData.country}
                      onChange={handleChange}
                      required
                      className="rounded-lg"
                    />
                  </div>
                </div>
              </div>
            </div>

            <div className="rounded-2xl border border-slate-200 bg-white p-6 md:p-8">
              <h2 className="text-lg font-semibold text-slate-900 mb-4">Order Notes (Optional)</h2>
              <textarea
                name="customer_note"
                value={formData.customer_note}
                onChange={handleChange}
                rows={4}
                className="w-full rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm shadow-sm transition-all outline-none focus:border-indigo-600 focus:ring-2 focus:ring-indigo-600/20 resize-none"
                placeholder="Any special instructions for your order..."
              />
            </div>

            {error && (
              <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                {error}
              </div>
            )}
          </div>

          <div className="lg:col-span-1">
            <div className="sticky top-24 space-y-4">
              <CartSummary />
              <Button
                type="submit"
                className="w-full rounded-xl shadow-lg shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30"
                size="lg"
                disabled={loading || items.length === 0}
              >
                {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                Place Order
              </Button>
              <p className="text-xs text-center text-slate-500 flex items-center justify-center gap-1">
                <Lock className="h-3 w-3" />
                Secure checkout powered by ShopFlow
              </p>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
}
