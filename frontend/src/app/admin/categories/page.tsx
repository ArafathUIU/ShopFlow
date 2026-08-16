'use client';

import { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { AdminCategory, CategoryCreateInput } from '@/lib/types';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Plus, Edit, Trash2 } from 'lucide-react';

export default function AdminCategoriesPage() {
  const router = useRouter();
  const { isAuthenticated, user, loadFromStorage } = useAuthStore();
  const [categories, setCategories] = useState<AdminCategory[]>([]);
  const [loading, setLoading] = useState(true);
  const [createOpen, setCreateOpen] = useState(false);
  const [editOpen, setEditOpen] = useState(false);
  const [selectedCategory, setSelectedCategory] = useState<AdminCategory | null>(null);
  const [formData, setFormData] = useState<CategoryCreateInput>({
    name: '',
    description: '',
    status: 'active',
  });

  const fetchCategories = async () => {
    setLoading(true);
    try {
      const data = await adminService.getCategories();
      setCategories(data);
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
    fetchCategories();
  }, [isAuthenticated, router, user]);

  if (!isAuthenticated || (user?.role !== 'admin' && user?.role !== 'manager')) {
    return null;
  }

  const openCreate = () => {
    setFormData({ name: '', description: '', status: 'active' });
    setCreateOpen(true);
  };

  const openEdit = (category: AdminCategory) => {
    setSelectedCategory(category);
    setFormData({ name: category.name, description: category.description || '', status: category.status });
    setEditOpen(true);
  };

  const handleCreate = async (e: React.FormEvent) => {
    e.preventDefault();
    try {
      await adminService.createCategory(formData);
      setCreateOpen(false);
      fetchCategories();
    } catch {
      // handle error
    }
  };

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedCategory) return;
    try {
      await adminService.updateCategory(selectedCategory.id, formData);
      setEditOpen(false);
      fetchCategories();
    } catch {
      // handle error
    }
  };

  const handleDelete = async (id: number) => {
    if (!confirm('Are you sure you want to delete this category?')) return;
    try {
      await adminService.deleteCategory(id);
      fetchCategories();
    } catch {
      // handle error
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold tracking-tight text-slate-900">Categories</h1>
          <p className="text-slate-500 mt-1">Organize your products with categories.</p>
        </div>
        <Button onClick={openCreate} className="rounded-xl shadow-sm hover:shadow-md">
          <Plus className="h-4 w-4 mr-2" />
          Add Category
        </Button>
      </div>

      <Card className="border-slate-200 shadow-sm">
        <CardContent className="p-0">
          {loading ? (
            <div className="divide-y divide-slate-100">
              {Array.from({ length: 5 }).map((_, i) => (
                <div key={i} className="flex items-center gap-4 p-4">
                  <div className="flex-1 space-y-2">
                    <Skeleton className="h-4 w-48 rounded-lg" />
                    <Skeleton className="h-3 w-32 rounded-lg" />
                  </div>
                  <Skeleton className="h-6 w-16 rounded-lg" />
                </div>
              ))}
            </div>
          ) : categories.length === 0 ? (
            <div className="p-12 text-center text-slate-500">No categories found.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b border-slate-200 bg-slate-50">
                    <th className="text-left p-4 font-semibold text-slate-600">Name</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Slug</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Description</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Products</th>
                    <th className="text-left p-4 font-semibold text-slate-600">Status</th>
                    <th className="text-right p-4 font-semibold text-slate-600">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100">
                  {categories.map((category) => (
                    <tr key={category.id} className="hover:bg-slate-50 transition-colors">
                      <td className="p-4 font-medium text-slate-900">{category.name}</td>
                      <td className="p-4 font-mono text-xs text-slate-500">{category.slug}</td>
                      <td className="p-4 text-slate-500 max-w-xs truncate">{category.description || '-'}</td>
                      <td className="p-4 text-slate-600">{category.products_count}</td>
                      <td className="p-4">
                        <Badge variant={category.status === 'active' ? 'default' : 'secondary'} className="capitalize rounded-full">
                          {category.status}
                        </Badge>
                      </td>
                      <td className="p-4 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="icon" onClick={() => openEdit(category)} className="rounded-lg hover:bg-slate-100">
                            <Edit className="h-4 w-4" />
                          </Button>
                          <Button variant="ghost" size="icon" className="text-red-600 hover:text-red-700 hover:bg-red-50" onClick={() => handleDelete(category.id)}>
                            <Trash2 className="h-4 w-4" />
                          </Button>
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

      <Dialog open={createOpen} onOpenChange={setCreateOpen}>
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-900">Create Category</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleCreate} className="space-y-4">
            <div>
              <label className="text-sm font-medium text-slate-700">Name</label>
              <Input value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} required className="rounded-lg" />
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Description</label>
              <Input value={formData.description} onChange={(e) => setFormData({ ...formData, description: e.target.value })} className="rounded-lg" />
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
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setCreateOpen(false)} className="rounded-lg">Cancel</Button>
              <Button type="submit" className="rounded-lg">Create</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      <Dialog open={editOpen} onOpenChange={setEditOpen}>
        <DialogContent className="max-w-md rounded-2xl">
          <DialogHeader>
            <DialogTitle className="text-slate-900">Edit Category</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleUpdate} className="space-y-4">
            <div>
              <label className="text-sm font-medium text-slate-700">Name</label>
              <Input value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} required className="rounded-lg" />
            </div>
            <div>
              <label className="text-sm font-medium text-slate-700">Description</label>
              <Input value={formData.description} onChange={(e) => setFormData({ ...formData, description: e.target.value })} className="rounded-lg" />
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
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEditOpen(false)} className="rounded-lg">Cancel</Button>
              <Button type="submit" className="rounded-lg">Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
