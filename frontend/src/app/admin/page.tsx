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
        <h1 className="text-3xl font-bold tracking-tight text-slate-900">Dashboard</h1>
        <p className="text-slate-500 mt-1">Welcome back, {user?.name}. Here&apos;s your store overview.</p>
      </div>

      <div className="grid gap-5 md:grid-cols-2 lg:grid-cols-4">
        <Card className="border-slate-200 shadow-sm hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-slate-600">Total Revenue</CardTitle>
            <div className="h-10 w-10 rounded-xl bg-emerald-50 flex items-center justify-center">
              <DollarSign className="h-5 w-5 text-emerald-600" />
            </div>
          </CardHeader>
          <CardContent>
            {loading ? (
              <Skeleton className="h-8 w-24 rounded-lg" />
            ) : (
              <div className="text-2xl font-bold text-slate-900">{formatCurrency(analytics?.total_revenue || 0)}</div>
            )}
          </CardContent>
        </Card>
        <Card className="border-slate-200 shadow-sm hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-slate-600">Total Orders</CardTitle>
            <div className="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center">
              <ShoppingCart className="h-5 w-5 text-indigo-600" />
            </div>
          </CardHeader>
          <CardContent>
            {loading ? (
              <Skeleton className="h-8 w-16 rounded-lg" />
            ) : (
              <div className="text-2xl font-bold text-slate-900">{analytics?.total_orders || 0}</div>
            )}
          </CardContent>
        </Card>
        <Card className="border-slate-200 shadow-sm hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-slate-600">Pending Orders</CardTitle>
            <div className="h-10 w-10 rounded-xl bg-amber-50 flex items-center justify-center">
              <Clock className="h-5 w-5 text-amber-600" />
            </div>
          </CardHeader>
          <CardContent>
            {loading ? (
              <Skeleton className="h-8 w-16 rounded-lg" />
            ) : (
              <div className="text-2xl font-bold text-slate-900">{analytics?.pending_orders || 0}</div>
            )}
          </CardContent>
        </Card>
        <Card className="border-slate-200 shadow-sm hover:shadow-md transition-shadow">
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium text-slate-600">Total Customers</CardTitle>
            <div className="h-10 w-10 rounded-xl bg-indigo-50 flex items-center justify-center">
              <Users className="h-5 w-5 text-indigo-600" />
            </div>
          </CardHeader>
          <CardContent>
            {loading ? (
              <Skeleton className="h-8 w-16 rounded-lg" />
            ) : (
              <div className="text-2xl font-bold text-slate-900">{analytics?.total_customers || 0}</div>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle className="text-slate-900">Top Products</CardTitle>
            <CardDescription className="text-slate-500">Best performing products by sales.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="space-y-3">
                {Array.from({ length: 5 }).map((_, i) => (
                  <Skeleton key={i} className="h-10 w-full rounded-lg" />
                ))}
              </div>
            ) : analytics?.top_products && analytics.top_products.length > 0 ? (
              <div className="divide-y divide-slate-100">
                {analytics.top_products.map((product) => (
                  <div key={product.product_id} className="flex items-center justify-between py-3">
                    <div>
                      <p className="text-sm font-medium text-slate-900">{product.product_name}</p>
                      <p className="text-xs text-slate-500">{product.units_sold} units sold</p>
                    </div>
                    <p className="text-sm font-medium text-slate-900">{formatCurrency(product.total_sales)}</p>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-slate-500 text-center py-6">No data available</p>
            )}
          </CardContent>
        </Card>

        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle className="text-slate-900">Recent Orders</CardTitle>
            <CardDescription className="text-slate-500">Latest orders placed in your store.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="space-y-3">
                {Array.from({ length: 5 }).map((_, i) => (
                  <Skeleton key={i} className="h-10 w-full rounded-lg" />
                ))}
              </div>
            ) : recentOrders.length > 0 ? (
              <div className="divide-y divide-slate-100">
                {recentOrders.map((order: AdminOrder) => (
                  <div key={order.id} className="flex items-center justify-between py-3">
                    <div>
                      <p className="text-sm font-medium text-slate-900">#{order.order_number}</p>
                      <p className="text-xs text-slate-500">{order.customer_name}</p>
                    </div>
                    <div className="flex items-center gap-2">
                      <Badge variant={statusVariant(order.status)} className="capitalize rounded-full">
                        {order.status}
                      </Badge>
                      <span className="text-sm font-medium text-slate-900">{formatCurrency(order.total)}</span>
                    </div>
                  </div>
                ))}
              </div>
            ) : (
              <p className="text-sm text-slate-500 text-center py-6">No orders yet</p>
            )}
          </CardContent>
        </Card>
      </div>

      <Card className="border-slate-200 shadow-sm">
        <CardHeader>
          <CardTitle className="text-slate-900">Revenue Overview</CardTitle>
          <CardDescription className="text-slate-500">Daily revenue for the last 7 days.</CardDescription>
        </CardHeader>
        <CardContent>
          {loading ? (
            <div className="space-y-3">
              {Array.from({ length: 7 }).map((_, i) => (
                <Skeleton key={i} className="h-12 w-full rounded-lg" />
              ))}
            </div>
          ) : analytics?.revenue_chart && analytics.revenue_chart.length > 0 ? (
            <div className="space-y-3">
              {analytics.revenue_chart.map((point) => {
                const maxRevenue = Math.max(...analytics.revenue_chart!.map((p) => p.revenue), 1);
                const widthPercent = (point.revenue / maxRevenue) * 100;
                return (
                  <div key={point.date} className="flex items-center gap-4">
                    <span className="text-xs text-slate-500 w-16 shrink-0 font-medium">
                      {new Date(point.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}
                    </span>
                    <div className="flex-1 h-10 bg-slate-100 rounded-lg overflow-hidden relative">
                      <div
                        className="h-full bg-gradient-to-r from-indigo-600 to-indigo-500 rounded-lg transition-all duration-500"
                        style={{ width: `${Math.max(widthPercent, 2)}%` }}
                      />
                    </div>
                    <span className="text-xs font-semibold text-slate-700 w-20 text-right">
                      {formatCurrency(point.revenue)}
                    </span>
                  </div>
                );
              })}
            </div>
          ) : (
            <p className="text-sm text-slate-500 text-center py-6">No revenue data available</p>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
