import Link from 'next/link';

export function Footer() {
  return (
    <footer className="border-t bg-background">
      <div className="container mx-auto px-4 py-8">
        <div className="flex flex-col md:flex-row items-center justify-between gap-4">
          <div className="flex items-center space-x-2">
            <span className="text-lg font-bold">ShopFlow</span>
          </div>
          <nav className="flex items-center gap-6 text-sm text-muted-foreground">
            <Link href="/" className="hover:text-foreground transition-colors">
              Home
            </Link>
            <Link href="/products" className="hover:text-foreground transition-colors">
              Products
            </Link>
            <Link href="/cart" className="hover:text-foreground transition-colors">
              Cart
            </Link>
          </nav>
          <p className="text-sm text-muted-foreground">
            &copy; {new Date().getFullYear()} ShopFlow. All rights reserved.
          </p>
        </div>
      </div>
    </footer>
  );
}
