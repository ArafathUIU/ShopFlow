'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { orderService } from '@/lib/services/order-service';
import type { Order } from '@/lib/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
        <h1 className="text-3xl font-bold tracking-tight">My Orders</h1>
        <p className="text-muted-foreground mt-1">View and track your order history.</p>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="divide-y">
              {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className="flex items-center justify-between p-4">
                  <div className="space-y-2">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-3 w-24" />
                  </div>
                  <Skeleton className="h-6 w-20" />
                </div>
              ))}
            </div>
          ) : orders.length === 0 ? (
            <div className="p-12 text-center">
              <p className="text-muted-foreground">No orders found.</p>
            </div>
          ) : (
            <div className="divide-y">
              {orders.map((order) => (
                <div
                  key={order.id}
                  className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 hover:bg-accent/50 transition-colors"
                >
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
                    <Button
                      variant="ghost"
                      size="icon"
                      onClick={() => setSelectedOrder(order)}
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
        <div className="flex items-center justify-center gap-2">
          <Button
            variant="outline"
            size="sm"
            disabled={page <= 1}
            onClick={() => setPage((p) => p - 1)}
          >
            <ChevronLeft className="h-4 w-4" />
            Previous
          </Button>
          <span className="text-sm text-muted-foreground">
            Page {page} of {lastPage}
          </span>
          <Button
            variant="outline"
            size="sm"
            disabled={page >= lastPage}
            onClick={() => setPage((p) => p + 1)}
          >
            Next
            <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      )}

      {selectedOrder && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80" onClick={() => setSelectedOrder(null)}>
          <div className="bg-background rounded-lg border shadow-lg max-w-lg w-full max-h-[80vh] overflow-y-auto" onClick={(e) => e.stopPropagation()}>
            <div className="flex items-center justify-between p-6 border-b">
              <h3 className="text-lg font-semibold">Order #{selectedOrder.order_number}</h3>
              <Button variant="ghost" size="icon" onClick={() => setSelectedOrder(null)}>
                <span className="sr-only">Close</span>
                <svg className="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
              </Button>
            </div>
            <div className="p-6 space-y-4">
              <div className="grid grid-cols-2 gap-4 text-sm">
                <div>
                  <p className="text-muted-foreground">Status</p>
                  <Badge variant={statusVariant(selectedOrder.status)} className="capitalize mt-1">
                    {selectedOrder.status}
                  </Badge>
                </div>
                <div>
                  <p className="text-muted-foreground">Payment</p>
                  <p className="font-medium capitalize mt-1">{selectedOrder.payment_status}</p>
                </div>
                <div>
                  <p className="text-muted-foreground">Total</p>
                  <p className="font-medium mt-1">${(selectedOrder.total / 100).toFixed(2)}</p>
                </div>
                <div>
                  <p className="text-muted-foreground">Date</p>
                  <p className="font-medium mt-1">
                    {new Date(selectedOrder.placed_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                  </p>
                </div>
              </div>
              <Separator />
              <div>
                <p className="text-sm font-medium mb-2">Items</p>
                <div className="space-y-2">
                  {selectedOrder.items.map((item) => (
                    <div key={item.id} className="flex justify-between text-sm">
                      <span>{item.product.name} x{item.quantity}</span>
                      <span className="font-medium">${(item.total / 100).toFixed(2)}</span>
                    </div>
                  ))}
                </div>
              </div>
              <Separator />
              <div className="space-y-1 text-sm">
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Subtotal</span>
                  <span>${(selectedOrder.subtotal / 100).toFixed(2)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Tax</span>
                  <span>${(selectedOrder.tax / 100).toFixed(2)}</span>
                </div>
                <div className="flex justify-between">
                  <span className="text-muted-foreground">Shipping</span>
                  <span>${(selectedOrder.shipping_fee / 100).toFixed(2)}</span>
                </div>
                <div className="flex justify-between font-semibold pt-2 border-t">
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
  return <div className={`h-px bg-border ${className || ''}`} />;
}
