'use client';

import Link from 'next/link';
import { useState } from 'react';
import { ShoppingCart, User, Menu, X, LogOut, LayoutDashboard, Shield } from 'lucide-react';
import { useAuthStore } from '@/lib/stores/auth-store';
import { useCartStore } from '@/lib/stores/cart-store';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const navLinks = [
  { href: '/', label: 'Home' },
  { href: '/products', label: 'Products' },
  { href: '/cart', label: 'Cart' },
];

export function Header() {
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const { user, isAuthenticated, logout } = useAuthStore();
  const { itemCount } = useCartStore();

  const handleLogout = async () => {
    await logout();
  };

  return (
    <header className="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div className="container mx-auto flex h-16 items-center justify-between px-4">
        <div className="flex items-center gap-6">
          <Link href="/" className="flex items-center space-x-2">
            <span className="text-xl font-bold tracking-tight">ShopFlow</span>
          </Link>
          <nav className="hidden md:flex items-center gap-6 text-sm font-medium">
            {navLinks.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className="transition-colors hover:text-foreground/80 text-foreground/60"
              >
                {link.label}
              </Link>
            ))}
          </nav>
        </div>

        <div className="flex items-center gap-2">
          <nav className="flex items-center gap-1">
            <Link href="/cart">
              <Button variant="ghost" size="icon" className="relative">
                <ShoppingCart className="h-5 w-5" />
                {itemCount > 0 && (
                  <span className="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] font-medium text-primary-foreground">
                    {itemCount > 99 ? '99+' : itemCount}
                  </span>
                )}
              </Button>
            </Link>

            {isAuthenticated ? (
              <DropdownMenu>
                <DropdownMenuTrigger>
                  <Button variant="ghost" size="icon">
                    <User className="h-5 w-5" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" className="w-48">
                  <DropdownMenuItem>
                    <Link href="/dashboard" className="flex items-center gap-2">
                      <LayoutDashboard className="h-4 w-4" />
                      Dashboard
                    </Link>
                  </DropdownMenuItem>
                  {(user?.role === 'admin' || user?.role === 'manager') && (
                    <DropdownMenuItem>
                      <Link href="/admin" className="flex items-center gap-2">
                        <Shield className="h-4 w-4" />
                        Admin
                      </Link>
                    </DropdownMenuItem>
                  )}
                  <DropdownMenuSeparator />
                  <DropdownMenuItem onSelect={handleLogout} className="cursor-pointer">
                    <LogOut className="h-4 w-4 mr-2" />
                    Logout
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            ) : (
              <div className="hidden md:flex items-center gap-2">
                <Link href="/auth/login">
                  <Button variant="ghost" size="sm">
                    Login
                  </Button>
                </Link>
                <Link href="/auth/register">
                  <Button size="sm">Register</Button>
                </Link>
              </div>
            )}
          </nav>

          <Button
            variant="ghost"
            size="icon"
            className="md:hidden"
            onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
          >
            {mobileMenuOpen ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </Button>
        </div>
      </div>

      {mobileMenuOpen && (
        <div className="md:hidden border-t bg-background">
          <nav className="flex flex-col gap-1 p-4">
            {navLinks.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className="px-3 py-2 rounded-md text-sm font-medium hover:bg-accent transition-colors"
                onClick={() => setMobileMenuOpen(false)}
              >
                {link.label}
              </Link>
            ))}
            {isAuthenticated ? (
              <>
                <Link
                  href="/dashboard"
                  className="px-3 py-2 rounded-md text-sm font-medium hover:bg-accent transition-colors"
                  onClick={() => setMobileMenuOpen(false)}
                >
                  Dashboard
                </Link>
                {(user?.role === 'admin' || user?.role === 'manager') && (
                  <Link
                    href="/admin"
                    className="px-3 py-2 rounded-md text-sm font-medium hover:bg-accent transition-colors"
                    onClick={() => setMobileMenuOpen(false)}
                  >
                    Admin
                  </Link>
                )}
                <button
                  onClick={() => {
                    handleLogout();
                    setMobileMenuOpen(false);
                  }}
                  className="px-3 py-2 rounded-md text-sm font-medium hover:bg-accent transition-colors text-left"
                >
                  Logout
                </button>
              </>
            ) : (
              <>
                <Link
                  href="/auth/login"
                  className="px-3 py-2 rounded-md text-sm font-medium hover:bg-accent transition-colors"
                  onClick={() => setMobileMenuOpen(false)}
                >
                  Login
                </Link>
                <Link
                  href="/auth/register"
                  className="px-3 py-2 rounded-md text-sm font-medium hover:bg-accent transition-colors"
                  onClick={() => setMobileMenuOpen(false)}
                >
                  Register
                </Link>
              </>
            )}
          </nav>
        </div>
      )}
    </header>
  );
}
