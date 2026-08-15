'use client';

import { useEffect } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { LayoutDashboard, ShoppingBag, User, LogOut, Shield } from 'lucide-react';
import { useAuthStore } from '@/lib/stores/auth-store';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';

const navItems = [
  { href: '/dashboard', label: 'Dashboard', icon: LayoutDashboard },
  { href: '/dashboard/orders', label: 'My Orders', icon: ShoppingBag },
  { href: '/dashboard/profile', label: 'Profile', icon: User },
];

export default function DashboardLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  const router = useRouter();
  const { isAuthenticated, user, loadFromStorage } = useAuthStore();

  useEffect(() => {
    loadFromStorage();
  }, [loadFromStorage]);

  useEffect(() => {
    if (!isAuthenticated) {
      router.push('/auth/login');
    }
  }, [isAuthenticated, router]);

  if (!isAuthenticated) {
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
        <nav className="flex flex-col gap-1 p-4">
          {navItems.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                'flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground',
                'data-[active=true]:bg-accent data-[active=true]:text-accent-foreground'
              )}
            >
              <item.icon className="h-4 w-4" />
              {item.label}
            </Link>
          ))}
          {(user?.role === 'admin' || user?.role === 'manager') && (
            <Link
              href="/admin"
              className="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground"
            >
              <Shield className="h-4 w-4" />
              Admin
            </Link>
          )}
          <div className="mt-auto pt-4 border-t">
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
        </nav>
      </aside>
      <main className="flex-1 ml-64">
        <div className="container mx-auto px-4 py-8 max-w-5xl">
          {children}
        </div>
      </main>
    </div>
  );
}
