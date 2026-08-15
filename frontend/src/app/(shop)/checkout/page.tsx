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
import { Loader2 } from 'lucide-react';

export default function CheckoutPage() {
  const router = useRouter();
  const { isAuthenticated } = useAuthStore();
  const { items, total, clear } = useCartStore();
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
    <div className="container mx-auto px-4 py-8">
      <h1 className="text-3xl font-bold mb-8">Checkout</h1>
      <form onSubmit={handleSubmit}>
        <div className="grid lg:grid-cols-3 gap-8">
          <div className="lg:col-span-2 space-y-6">
            <div className="rounded-lg border bg-background p-6">
              <h2 className="text-lg font-semibold mb-4">Shipping Address</h2>
              <div className="space-y-4">
                <div>
                  <Label htmlFor="street">Street Address</Label>
                  <Input
                    id="street"
                    name="street"
                    value={formData.street}
                    onChange={handleChange}
                    required
                  />
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="city">City</Label>
                    <Input
                      id="city"
                      name="city"
                      value={formData.city}
                      onChange={handleChange}
                      required
                    />
                  </div>
                  <div>
                    <Label htmlFor="state">State</Label>
                    <Input
                      id="state"
                      name="state"
                      value={formData.state}
                      onChange={handleChange}
                      required
                    />
                  </div>
                </div>
                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <Label htmlFor="postal_code">Postal Code</Label>
                    <Input
                      id="postal_code"
                      name="postal_code"
                      value={formData.postal_code}
                      onChange={handleChange}
                      required
                    />
                  </div>
                  <div>
                    <Label htmlFor="country">Country</Label>
                    <Input
                      id="country"
                      name="country"
                      value={formData.country}
                      onChange={handleChange}
                      required
                    />
                  </div>
                </div>
              </div>
            </div>

            <div className="rounded-lg border bg-background p-6">
              <h2 className="text-lg font-semibold mb-4">Order Notes (Optional)</h2>
              <textarea
                name="customer_note"
                value={formData.customer_note}
                onChange={handleChange}
                rows={4}
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring"
                placeholder="Any special instructions for your order..."
              />
            </div>

            {error && (
              <div className="rounded-md border border-destructive bg-destructive/10 p-4 text-sm text-destructive">
                {error}
              </div>
            )}
          </div>

          <div className="lg:col-span-1">
            <div className="sticky top-24">
              <CartSummary />
              <div className="mt-4">
                <Button
                  type="submit"
                  className="w-full"
                  size="lg"
                  disabled={loading || items.length === 0}
                >
                  {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                  Place Order
                </Button>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  );
}
