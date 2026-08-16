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
    <div className="flex min-h-screen bg-slate-50">
      <aside className="fixed inset-y-0 z-10 w-64 border-r border-slate-200 bg-white">
        <div className="flex h-16 items-center justify-center border-b border-slate-200 px-6">
          <Link href="/" className="flex items-center gap-2 group">
            <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm">
              S
            </div>
            <span className="text-lg font-bold tracking-tight text-slate-900 group-hover:text-indigo-600 transition-colors">
              ShopFlow
            </span>
          </Link>
        </div>
        <nav className="flex flex-col gap-1 p-4">
          {navItems.map((item) => (
            <Link
              key={item.href}
              href={item.href}
              className={cn(
                'flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200',
                'hover:bg-slate-100 hover:text-slate-900 text-slate-600'
              )}
            >
              <item.icon className="h-4 w-4" />
              {item.label}
            </Link>
          ))}
          {(user?.role === 'admin' || user?.role === 'manager') && (
            <Link
              href="/admin"
              className="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-200 hover:bg-slate-100 hover:text-slate-900 text-slate-600"
            >
              <Shield className="h-4 w-4" />
              Admin
            </Link>
          )}
          <div className="mt-auto pt-4 border-t border-slate-200">
            <Button
              variant="ghost"
              className="w-full justify-start gap-3 rounded-xl hover:bg-slate-100"
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
        <div className="container mx-auto px-4 sm:px-6 py-8 max-w-5xl">
          {children}
        </div>
      </main>
    </div>
  );
}
