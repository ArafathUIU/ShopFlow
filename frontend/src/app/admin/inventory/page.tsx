'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { InventoryItem, InventoryAdjustInput } from '@/lib/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Package, AlertTriangle, Plus } from 'lucide-react';
import { cn } from '@/lib/utils';

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
        <h1 className="text-3xl font-bold tracking-tight">Inventory</h1>
        <p className="text-muted-foreground mt-1">Track and manage stock levels.</p>
      </div>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="divide-y">
              {Array.from({ length: 6 }).map((_, i) => (
                <div key={i} className="flex items-center gap-4 p-4">
                  <Skeleton className="h-4 w-32" />
                  <Skeleton className="h-4 w-24" />
                  <Skeleton className="h-4 w-16" />
                  <Skeleton className="h-6 w-20" />
                </div>
              ))}
            </div>
          ) : items.length === 0 ? (
            <div className="p-12 text-center text-muted-foreground">No inventory items found.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-muted/50">
                    <th className="text-left p-4 font-medium">Product</th>
                    <th className="text-left p-4 font-medium">SKU</th>
                    <th className="text-right p-4 font-medium">Quantity</th>
                    <th className="text-right p-4 font-medium">Reserved</th>
                    <th className="text-right p-4 font-medium">Available</th>
                    <th className="text-left p-4 font-medium">Status</th>
                    <th className="text-right p-4 font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {items.map((item) => (
                    <tr key={item.id} className={cn('hover:bg-accent/50 transition-colors', item.status === 'low_stock' || item.status === 'out_of_stock' ? 'bg-red-50/50 dark:bg-red-950/20' : '')}>
                      <td className="p-4">
                        <div className="flex items-center gap-2">
                          {(item.status === 'low_stock' || item.status === 'out_of_stock') && <AlertTriangle className="h-4 w-4 text-orange-500 shrink-0" />}
                          <span className="font-medium">{item.product_name}</span>
                        </div>
                      </td>
                      <td className="p-4 font-mono text-xs">{item.sku}</td>
                      <td className="p-4 text-right">{item.quantity}</td>
                      <td className="p-4 text-right">{item.reserved}</td>
                      <td className="p-4 text-right font-medium">{item.available}</td>
                      <td className="p-4">
                        <Badge variant={statusVariant(item.status)} className="capitalize">
                          {item.status.replace('_', ' ')}
                        </Badge>
                      </td>
                      <td className="p-4 text-right">
                        <Button size="sm" variant="outline" onClick={() => openAdjust(item)}>
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
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Adjust Inventory</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleAdjust} className="space-y-4">
            <div>
              <Label>Product</Label>
              <Input value={selectedItem?.product_name || ''} disabled />
            </div>
            <div>
              <Label htmlFor="quantity">Quantity Change</Label>
              <Input
                id="quantity"
                type="number"
                value={formData.quantity}
                onChange={(e) => setFormData({ ...formData, quantity: parseInt(e.target.value) || 0 })}
                required
              />
              <p className="text-xs text-muted-foreground mt-1">Use positive to add, negative to subtract.</p>
            </div>
            <div>
              <Label htmlFor="reason">Reason</Label>
              <Input
                id="reason"
                value={formData.reason}
                onChange={(e) => setFormData({ ...formData, reason: e.target.value })}
                required
              />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setAdjustOpen(false)}>Cancel</Button>
              <Button type="submit">Adjust</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
