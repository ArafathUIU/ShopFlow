'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { AnalyticsData } from '@/lib/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { DollarSign, ShoppingCart, Clock, Users } from 'lucide-react';

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
        <h1 className="text-3xl font-bold tracking-tight text-slate-900">Analytics</h1>
        <p className="text-slate-500 mt-1">Detailed insights into your store performance.</p>
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
      </div>

      <div className="grid gap-6 lg:grid-cols-2">
        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle className="text-slate-900">Revenue Over Time</CardTitle>
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

        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle className="text-slate-900">Order Status Breakdown</CardTitle>
            <CardDescription className="text-slate-500">Distribution of orders by status.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="space-y-3">
                {Array.from({ length: 4 }).map((_, i) => (
                  <Skeleton key={i} className="h-8 w-full rounded-lg" />
                ))}
              </div>
            ) : analytics?.order_status_breakdown && analytics.order_status_breakdown.length > 0 ? (
              <div className="space-y-3">
                {analytics.order_status_breakdown.map((item) => {
                  const maxCount = Math.max(...analytics.order_status_breakdown!.map((p) => p.count), 1);
                  const widthPercent = (item.count / maxCount) * 100;
                  return (
                    <div key={item.status} className="flex items-center gap-4">
                      <span className="text-xs text-slate-500 w-24 shrink-0 font-medium capitalize">
                        {item.status.replace('_', ' ')}
                      </span>
                      <div className="flex-1 h-10 bg-slate-100 rounded-lg overflow-hidden relative">
                        <div
                          className="h-full bg-gradient-to-r from-indigo-600 to-indigo-500 rounded-lg transition-all duration-500"
                          style={{ width: `${Math.max(widthPercent, 2)}%` }}
                        />
                      </div>
                      <span className="text-xs font-semibold text-slate-700 w-12 text-right">{item.count}</span>
                    </div>
                  );
                })}
              </div>
            ) : (
              <p className="text-sm text-slate-500 text-center py-6">No data available</p>
            )}
          </CardContent>
        </Card>
      </div>

      <div className="grid gap-6 lg:grid-cols-3">
        <Card className="border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle className="text-slate-900">Product Stats</CardTitle>
            <CardDescription className="text-slate-500">Overview of product inventory.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="space-y-3">
                {Array.from({ length: 4 }).map((_, i) => (
                  <Skeleton key={i} className="h-6 w-full rounded-lg" />
                ))}
              </div>
            ) : analytics?.product_stats ? (
              <div className="space-y-3">
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500">Total Products</span>
                  <span className="font-semibold text-slate-900">{analytics.product_stats.total_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500">Active</span>
                  <span className="font-semibold text-slate-900">{analytics.product_stats.active_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500">Inactive</span>
                  <span className="font-semibold text-slate-900">{analytics.product_stats.inactive_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500">Archived</span>
                  <span className="font-semibold text-slate-900">{analytics.product_stats.archived_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500">Low Stock</span>
                  <span className="font-semibold text-amber-600">{analytics.product_stats.low_stock_products}</span>
                </div>
                <div className="flex justify-between text-sm">
                  <span className="text-slate-500">Out of Stock</span>
                  <span className="font-semibold text-red-600">{analytics.product_stats.out_of_stock_products}</span>
                </div>
              </div>
            ) : (
              <p className="text-sm text-slate-500 text-center py-6">No data available</p>
            )}
          </CardContent>
        </Card>

        <Card className="lg:col-span-2 border-slate-200 shadow-sm">
          <CardHeader>
            <CardTitle className="text-slate-900">Customer Stats</CardTitle>
            <CardDescription className="text-slate-500">Overview of customer base.</CardDescription>
          </CardHeader>
          <CardContent>
            {loading ? (
              <div className="grid grid-cols-2 gap-4">
                {Array.from({ length: 4 }).map((_, i) => (
                  <Skeleton key={i} className="h-16 w-full rounded-xl" />
                ))}
              </div>
            ) : analytics?.customer_stats ? (
              <div className="grid grid-cols-2 gap-4">
                <div className="rounded-xl border border-slate-200 p-4">
                  <p className="text-xs text-slate-500 font-medium">Total Customers</p>
                  <p className="text-xl font-bold text-slate-900 mt-1">{analytics.customer_stats.total_customers}</p>
                </div>
                <div className="rounded-xl border border-slate-200 p-4">
                  <p className="text-xs text-slate-500 font-medium">Active Customers</p>
                  <p className="text-xl font-bold text-slate-900 mt-1">{analytics.customer_stats.active_customers}</p>
                </div>
                <div className="rounded-xl border border-slate-200 p-4">
                  <p className="text-xs text-slate-500 font-medium">Verified Customers</p>
                  <p className="text-xl font-bold text-slate-900 mt-1">{analytics.customer_stats.verified_customers}</p>
                </div>
                <div className="rounded-xl border border-slate-200 p-4">
                  <p className="text-xs text-slate-500 font-medium">New This Month</p>
                  <p className="text-xl font-bold text-slate-900 mt-1">{analytics.customer_stats.new_customers_this_month}</p>
                </div>
              </div>
            ) : (
              <p className="text-sm text-slate-500 text-center py-6">No data available</p>
            )}
          </CardContent>
        </Card>
      </div>
    </div>
  );
}
