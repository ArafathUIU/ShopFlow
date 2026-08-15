'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { usePathname } from 'next/navigation';
import {
  LayoutDashboard,
  Package,
  FolderTree,
  Warehouse,
  ShoppingCart,
  Users,
  Store,
  LogOut,
} from 'lucide-react';
import { useAuthStore } from '@/lib/stores/auth-store';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

const navItems = [
  { href: '/admin', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/admin/products', label: 'Products', icon: Package },
  { href: '/admin/categories', label: 'Categories', icon: FolderTree },
  { href: '/admin/inventory', label: 'Inventory', icon: Warehouse },
  { href: '/admin/orders', label: 'Orders', icon: ShoppingCart },
  { href: '/admin/users', label: 'Users', icon: Users },
];

export default function AdminLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const pathname = usePathname();
  const { isAuthenticated, user, loadFromStorage } = useAuthStore();

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
    }
  }, [isAuthenticated, router, user]);

  if (!isAuthenticated || (user?.role !== 'admin' && user?.role !== 'manager')) {
    return null;
  }

  return (
    <div className="flex min-h-screen">
      <aside className="fixed inset-y-0 left-0 z-10 w-64 border-r bg-background">
        <div className="flex h-16 items-center justify-center border-b px-6">
          <Link href="/" className="text-lg font-bold tracking-tight">
            ShopFlow
          </Link>
        </div>
        <div className="p-4">
          <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-2 px-2">
            Administration
          </p>
        </div>
        <nav className="flex flex-col gap-1 px-4">
          {navItems.map((item) => {
            const isActive = pathname === item.href || (item.href !== '/admin' && pathname.startsWith(item.href));
            return (
              <Link
                key={item.href}
                href={item.href}
                className={cn(
                  'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors',
                  isActive
                    ? 'bg-accent text-accent-foreground'
                    : 'hover:bg-accent hover:text-accent-foreground text-muted-foreground'
                )}
              >
                <item.icon className="h-4 w-4" />
                {item.label}
              </Link>
            );
          })}
        </nav>
        <div className="absolute bottom-0 left-0 right-0 p-4 border-t">
          <div className="space-y-2">
            <Link href="/">
              <Button variant="ghost" className="w-full justify-start gap-3">
                <Store className="h-4 w-4" />
                Back to Store
              </Button>
            </Link>
            <Button
              variant="ghost"
              className="w-full justify-start gap-3"
              onClick={async () => {
                await useAuthStore.getState().logout();
                router.push('/auth/login');
              }}
            >
              <LogOut className="h-4 w-4" />
              Logout
            </Button>
          </div>
        </div>
      </aside>
      <main className="flex-1 ml-64">
        <div className="container mx-auto px-4 py-8 max-w-7xl">
          {children}
        </div>
      </main>
    </div>
  );
}
