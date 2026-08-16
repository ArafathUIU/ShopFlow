'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { InventoryItem, InventoryAdjustInput } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { AlertTriangle, Plus } from 'lucide-react';

export default function AdminInventoryPage() {
  const router = useRouter();
  const { isAuthenticated, user, loadFromStorage } = useAuthStore();
  const [items, setItems] = useState<InventoryItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [adjustOpen, setAdjustOpen] = useState(false);
  const [selectedItem, setSelectedItem] = useState<InventoryItem | null>(null);
  const [formData, setFormData] = useState<InventoryAdjustInput>({
    product_id: 0,
    quantity: 0,
    reason: '',
  });

  const fetchInventory = async () => {
    setLoading(true);
    try {
      const result = await adminService.getInventory();
      if (result) {
        setItems(result.data);
      }
    } catch {
      // handle error
    } finally {
      setLoading(false);
    }
  };

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
    fetchInventory();
  }, [isAuthenticated, router, user]);

  if (!isAuthenticated || (user?.role !== 'admin' && user?.role !== 'manager')) {
    return null;
  }

  const openAdjust = (item: InventoryItem) => {
    setSelectedItem(item);
    setFormData({ product_id: item.product_id, quantity: 0, reason: '' });
    setAdjustOpen(true);
  };

  const handleAdjust = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await adminService.adjustInventory(formData);
      setAdjustOpen(false);
      fetchInventory();
    } catch {
      // handle error
    }
  };

  const statusVariant = (status: string) => {
    switch (status) {
      case 'in_stock': return 'default';
      case 'low_stock': return 'secondary';
      case 'out_of_stock': return 'destructive';
      default: return 'secondary';
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight text-slate-900">Inventory</h1>
        <p className="text-slate-500 mt-1">Track and manage stock levels.</p>
      </div>

      <Card className="border-slate-200 shadow-sm">
        <CardContent className="p-0">
          {loading ? (
            <div className="divide-y divide-slate-100">
              {Array.from({ length: 6 }).map((_, i) => (
                <div key={i} className="flex items-center gap-4 p-4">
                  <Skeleton className="h-4 w-32 rounded-lg" />
                  <Skeleton className="h-4 w-24 rounded-lg" />
                  <Skeleton className="h-4 w-16 rounded-lg" />
                  <Skeleton className="h-6 w-20 rounded-lg" />
                </div>
              ))}
            </div>
          ) : items.length === 0 ? (
            <div className="p-12 text-center text-slate-500">No inventory items found.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-slate-200 bg-slate-50">
                    <th className="text-left p-4 font-semibold text-slate-600">Product</th>
                    <th className="text-left p-4 font-semibold text-slate-600">SKU</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Quantity</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Reserved</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Available</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Status</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {items.map((item) => (
                    <tr key={item.id} className={cn('hover:bg-slate-50 transition-colors', item.status === 'low_stock' || item.status === 'out_of_stock' ? 'bg-red-50/50' : '')}>
                      <td className="p-4">
                        <div className="flex items-center gap-2">
                          {(item.status === 'low_stock' || item.status === 'out_of_stock') && <AlertTriangle className="h-4 w-4 text-amber-500 shrink-0" />}
                          <span className="font-medium text-slate-900">{item.product_name}</span>
                        </div>
                      </td>
                      <td className="p-4 font-mono text-xs text-slate-600">{item.sku}</td>
                      <td className="p-4 text-right text-slate-600">{item.quantity}</td>
                      <td className="p-4 text-right text-slate-600">{item.reserved}</td>
                      <td className="p-4 text-right font-medium text-slate-900">{item.available}</td>
                      <td className="p-4">
                        <Badge variant={statusVariant(item.status)} className="capitalize rounded-full">
                          {item.status.replace('_', ' ')}
                        </Badge>
                      </td>
                      <td className="p-4 text-right">
                        <Button size="sm" variant="outline" onClick={() => openAdjust(item)} className="rounded-lg border-slate-200 hover:bg-slate-50">
                          <Plus className="h-4 w-4 mr-1" /> Adjust
                        </Button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </CardContent>
      </Card>

      <Dialog open={adjustOpen} onOpenChange={setAdjustOpen}>
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-900">Adjust Inventory</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleAdjust} className="space-y-4">
            <div>
              <Label className="text-sm font-medium text-slate-700">Product</Label>
              <Input value={selectedItem?.product_name || ''} disabled className="rounded-lg bg-slate-50" />
            </div>
            <div>
              <Label htmlFor="quantity" className="text-sm font-medium text-slate-700">Quantity Change</Label>
              <Input
                id="quantity"
                type="number"
                value={formData.quantity}
                onChange={(e) => setFormData({ ...formData, quantity: parseInt(e.target.value) || 0 })}
                required
                className="rounded-lg"
              />
              <p className="text-xs text-slate-500 mt-1">Use positive to add, negative to subtract.</p>
            </div>
            <div>
              <Label htmlFor="reason" className="text-sm font-medium text-slate-700">Reason</Label>
              <Input
                id="reason"
                value={formData.reason}
                onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
                required
                className="rounded-lg"
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setAdjustOpen(false)} className="rounded-lg">Cancel</Button>
              <Button type="submit" className="rounded-lg">Adjust</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
