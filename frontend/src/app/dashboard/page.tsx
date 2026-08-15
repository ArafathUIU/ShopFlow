'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { useRouter } from 'next/navigation';
import { LayoutDashboard, ShoppingBag, ArrowRight } from 'lucide-react';
import { useAuthStore } from '@/lib/stores/auth-store';
import { orderService } from '@/lib/services/order-service';
import type { Order } from '@/lib/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';

export default function DashboardPage() {
  const router = useRouter();
  const { user, isAuthenticated, loadFromStorage } = useAuthStore();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadFromStorage();
  }, [loadFromStorage]);

  useEffect(() => {
    if (!isAuthenticated) {
      router.push('/auth/login');
      return;
    }
    const fetchOrders = async () => {
      try {
      const result = await orderService.getOrders(1, 5);
      if (result) {
        setOrders(result.data);
      }
      } catch {
        // handle error silently for dashboard
      } finally {
        setLoading(false);
      }
    };
    fetchOrders();
  }, [isAuthenticated, router]);

  if (!isAuthenticated) {
    return null;
  }

  const totalOrders = orders.length;

  const statusVariant = (status: string) => {
    switch (status) {
      case 'pending': return 'secondary';
      case 'paid': return 'default';
      case 'processing': return 'outline';
      case 'shipped': return 'default';
      case 'delivered': return 'default';
      case 'cancelled': return 'destructive';
      default: return 'secondary';
    }
  };

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Welcome back, {user?.name?.split(' ')[0]}</h1>
        <p className="text-muted-foreground mt-1">Here's what's happening with your account today.</p>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Orders</CardTitle>
            <ShoppingBag className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{totalOrders}</div>
            <p className="text-xs text-muted-foreground">All time orders</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Account Status</CardTitle>
            <LayoutDashboard className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold capitalize">{user?.role}</div>
            <p className="text-xs text-muted-foreground">Current role</p>
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Member Since</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {user?.created_at ? new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', year: 'numeric' }) : '-'}
            </div>
            <p className="text-xs text-muted-foreground">Account created</p>
          </CardContent>
        </Card>
      </div>

      <div className="flex items-center justify-between">
        <h2 className="text-xl font-semibold">Recent Orders</h2>
        <Button variant="ghost" size="sm" asChild>
          <Link href="/dashboard/orders" className="gap-1">
            View all <ArrowRight className="h-4 w-4" />
          </Link>
        </Button>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="p-6 space-y-4">
              {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className="flex items-center justify-between">
                  <div className="space-y-1">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-3 w-20" />
                  </div>
                  <Skeleton className="h-6 w-16" />
                </div>
              ))}
            </div>
          ) : orders.length === 0 ? (
            <div className="p-12 text-center">
              <ShoppingBag className="mx-auto h-12 w-12 text-muted-foreground mb-4" />
              <h3 className="text-lg font-medium">No orders yet</h3>
              <p className="text-muted-foreground text-sm mt-1">Start shopping to see your orders here.</p>
              <Button asChild className="mt-4">
                <Link href="/products">Browse Products</Link>
              </Button>
            </div>
          ) : (
            <div className="divide-y">
              {orders.slice(0, 5).map((order) => (
                <div key={order.id} className="flex items-center justify-between p-4 hover:bg-accent/50 transition-colors">
                  <div>
                    <p className="font-medium text-sm">#{order.order_number}</p>
                    <p className="text-xs text-muted-foreground mt-0.5">
                      {new Date(order.placed_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                    </p>
                  </div>
                  <div className="flex items-center gap-3">
                    <Badge variant={statusVariant(order.status)} className="capitalize">
                      {order.status}
                    </Badge>
                    <span className="text-sm font-medium">
                      ${(order.total / 100).toFixed(2)}
                    </span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
