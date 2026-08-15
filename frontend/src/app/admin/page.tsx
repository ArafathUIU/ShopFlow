'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { DollarSign, ShoppingCart, Clock, Users } from 'lucide-react';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { AnalyticsData, AdminOrder } from '@/lib/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

export default function AdminDashboardPage() {
  const router = useRouter();
  const { isAuthenticated, user, loadFromStorage } = useAuthStore();
  const [analytics, setAnalytics] = useState<AnalyticsData | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadFromStorage();
  }, [loadFromStorage]);

  useEffect(() => {
    if (!isAuthenticated) {
      router.push('/auth/login');
      return;
    }
    if (user?.role !== 'admin' && user?.role !== 'manager') {
      router.push('/');
      return;
    }
    const fetchData = async () => {
      try {
      const data = await adminService.getAnalytics();
      if (data) {
        setAnalytics(data);
      }
      } catch {
        // handle error
      } finally {
        setLoading(false);
      }
    };
    fetchData();
  }, [isAuthenticated, router, user]);

  if (!isAuthenticated || (user?.role !== 'admin' && user?.role !== 'manager')) {
    return null;
  }

  const formatCurrency = (cents: number) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
    }).format(cents / 100);
  };

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

  const recentOrders = analytics?.recent_orders?.slice(0, 5) || [];

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Dashboard</h1>
        <p className="text-muted-foreground mt-1">Welcome back, {user?.name}. Here's your store overview.</p>
      </div>

      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Revenue</CardTitle>
            <DollarSign className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            {loading ? (
              <Skeleton className="h-8 w-24" />
            ) : (
              <div className="text-2xl font-bold">{formatCurrency(analytics?.total_revenue || 0)}</div>
            )}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Orders</CardTitle>
            <ShoppingCart className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            {loading ? (
              <Skeleton className="h-8 w-16" />
            ) : (
              <div className="text-2xl font-bold">{analytics?.total_orders || 0}</div>
            )}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending Orders</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            {loading ? (
              <Skeleton className="h-8 w-16" />
            ) : (
              <div className="text-2xl font-bold">{analytics?.pending_orders || 0}</div>
            )}
          </CardContent>
        </Card>
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Customers</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            {loading ? (
              <Skeleton className="h-8 w-16" />
            ) : (
              <div className="text-2xl font-bold">{analytics?.total_customers || 0}</div>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Top Products</CardTitle>
            <CardDescription>Best performing products by sales.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="space-y-3">
                {Array.from({ length: 5 }).map((_, i) => (
                  <Skeleton key={i} className="h-10 w-full" />
                ))}
              </div>
            ) : analytics?.top_products && analytics.top_products.length > 0 ? (
              <div className="divide-y">
                {analytics.top_products.map((product) => (
                  <div key={product.product_id} className="flex items-center justify-between py-3">
                    <div>
                      <p className="text-sm font-medium">{product.product_name}</p>
                      <p className="text-xs text-muted-foreground">{product.units_sold} units sold</p>
                    </div>
                    <p className="text-sm font-medium">{formatCurrency(product.total_sales)}</p>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground text-center py-6">No data available</p>
            )}
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Recent Orders</CardTitle>
            <CardDescription>Latest orders placed in your store.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="space-y-3">
                {Array.from({ length: 5 }).map((_, i) => (
                  <Skeleton key={i} className="h-10 w-full" />
                ))}
              </div>
            ) : recentOrders.length > 0 ? (
              <div className="divide-y">
                {recentOrders.map((order: AdminOrder) => (
                  <div key={order.id} className="flex items-center justify-between py-3">
                    <div>
                      <p className="text-sm font-medium">#{order.order_number}</p>
                      <p className="text-xs text-muted-foreground">{order.customer_name}</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge variant={statusVariant(order.status)} className="capitalize">
                        {order.status}
                      </Badge>
                      <span className="text-sm font-medium">{formatCurrency(order.total)}</span>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground text-center py-6">No orders yet</p>
            )}
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader>
          <CardTitle>Revenue Overview</CardTitle>
          <CardDescription>Daily revenue for the last 7 days.</CardDescription>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="space-y-3">
              {Array.from({ length: 7 }).map((_, i) => (
                <Skeleton key={i} className="h-12 w-full" />
              ))}
            </div>
          ) : analytics?.revenue_chart && analytics.revenue_chart.length > 0 ? (
            <div className="space-y-2">
              {analytics.revenue_chart.map((point) => {
                const maxRevenue = Math.max(...analytics.revenue_chart!.map((p) => p.revenue), 1);
                const widthPercent = (point.revenue / maxRevenue) * 100;
                return (
                  <div key={point.date} className="flex items-center gap-3">
                    <span className="text-xs text-muted-foreground w-16 shrink-0">
                      {new Date(point.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                    </span>
                    <div className="flex-1 h-8 bg-accent rounded-md overflow-hidden relative">
                      <div
                        className="h-full bg-primary/80 rounded-md transition-all"
                        style={{ width: `${Math.max(widthPercent, 2)}%` }}
                      />
                    </div>
                    <span className="text-xs font-medium w-16 text-right">
                      {formatCurrency(point.revenue)}
                    </span>
                  </div>
                );
              })}
            </div>
          ) : (
            <p className="text-sm text-muted-foreground text-center py-6">No revenue data available</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
