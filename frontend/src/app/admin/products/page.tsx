'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { AdminProduct, ProductCreateInput } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import {
  Search,
  Plus,
  MoreVertical,
  Edit,
  Trash2,
  Archive,
  Undo2,
  ImagePlus,
  ChevronLeft,
  ChevronRight,
} from 'lucide-react';
import { cn } from '@/lib/utils';

const statusColors: Record<string, string> = {
  active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900 dark:text-emerald-300',
  inactive: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
  archived: 'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300',
};

export default function AdminProductsPage() {
  const router = useRouter();
  const { isAuthenticated, user, loadFromStorage } = useAuthStore();
  const [products, setProducts] = useState<AdminProduct[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [categoryFilter, setCategoryFilter] = useState('');
  const [statusFilter, setStatusFilter] = useState('');
  const [trashedOnly, setTrashedOnly] = useState(false);
  const [categories, setCategories] = useState<{ id: number; name: string }[]>([]);
  const [createOpen, setCreateOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [selectedProduct, setSelectedProduct] = useState<AdminProduct | null>(null);
  const [menuOpenId, setMenuOpenId] = useState<number | null>(null);
  const [imageUrl, setImageUrl] = useState('');
  const [imageAlt, setImageAlt] = useState('');
  const [imageProductId, setImageProductId] = useState<number | null>(null);
  const [formData, setFormData] = useState<ProductCreateInput>({
    name: '',
    description: '',
    sku: '',
    price_cents: 0,
    compare_at_price_cents: undefined,
    category_id: 0,
    status: 'active',
    is_featured: false,
  });

  const fetchProducts = useCallback(async () => {
    setLoading(true);
    try {
      const result = await adminService.getProducts({
        search: search || undefined,
        category: categoryFilter || undefined,
        status: statusFilter || undefined,
        trashed_only: trashedOnly ? true : undefined,
        page,
      });
      if (result) {
        setProducts(result.data);
        setLastPage(result.pagination.last_page);
      }
    } catch {
      // handle error
    } finally {
      setLoading(false);
    }
  }, [search, categoryFilter, statusFilter, trashedOnly, page]);

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
    fetchProducts();
  }, [isAuthenticated, router, user, fetchProducts]);

  useEffect(() => {
    const loadCategories = async () => {
      try {
        const cats = await adminService.getCategories();
        setCategories(cats.map((c) => ({ id: c.id, name: c.name })));
      } catch {
        // ignore
      }
    };
    loadCategories();
  }, []);

  if (!isAuthenticated || (user?.role !== 'admin' && user?.role !== 'manager')) {
    return null;
  }

  const openCreate = () => {
    setFormData({
      name: '',
      description: '',
      sku: '',
      price_cents: 0,
      compare_at_price_cents: undefined,
      category_id: categories[0]?.id || 0,
      status: 'active',
      is_featured: false,
    });
    setCreateOpen(true);
  };

  const openEdit = (product: AdminProduct) => {
    setSelectedProduct(product);
    setFormData({
      name: product.name,
      description: product.description,
      sku: product.sku,
      price_cents: product.price.cents,
      compare_at_price_cents: product.compare_at_price?.cents,
      category_id: product.category.id,
      status: product.status,
      is_featured: product.is_featured,
    });
    setEditOpen(true);
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await adminService.createProduct(formData);
      setCreateOpen(false);
      fetchProducts();
    } catch {
      // handle error
    }
  };

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedProduct) return;
    try {
      await adminService.updateProduct(selectedProduct.id, formData);
      setEditOpen(false);
      fetchProducts();
    } catch {
      // handle error
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this product?')) return;
    try {
      await adminService.deleteProduct(id);
      fetchProducts();
    } catch {
      // handle error
    }
  };

  const handleRestore = async (id: number) => {
    try {
      await adminService.restoreProduct(id);
      fetchProducts();
    } catch {
      // handle error
    }
  };

  const handleArchive = async (id: number) => {
    try {
      await adminService.archiveProduct(id);
      fetchProducts();
    } catch {
      // handle error
    }
  };

  const handleAttachImage = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!imageProductId || !imageUrl) return;
    try {
      await adminService.attachProductImage(imageProductId, imageUrl, imageAlt);
      setImageUrl('');
      setImageAlt('');
      setImageProductId(null);
      fetchProducts();
    } catch {
      // handle error
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-slate-900">Products</h1>
          <p className="text-slate-500 mt-1">Manage your product inventory.</p>
        </div>
        <Button onClick={openCreate} className="rounded-xl shadow-sm hover:shadow-md">
          <Plus className="h-4 w-4 mr-2" />
          Add Product
        </Button>
      </div>

      <Card className="border-slate-200 shadow-sm">
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" />
              <Input
                placeholder="Search products..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                className="pl-9 rounded-lg"
              />
            </div>
            <select
              value={categoryFilter}
              onChange={(e) => { setCategoryFilter(e.target.value); setPage(1); }}
              className="h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium"
            >
              <option value="">All Categories</option>
              {categories.map((cat) => (
                <option key={cat.id} value={cat.id}>{cat.name}</option>
              ))}
            </select>
            <select
              value={statusFilter}
              onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
              className="h-10 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium"
            >
              <option value="">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="archived">Archived</option>
            </select>
            <label className="flex items-center gap-2 text-sm cursor-pointer whitespace-nowrap">
              <input
                type="checkbox"
                checked={trashedOnly}
                onChange={(e) => { setTrashedOnly(e.target.checked); setPage(1); }}
                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
              />
              <span className="font-medium text-slate-700">Trashed only</span>
            </label>
          </div>
        </CardContent>
      </Card>

      <Card className="border-slate-200 shadow-sm">
        <CardContent className="p-0">
          {loading ? (
            <div className="divide-y divide-slate-100">
              {Array.from({ length: 8 }).map((_, i) => (
                <div key={i} className="flex items-center gap-4 p-4">
                  <Skeleton className="h-10 w-10 rounded-lg" />
                  <div className="flex-1 space-y-2">
                    <Skeleton className="h-4 w-48 rounded-lg" />
                    <Skeleton className="h-3 w-24 rounded-lg" />
                  </div>
                  <Skeleton className="h-6 w-16 rounded-lg" />
                  <Skeleton className="h-6 w-16 rounded-lg" />
                </div>
              ))}
            </div>
          ) : products.length === 0 ? (
            <div className="p-12 text-center text-slate-500">No products found.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-slate-200 bg-slate-50">
                    <th className="text-left p-4 font-semibold text-slate-600">Product</th>
                    <th className="text-left p-4 font-semibold text-slate-600">SKU</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Category</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Price</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Status</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {products.map((product) => (
                    <tr key={product.id} className="hover:bg-slate-50 transition-colors">
                      <td className="p-4">
                        <div className="flex items-center gap-3">
                          {product.images?.[0] ? (
                             <img src={product.images[0].url} alt={product.images[0].alt_text || product.name} className="h-10 w-10 rounded-lg object-cover" />
                           ) : (
                             <div className="h-10 w-10 rounded-lg bg-slate-100 flex items-center justify-center text-xs text-slate-400 font-medium">No img</div>
                           )}
                          <div>
                            <p className="font-medium text-slate-900">{product.name}</p>
                            <p className="text-xs text-slate-500">ID: {product.id}</p>
                          </div>
                        </div>
                      </td>
                      <td className="p-4 font-mono text-xs text-slate-600">{product.sku}</td>
                      <td className="p-4 text-slate-600">{product.category.name}</td>
                      <td className="p-4 text-right font-medium text-slate-900">{product.price.formatted}</td>
                      <td className="p-4">
                        <span className={cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', statusColors[product.status] || 'bg-slate-100 text-slate-700')}>
                          {product.status}
                        </span>
                      </td>
                      <td className="p-4 text-right">
                        <div className="relative inline-block">
                          <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setMenuOpenId(menuOpenId === product.id ? null : product.id)}
                            className="rounded-lg hover:bg-slate-100"
                          >
                            <MoreVertical className="h-4 w-4" />
                          </Button>
                          {menuOpenId === product.id && (
                            <div className="absolute right-0 top-full mt-1 z-20 w-48 rounded-xl border border-slate-200 bg-white shadow-lg">
                              <button
                                className="flex items-center gap-2 w-full px-3 py-2.5 text-sm hover:bg-slate-50 transition-colors"
                                onClick={() => { openEdit(product); setMenuOpenId(null); }}
                              >
                                <Edit className="h-4 w-4 text-slate-500" /> Edit
                              </button>
                              {product.status === 'archived' ? (
                                <button
                                  className="flex items-center gap-2 w-full px-3 py-2.5 text-sm hover:bg-slate-50 transition-colors"
                                  onClick={() => { handleRestore(product.id); setMenuOpenId(null); }}
                                >
                                  <Undo2 className="h-4 w-4 text-slate-500" /> Restore
                                </button>
                              ) : (
                                <button
                                  className="flex items-center gap-2 w-full px-3 py-2.5 text-sm hover:bg-slate-50 transition-colors"
                                  onClick={() => { handleArchive(product.id); setMenuOpenId(null); }}
                                >
                                  <Archive className="h-4 w-4 text-slate-500" /> Archive
                                </button>
                              )}
                              <button
                                className="flex items-center gap-2 w-full px-3 py-2.5 text-sm hover:bg-slate-50 transition-colors"
                                onClick={() => { setImageProductId(product.id); setImageUrl(''); setImageAlt(''); setMenuOpenId(null); }}
                              >
                                <ImagePlus className="h-4 w-4 text-slate-500" /> Attach Image
                              </button>
                              <button
                                className="flex items-center gap-2 w-full px-3 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                onClick={() => { handleDelete(product.id); setMenuOpenId(null); }}
                              >
                                <Trash2 className="h-4 w-4" /> Delete
                              </button>
                            </div>
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

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="max-w-lg rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-900">Create Product</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleCreate} className="space-y-4">
            <div>
              <label className="text-sm font-medium text-slate-700">Name</label>
              <Input value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} required className="rounded-lg" />
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Description</label>
              <Input value={formData.description} onChange={(e) => setFormData({ ...formData, description: e.target.value })} required className="rounded-lg" />
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">SKU</label>
              <Input value={formData.sku} onChange={(e) => setFormData({ ...formData, sku: e.target.value })} required className="rounded-lg" />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="text-sm font-medium text-slate-700">Price (cents)</label>
                <Input type="number" value={formData.price_cents} onChange={(e) => setFormData({ ...formData, price_cents: parseInt(e.target.value) || 0 })} required className="rounded-lg" />
              </div>
              <div>
                <label className="text-sm font-medium text-slate-700">Compare at Price (cents)</label>
                <Input type="number" value={formData.compare_at_price_cents || ''} onChange={(e) => setFormData({ ...formData, compare_at_price_cents: e.target.value ? parseInt(e.target.value) : undefined })} className="rounded-lg" />
              </div>
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Category</label>
              <select
                value={formData.category_id}
                onChange={(e) => setFormData({ ...formData, category_id: parseInt(e.target.value) })}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
              >
                {categories.map((cat) => (
                  <option key={cat.id} value={cat.id}>{cat.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Status</label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value as 'active' | 'inactive' })}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
              </select>
            </div>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={formData.is_featured}
                onChange={(e) => setFormData({ ...formData, is_featured: e.target.checked })}
                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
              />
              <span className="font-medium text-slate-700">Featured product</span>
            </label>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setCreateOpen(false)} className="rounded-lg">Cancel</Button>
              <Button type="submit" className="rounded-lg">Create</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={editOpen} onOpenChange={setEditOpen}>
        <DialogContent className="max-w-lg rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-900">Edit Product</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleUpdate} className="space-y-4">
            <div>
              <label className="text-sm font-medium text-slate-700">Name</label>
              <Input value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} required className="rounded-lg" />
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Description</label>
              <Input value={formData.description} onChange={(e) => setFormData({ ...formData, description: e.target.value })} required className="rounded-lg" />
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">SKU</label>
              <Input value={formData.sku} onChange={(e) => setFormData({ ...formData, sku: e.target.value })} required className="rounded-lg" />
            </div>
            <div className="grid grid-cols-2 gap-4">
              <div>
                <label className="text-sm font-medium text-slate-700">Price (cents)</label>
                <Input type="number" value={formData.price_cents} onChange={(e) => setFormData({ ...formData, price_cents: parseInt(e.target.value) || 0 })} required className="rounded-lg" />
              </div>
              <div>
                <label className="text-sm font-medium text-slate-700">Compare at Price (cents)</label>
                <Input type="number" value={formData.compare_at_price_cents || ''} onChange={(e) => setFormData({ ...formData, compare_at_price_cents: e.target.value ? parseInt(e.target.value) : undefined })} className="rounded-lg" />
              </div>
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Category</label>
              <select
                value={formData.category_id}
                onChange={(e) => setFormData({ ...formData, category_id: parseInt(e.target.value) })}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
              >
                {categories.map((cat) => (
                  <option key={cat.id} value={cat.id}>{cat.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Status</label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value as 'active' | 'inactive' | 'archived' })}
                className="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm"
              >
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="archived">Archived</option>
              </select>
            </div>
            <label className="flex items-center gap-2 text-sm">
              <input
                type="checkbox"
                checked={formData.is_featured}
                onChange={(e) => setFormData({ ...formData, is_featured: e.target.checked })}
                className="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
              />
              <span className="font-medium text-slate-700">Featured product</span>
            </label>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEditOpen(false)} className="rounded-lg">Cancel</Button>
              <Button type="submit" className="rounded-lg">Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={!!imageProductId} onOpenChange={(open) => { if (!open) setImageProductId(null); }}>
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-900">Attach Image</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleAttachImage} className="space-y-4">
            <div>
              <label className="text-sm font-medium text-slate-700">Image URL</label>
              <Input value={imageUrl} onChange={(e) => setImageUrl(e.target.value)} required className="rounded-lg" />
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Alt Text</label>
              <Input value={imageAlt} onChange={(e) => setImageAlt(e.target.value)} className="rounded-lg" />
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setImageProductId(null)} className="rounded-lg">Cancel</Button>
              <Button type="submit" className="rounded-lg">Attach</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
