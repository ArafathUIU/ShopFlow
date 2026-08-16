'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { AdminOrder } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Eye,
  XCircle,
  ChevronLeft,
  ChevronRight,
  Search,
  Filter,
} from 'lucide-react';
import { cn } from '@/lib/utils';
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

export default function AdminOrdersPage() {
  const router = useRouter();
  const { isAuthenticated, user, loadFromStorage } = useAuthStore();
  const [orders, setOrders] = useState<AdminOrder[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [paymentFilter, setPaymentFilter] = useState('');
  const [selectedOrder, setSelectedOrder] = useState<AdminOrder | null>(null);
  const [statusUpdateOpen, setStatusUpdateOpen] = useState(false);
  const [newStatus, setNewStatus] = useState('');
  const [statusReason, setStatusReason] = useState('');

  const fetchOrders = useCallback(async () => {
    setLoading(true);
    try {
      const result = await adminService.getOrders({
        search: search || undefined,
        status: statusFilter || undefined,
        payment_status: paymentFilter || undefined,
        page,
      });
      if (result) {
        setOrders(result.data);
        setLastPage(result.pagination.last_page);
      }
    } catch {
      // handle error
    } finally {
      setLoading(false);
    }
  }, [search, statusFilter, paymentFilter, page]);

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
    fetchOrders();
  }, [isAuthenticated, router, user, fetchOrders]);

  if (!isAuthenticated || (user?.role !== 'admin' && user?.role !== 'manager')) {
    return null;
  }

  const handleStatusUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedOrder) return;
    try {
      await adminService.updateOrderStatus(selectedOrder.id, { status: newStatus, reason: statusReason });
      setStatusUpdateOpen(false);
      fetchOrders();
    } catch {
      // handle error
    }
  };

  const handleCancel = async (id: number) => {
    if (!confirm('Are you sure you want to cancel this order?')) return;
    try {
      await adminService.cancelOrder(id);
      fetchOrders();
    } catch {
      // handle error
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight text-slate-900">Orders</h1>
        <p className="text-slate-500 mt-1">Manage and track customer orders.</p>
      </div>

      <Card className="border-slate-200 shadow-sm">
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
              <Input
                placeholder="Search orders..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                className="pl-9 rounded-lg"
              />
            </div>
            <div className="flex items-center gap-2">
              <Filter className="h-4 w-4 text-slate-400" />
              <select
                value={statusFilter}
                onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
                className="h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium"
              >
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
              <select
                value={paymentFilter}
                onChange={(e) => { setPaymentFilter(e.target.value); setPage(1); }}
                className="h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium"
              >
                <option value="">All Payment</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="failed">Failed</option>
                <option value="refunded">Refunded</option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      <Card className="border-slate-200 shadow-sm">
        <CardContent className="p-0">
          {loading ? (
            <div className="divide-y divide-slate-100">
              {Array.from({ length: 8 }).map((_, i) => (
                <div key={i} className="flex items-center gap-4 p-4">
                  <div className="flex-1 space-y-2">
                    <Skeleton className="h-4 w-32 rounded-lg" />
                    <Skeleton className="h-3 w-48 rounded-lg" />
                  </div>
                  <Skeleton className="h-6 w-20 rounded-lg" />
                  <Skeleton className="h-6 w-20 rounded-lg" />
                </div>
              ))}
            </div>
          ) : orders.length === 0 ? (
            <div className="p-12 text-center text-slate-500">No orders found.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-slate-200 bg-slate-50">
                    <th className="text-left p-4 font-semibold text-slate-600">Order</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Customer</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Date</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Status</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Payment</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Total</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {orders.map((order) => (
                    <tr key={order.id} className="hover:bg-slate-50 transition-colors">
                      <td className="p-4 font-medium text-slate-900">#{order.order_number}</td>
                      <td className="p-4">
                        <div>
                          <p className="font-medium text-slate-900">{order.customer_name}</p>
                          <p className="text-xs text-slate-500">{order.customer_email}</p>
                        </div>
                      </td>
                      <td className="p-4 text-slate-600">
                        {new Date(order.placed_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                      </td>
                      <td className="p-4">
                        <Badge variant={statusVariant(order.status)} className="capitalize rounded-full">
                          {order.status}
                        </Badge>
                      </td>
                      <td className="p-4">
                        <Badge variant={order.payment_status === 'paid' ? 'default' : 'secondary'} className="capitalize rounded-full">
                          {order.payment_status}
                        </Badge>
                      </td>
                      <td className="p-4 text-right font-medium text-slate-900">
                        ${(order.total / 100).toFixed(2)}
                      </td>
                      <td className="p-4 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="icon" onClick={() => setSelectedOrder(order)} className="rounded-lg hover:bg-slate-100">
                            <Eye className="h-4 w-4" />
                          </Button>
                          <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                              setSelectedOrder(order);
                              setNewStatus(order.status);
                              setStatusUpdateOpen(true);
                            }}
                            className="rounded-lg hover:bg-slate-100"
                          >
                            Status
                          </Button>
                          {order.status !== 'cancelled' && (
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-red-600 hover:text-red-700 hover:bg-red-50"
                              onClick={() => handleCancel(order.id)}
                            >
                              <XCircle className="h-4 w-4" />
                            </Button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      {lastPage > 1 && (
        <div className="flex items-center justify-center gap-3">
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)} className="rounded-lg border-slate-200 hover:bg-slate-50">
            <ChevronLeft className="h-4 w-4" /> Previous
          </Button>
          <span className="text-sm font-medium text-slate-600">Page {page} of {lastPage}</span>
          <Button variant="outline" size="sm" disabled={page >= lastPage} onClick={() => setPage((p) => p + 1)} className="rounded-lg border-slate-200 hover:bg-slate-50">
            Next <ChevronRight className="h-4 w-4" />
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
                  <p className="text-slate-500">Customer</p>
                  <p className="font-medium text-slate-900">{selectedOrder.customer_name}</p>
                  <p className="text-xs text-slate-500">{selectedOrder.customer_email}</p>
                </div>
                <div>
                  <p className="text-slate-500">Date</p>
                  <p className="font-medium text-slate-900">
                    {new Date(selectedOrder.placed_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}
                  </p>
                </div>
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
              {selectedOrder.customer_note && (
                <>
                  <Separator />
                  <div>
                    <p className="text-sm font-semibold text-slate-900">Note</p>
                    <p className="text-sm text-slate-500 mt-1">{selectedOrder.customer_note}</p>
                  </div>
                </>
              )}
            </div>
          </div>
        </div>
      )}

      <Dialog open={statusUpdateOpen} onOpenChange={setStatusUpdateOpen}>
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-900">Update Order Status</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleStatusUpdate} className="space-y-4">
            <div>
              <Label htmlFor="status" className="text-sm font-medium text-slate-700">Status</Label>
              <select
                id="status"
                value={newStatus}
                onChange={(e) => setNewStatus(e.target.value)}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
              >
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="processing">Processing</option>
                <option value="shipped">Shipped</option>
                <option value="delivered">Delivered</option>
                <option value="cancelled">Cancelled</option>
              </select>
            </div>
            <div>
              <Label htmlFor="reason" className="text-sm font-medium text-slate-700">Reason (optional)</Label>
              <Input
                id="reason"
                value={statusReason}
                onChange={(e) => setStatusReason(e.target.value)}
                className="rounded-lg"
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setStatusUpdateOpen(false)} className="rounded-lg">Cancel</Button>
              <Button type="submit" className="rounded-lg">Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}

function Separator({ className }: { className?: string }) {
  return <div className={`h-px bg-slate-200 ${className || ''}`} />;
}
