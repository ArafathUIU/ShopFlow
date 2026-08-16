import Link from 'next/link';

export function Footer() {
  return (
    <footer className="border-t border-slate-200 bg-slate-50">
      <div className="container mx-auto px-4 sm:px-6 py-12">
        <div className="grid grid-cols-1 md:grid-cols-4 gap-8">
          <div className="col-span-1 md:col-span-2">
            <Link href="/" className="flex items-center gap-2 mb-4">
              <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-600 text-white font-bold text-sm">
                S
              </div>
              <span className="text-lg font-bold tracking-tight text-slate-900">
                ShopFlow
              </span>
            </Link>
            <p className="text-sm text-slate-500 max-w-sm mb-6">
              Discover quality products with fast shipping and exceptional customer service. Shop the latest trends with confidence.
            </p>
            <div className="flex items-center gap-4">
              <div className="flex items-center gap-2 text-xs text-slate-400">
                <span className="font-medium">We accept:</span>
              </div>
              <div className="flex items-center gap-2">
                {['Visa', 'MC', 'Amex', 'PayPal'].map((card) => (
                  <span key={card} className="px-2 py-1 rounded bg-white border border-slate-200 text-xs font-medium text-slate-600">
                    {card}
                  </span>
                ))}
              </div>
            </div>
          </div>
          <div>
            <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">
              Shop
            </h3>
            <nav className="flex flex-col gap-3">
              <Link href="/products" className="text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                All Products
              </Link>
              <Link href="/products?category=1" className="text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                New Arrivals
              </Link>
              <Link href="/products?category=2" className="text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                Best Sellers
              </Link>
              <Link href="/products?category=3" className="text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                Sale Items
              </Link>
            </nav>
          </div>
          <div>
            <h3 className="text-sm font-semibold text-slate-900 uppercase tracking-wider mb-4">
              Support
            </h3>
            <nav className="flex flex-col gap-3">
              <Link href="/dashboard" className="text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                My Account
              </Link>
              <Link href="/dashboard/orders" className="text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                Order Tracking
              </Link>
              <Link href="/cart" className="text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                Shopping Cart
              </Link>
              <Link href="/auth/login" className="text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                Sign In
              </Link>
            </nav>
          </div>
        </div>
        <div className="mt-12 pt-8 border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-4">
          <p className="text-sm text-slate-500">
            &copy; {new Date().getFullYear()} ShopFlow. All rights reserved.
          </p>
          <div className="flex items-center gap-6">
            <Link href="#" className="text-xs text-slate-400 hover:text-slate-600 transition-colors">
              Privacy Policy
            </Link>
            <Link href="#" className="text-xs text-slate-400 hover:text-slate-600 transition-colors">
              Terms of Service
            </Link>
          </div>
        </div>
      </div>
    </footer>
  );
}
