'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { orderService } from '@/lib/services/order-service';
import type { Order } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { ChevronLeft, ChevronRight, Eye } from 'lucide-react';

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

export default function OrdersPage() {
  const router = useRouter();
  const { isAuthenticated, loadFromStorage } = useAuthStore();
  const [orders, setOrders] = useState<Order[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [selectedOrder, setSelectedOrder] = useState<Order | null>(null);

  const fetchOrders = useCallback(async (pageNum: number) => {
    setLoading(true);
    try {
      const result = await orderService.getOrders(pageNum, 20);
      if (result) {
        setOrders(result.data);
        setLastPage(result.pagination.last_page);
      }
    } catch {
      // handle error
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    loadFromStorage();
  }, [loadFromStorage]);

  useEffect(() => {
    if (!isAuthenticated) {
      router.push('/auth/login');
      return;
    }
    fetchOrders(page);
  }, [isAuthenticated, router, page, fetchOrders]);

  if (!isAuthenticated) {
    return null;
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight text-slate-900">My Orders</h1>
        <p className="text-slate-500 mt-1">View and track your order history.</p>
      </div>

      <Card className="border-slate-200 shadow-sm">
        <CardContent className="p-0">
          {loading ? (
            <div className="divide-y divide-slate-100">
              {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className="flex items-center justify-between p-4">
                  <div className="space-y-2">
                    <Skeleton className="h-4 w-32 rounded-lg" />
                    <Skeleton className="h-3 w-24 rounded-lg" />
                  </div>
                  <Skeleton className="h-6 w-20 rounded-lg" />
                </div>
              ))}
            </div>
          ) : orders.length === 0 ? (
            <div className="p-12 text-center">
              <p className="text-slate-500">No orders found.</p>
            </div>
          ) : (
            <div className="divide-y divide-slate-100">
              {orders.map((order) => (
                <div
                  key={order.id}
                  className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 hover:bg-slate-50 transition-colors"
                >
                  <div>
                    <p className="font-medium text-sm text-slate-900">#{order.order_number}</p>
                    <p className="text-xs text-slate-500 mt-0.5">
                      {new Date(order.placed_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                    </p>
                  </div>
                  <div className="flex items-center gap-3">
                    <Badge variant={statusVariant(order.status)} className="capitalize rounded-full">
                      {order.status}
                    </Badge>
                    <span className="text-sm font-medium text-slate-900">
                      ${(order.total / 100).toFixed(2)}
                    </span>
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => setSelectedOrder(order)}
                      className="rounded-lg hover:bg-slate-100"
                    >
                      <Eye className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>

      {lastPage > 1 && (
        <div className="flex items-center justify-center gap-3">
          <Button
            variant="outline"
            size="sm"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
            className="rounded-lg border-slate-200 hover:bg-slate-50"
          >
            <ChevronLeft className="h-4 w-4" />
            Previous
          </Button>
          <span className="text-sm font-medium text-slate-600">
            Page {page} of {lastPage}
          </span>
          <Button
            variant="outline"
            size="sm"
            disabled={page >= lastPage}
            onClick={() => setPage((p) => p + 1)}
            className="rounded-lg border-slate-200 hover:bg-slate-50"
          >
            Next
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      )}

      {selectedOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm" onClick={() => setSelectedOrder(null)}>
          <div className="bg-white rounded-2xl border border-slate-200 shadow-2xl max-w-lg w-full max-h-[80vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between p-6 border-b border-slate-200">
              <h3 className="text-lg font-bold text-slate-900">Order #{selectedOrder.order_number}</h3>
              <Button variant="ghost" size="icon" onClick={() => setSelectedOrder(null)} className="rounded-lg hover:bg-slate-100">
                <span className="sr-only">Close</span>
                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </Button>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="text-slate-500">Status</p>
                  <Badge variant={statusVariant(selectedOrder.status)} className="capitalize mt-1">
                    {selectedOrder.status}
                  </Badge>
                </div>
                <div>
                  <p className="text-slate-500">Payment</p>
                  <p className="font-medium text-slate-900 capitalize mt-1">{selectedOrder.payment_status}</p>
                </div>
                <div>
                  <p className="text-slate-500">Total</p>
                  <p className="font-medium text-slate-900 mt-1">${(selectedOrder.total / 100).toFixed(2)}</p>
                </div>
                <div>
                  <p className="text-slate-500">Date</p>
                  <p className="font-medium text-slate-900 mt-1">
                    {new Date(selectedOrder.placed_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                  </p>
                </div>
              </div>
              <Separator />
              <div>
                <p className="text-sm font-semibold text-slate-900 mb-2">Items</p>
                <div className="space-y-2">
                  {selectedOrder.items.map((item) => (
                    <div key={item.id} className="flex justify-between text-sm">
                      <span className="text-slate-600">{item.product.name} x{item.quantity}</span>
                      <span className="font-medium text-slate-900">${(item.total / 100).toFixed(2)}</span>
                    </div>
                  ))}
                </div>
              </div>
              <Separator />
              <div className="space-y-2 text-sm">
                <div className="flex justify-between">
                  <span className="text-slate-500">Subtotal</span>
                  <span className="text-slate-900">${(selectedOrder.subtotal / 100).toFixed(2)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-500">Tax</span>
                  <span className="text-slate-900">${(selectedOrder.tax / 100).toFixed(2)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-slate-500">Shipping</span>
                  <span className="text-slate-900">${(selectedOrder.shipping_fee / 100).toFixed(2)}</span>
                </div>
                <div className="flex justify-between font-semibold pt-2 border-t border-slate-200">
                  <span>Total</span>
                  <span>${(selectedOrder.total / 100).toFixed(2)}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

function Separator({ className }: { className?: string }) {
  return <div className={`h-px bg-slate-200 ${className || ''}`} />;
}
