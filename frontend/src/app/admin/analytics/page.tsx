'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { AnalyticsData } from '@/lib/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { DollarSign, ShoppingCart, Clock, Users, Package, TrendingUp } from 'lucide-react';

export default function AdminAnalyticsPage() {
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

  return (
    <div className="space-y-8">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Analytics</h1>
        <p className="text-muted-foreground mt-1">Detailed insights into your store performance.</p>
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
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>Revenue Over Time</CardTitle>
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

        <Card>
          <CardHeader>
            <CardTitle>Order Status Breakdown</CardTitle>
            <CardDescription>Distribution of orders by status.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="space-y-3">
                {Array.from({ length: 4 }).map((_, i) => (
                  <Skeleton key={i} className="h-8 w-full" />
                ))}
              </div>
            ) : analytics?.order_status_breakdown && analytics.order_status_breakdown.length > 0 ? (
              <div className="space-y-3">
                {analytics.order_status_breakdown.map((item) => {
                  const maxCount = Math.max(...analytics.order_status_breakdown!.map((p) => p.count), 1);
                  const widthPercent = (item.count / maxCount) * 100;
                  return (
                    <div key={item.status} className="flex items-center gap-3">
                      <span className="text-xs text-muted-foreground w-24 shrink-0 capitalize">
                        {item.status.replace('_', ' ')}
                      </span>
                      <div className="flex-1 h-8 bg-accent rounded-md overflow-hidden relative">
                        <div
                          className="h-full bg-primary/80 rounded-md transition-all"
                          style={{ width: `${Math.max(widthPercent, 2)}%` }}
                        />
                      </div>
                      <span className="text-xs font-medium w-12 text-right">{item.count}</span>
                    </div>
                  );
                })}
              </div>
            ) : (
              <p className="text-sm text-muted-foreground text-center py-6">No data available</p>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card>
          <CardHeader>
            <CardTitle>Product Stats</CardTitle>
            <CardDescription>Overview of product inventory.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="space-y-3">
                {Array.from({ length: 4 }).map((_, i) => (
                  <Skeleton key={i} className="h-6 w-full" />
                ))}
              </div>
            ) : analytics?.product_stats ? (
              <div className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Total Products</span>
                  <span className="font-medium">{analytics.product_stats.total_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Active</span>
                  <span className="font-medium">{analytics.product_stats.active_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Inactive</span>
                  <span className="font-medium">{analytics.product_stats.inactive_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Archived</span>
                  <span className="font-medium">{analytics.product_stats.archived_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Low Stock</span>
                  <span className="font-medium text-orange-600">{analytics.product_stats.low_stock_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-muted-foreground">Out of Stock</span>
                  <span className="font-medium text-red-600">{analytics.product_stats.out_of_stock_products}</span>
                </div>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground text-center py-6">No data available</p>
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-2">
          <CardHeader>
            <CardTitle>Customer Stats</CardTitle>
            <CardDescription>Overview of customer base.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="grid grid-cols-2 gap-4">
                {Array.from({ length: 4 }).map((_, i) => (
                  <Skeleton key={i} className="h-16 w-full" />
                ))}
              </div>
            ) : analytics?.customer_stats ? (
              <div className="grid grid-cols-2 gap-4">
                <div className="rounded-lg border p-4">
                  <p className="text-xs text-muted-foreground">Total Customers</p>
                  <p className="text-xl font-bold mt-1">{analytics.customer_stats.total_customers}</p>
                </div>
                <div className="rounded-lg border p-4">
                  <p className="text-xs text-muted-foreground">Active Customers</p>
                  <p className="text-xl font-bold mt-1">{analytics.customer_stats.active_customers}</p>
                </div>
                <div className="rounded-lg border p-4">
                  <p className="text-xs text-muted-foreground">Verified Customers</p>
                  <p className="text-xl font-bold mt-1">{analytics.customer_stats.verified_customers}</p>
                </div>
                <div className="rounded-lg border p-4">
                  <p className="text-xs text-muted-foreground">New This Month</p>
                  <p className="text-xl font-bold mt-1">{analytics.customer_stats.new_customers_this_month}</p>
                </div>
              </div>
            ) : (
              <p className="text-sm text-muted-foreground text-center py-6">No data available</p>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
