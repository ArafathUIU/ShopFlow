'use client';

import { useEffect, useState, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { useAuthStore } from '@/lib/stores/auth-store';
import { adminService } from '@/lib/services/admin-service';
import type { AdminUser, UserUpdateInput } from '@/lib/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogFooter } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
  Eye,
  ChevronLeft,
  ChevronRight,
  Search,
  UserPlus,
  UserCheck,
  UserX,
} from 'lucide-react';

export default function AdminUsersPage() {
  const router = useRouter();
  const { isAuthenticated, user, loadFromStorage } = useAuthStore();
  const [users, setUsers] = useState<AdminUser[]>([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [search, setSearch] = useState('');
  const [roleFilter, setRoleFilter] = useState('');
  const [activeFilter, setActiveFilter] = useState('');
  const [verifiedFilter, setVerifiedFilter] = useState('');
  const [editOpen, setEditOpen] = useState(false);
  const [selectedUser, setSelectedUser] = useState<AdminUser | null>(null);
  const [formData, setFormData] = useState<UserUpdateInput>({});

  const fetchUsers = useCallback(async () => {
    setLoading(true);
    try {
      const result = await adminService.getUsers({
        search: search || undefined,
        role: roleFilter || undefined,
        active: activeFilter || undefined,
        verified: verifiedFilter || undefined,
        page,
      });
      if (result) {
        setUsers(result.data);
        setLastPage(result.pagination.last_page);
      }
    } catch {
      // handle error
    } finally {
      setLoading(false);
    }
  }, [search, roleFilter, activeFilter, verifiedFilter, page]);

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
    fetchUsers();
  }, [isAuthenticated, router, user, fetchUsers]);

  if (!isAuthenticated || (user?.role !== 'admin' && user?.role !== 'manager')) {
    return null;
  }

  const openEdit = (u: AdminUser) => {
    setSelectedUser(u);
    setFormData({
      name: u.name,
      email: u.email,
      phone: u.phone,
      role: u.role,
    });
    setEditOpen(true);
  };

  const handleUpdate = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedUser) return;
    try {
      await adminService.updateUser(selectedUser.id, formData);
      setEditOpen(false);
      fetchUsers();
    } catch {
      // handle error
    }
  };

  const handleActivate = async (id: number) => {
    try {
      await adminService.activateUser(id);
      fetchUsers();
    } catch {
      // handle error
    }
  };

  const handleDeactivate = async (id: number) => {
    if (!confirm('Are you sure you want to deactivate this user?')) return;
    try {
      await adminService.deactivateUser(id);
      fetchUsers();
    } catch {
      // handle error
    }
  };

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Users</h1>
        <p className="text-muted-foreground mt-1">Manage user accounts and permissions.</p>
      </div>

      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-3">
            <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search users..."
                value={search}
                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                className="pl-9"
              />
            </div>
            <select
              value={roleFilter}
              onChange={(e) => { setRoleFilter(e.target.value); setPage(1); }}
              className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
            >
              <option value="">All Roles</option>
              <option value="customer">Customer</option>
              <option value="admin">Admin</option>
              <option value="manager">Manager</option>
            </select>
            <select
              value={activeFilter}
              onChange={(e) => { setActiveFilter(e.target.value); setPage(1); }}
              className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
            >
              <option value="">All Status</option>
              <option value="1">Active</option>
              <option value="0">Inactive</option>
            </select>
            <select
              value={verifiedFilter}
              onChange={(e) => { setVerifiedFilter(e.target.value); setPage(1); }}
              className="h-9 rounded-md border border-input bg-transparent px-3 py-1 text-sm"
            >
              <option value="">All Verified</option>
              <option value="1">Verified</option>
              <option value="0">Unverified</option>
            </select>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardContent className="p-0">
          {loading ? (
            <div className="divide-y">
              {Array.from({ length: 8 }).map((_, i) => (
                <div key={i} className="flex items-center gap-4 p-4">
                  <div className="flex-1 space-y-2">
                    <Skeleton className="h-4 w-48" />
                    <Skeleton className="h-3 w-32" />
                  </div>
                  <Skeleton className="h-6 w-16" />
                </div>
              ))}
            </div>
          ) : users.length === 0 ? (
            <div className="p-12 text-center text-muted-foreground">No users found.</div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-sm">
                <thead>
                  <tr className="border-b bg-muted/50">
                    <th className="text-left p-4 font-medium">User</th>
                    <th className="text-left p-4 font-medium">Role</th>
                    <th className="text-left p-4 font-medium">Orders</th>
                    <th className="text-left p-4 font-medium">Total Spent</th>
                    <th className="text-left p-4 font-medium">Verified</th>
                    <th className="text-left p-4 font-medium">Status</th>
                    <th className="text-right p-4 font-medium">Actions</th>
                  </tr>
                </thead>
                <tbody className="divide-y">
                  {users.map((u) => (
                    <tr key={u.id} className="hover:bg-accent/50 transition-colors">
                      <td className="p-4">
                        <div>
                          <p className="font-medium">{u.name}</p>
                          <p className="text-xs text-muted-foreground">{u.email}</p>
                        </div>
                      </td>
                      <td className="p-4">
                        <Badge variant="outline" className="capitalize">{u.role}</Badge>
                      </td>
                      <td className="p-4">{u.orders_count}</td>
                      <td className="p-4 font-medium">
                        ${(u.total_spent / 100).toFixed(2)}
                      </td>
                      <td className="p-4">
                        {u.email_verified_at ? (
                          <Badge variant="default">Yes</Badge>
                        ) : (
                          <Badge variant="secondary">No</Badge>
                        )}
                      </td>
                      <td className="p-4">
                        <Badge variant={u.is_active ? 'default' : 'destructive'} className="capitalize">
                          {u.is_active ? 'Active' : 'Inactive'}
                        </Badge>
                      </td>
                      <td className="p-4 text-right">
                        <div className="flex items-center justify-end gap-1">
                          <Button variant="ghost" size="icon" onClick={() => openEdit(u)}>
                            <Eye className="h-4 w-4" />
                          </Button>
                          {u.is_active ? (
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-destructive"
                              onClick={() => handleDeactivate(u.id)}
                            >
                              <UserX className="h-4 w-4" />
                            </Button>
                          ) : (
                            <Button
                              variant="ghost"
                              size="icon"
                              className="text-green-600"
                              onClick={() => handleActivate(u.id)}
                            >
                              <UserCheck className="h-4 w-4" />
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
        <div className="flex items-center justify-center gap-2">
          <Button variant="outline" size="sm" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>
            <ChevronLeft className="h-4 w-4" /> Previous
          </Button>
          <span className="text-sm text-muted-foreground">Page {page} of {lastPage}</span>
          <Button variant="outline" size="sm" disabled={page >= lastPage} onClick={() => setPage((p) => p + 1)}>
            Next <ChevronRight className="h-4 w-4" />
          </Button>
        </div>
      )}

      <Dialog open={editOpen} onOpenChange={setEditOpen}>
        <DialogContent className="max-w-md">
          <DialogHeader>
            <DialogTitle>Edit User</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleUpdate} className="space-y-4">
            <div>
              <Label htmlFor="name">Name</Label>
              <Input
                id="name"
                value={formData.name || ''}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                required
              />
            </div>
            <div>
              <Label htmlFor="email">Email</Label>
              <Input
                id="email"
                type="email"
                value={formData.email || ''}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                required
              />
            </div>
            <div>
              <Label htmlFor="phone">Phone</Label>
              <Input
                id="phone"
                value={formData.phone || ''}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
              />
            </div>
            <div>
              <Label htmlFor="role">Role</Label>
              <select
                id="role"
                value={formData.role || 'customer'}
                onChange={(e) => setFormData({ ...formData, role: e.target.value as 'customer' | 'admin' | 'manager' })}
                className="h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
              >
                <option value="customer">Customer</option>
                <option value="admin">Admin</option>
                <option value="manager">Manager</option>
              </select>
            </div>
            <DialogFooter>
              <Button type="button" variant="outline" onClick={() => setEditOpen(false)}>Cancel</Button>
              <Button type="submit">Update</Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>
    </div>
  );
}
