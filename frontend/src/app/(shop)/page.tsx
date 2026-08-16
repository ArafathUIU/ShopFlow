'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { ArrowRight, Sparkles, Truck, Shield, RefreshCw } from 'lucide-react';
import { productService } from '@/lib/services/product-service';
import type { Product, Category } from '@/lib/types';
import { Button } from '@/components/ui/button';
import { ProductGrid } from '@/components/product/product-grid';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';

export default function HomePage() {
  const [featuredProducts, setFeaturedProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function loadData() {
      try {
        const [featured, cats] = await Promise.all([
          productService.getFeaturedProducts(),
          productService.getCategories(),
        ]);
        setFeaturedProducts(featured);
        setCategories(cats.slice(0, 6));
      } catch {
        // Error handled silently
      } finally {
        setLoading(false);
      }
    }
    loadData();
  }, []);

  return (
    <div>
      <section className="relative overflow-hidden bg-gradient-to-br from-indigo-50 via-white to-purple-50">
        <div className="absolute inset-0 bg-grid-slate-100 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))] -z-10" />
        <div className="container mx-auto px-4 sm:px-6 py-20 md:py-32">
          <div className="max-w-3xl">
            <Badge className="mb-6 bg-indigo-100 text-indigo-700 hover:bg-indigo-100 border-0 rounded-full px-3 py-1">
              <Sparkles className="h-3.5 w-3.5 mr-1.5" />
              New Collection 2026
            </Badge>
            <h1 className="text-4xl md:text-6xl lg:text-7xl font-bold tracking-tight text-slate-900 mb-6 leading-[1.1]">
              Discover Quality{' '}
              <span className="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">
                Products
              </span>
            </h1>
            <p className="text-lg md:text-xl text-slate-600 mb-8 max-w-2xl leading-relaxed">
              Shop the latest trends with fast shipping and exceptional customer service. 
              Premium quality, unbeatable prices, delivered to your doorstep.
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <Link href="/products">
                <Button size="lg" className="w-full sm:w-auto rounded-xl shadow-lg shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30">
                  Shop Now
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Button>
              </Link>
              <Link href="/products">
                <Button variant="outline" size="lg" className="w-full sm:w-auto rounded-xl border-slate-200 hover:border-slate-300">
                  Browse Categories
                </Button>
              </Link>
            </div>
          </div>
        </div>
        <div className="absolute top-1/2 right-0 -translate-y-1/2 w-1/3 h-2/3 bg-gradient-to-l from-indigo-100/50 to-transparent rounded-l-full blur-3xl" />
      </section>

      <section className="border-y border-slate-200 bg-white">
        <div className="container mx-auto px-4 sm:px-6 py-8">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-6">
            <div className="flex items-center gap-4 p-4 rounded-xl hover:bg-slate-50 transition-colors">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <Truck className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm font-semibold text-slate-900">Free Shipping</p>
                <p className="text-xs text-slate-500">On orders over $50</p>
              </div>
            </div>
            <div className="flex items-center gap-4 p-4 rounded-xl hover:bg-slate-50 transition-colors">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <Shield className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm font-semibold text-slate-900">Secure Payment</p>
                <p className="text-xs text-slate-500">100% protected</p>
              </div>
            </div>
            <div className="flex items-center gap-4 p-4 rounded-xl hover:bg-slate-50 transition-colors">
              <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                <RefreshCw className="h-6 w-6" />
              </div>
              <div>
                <p className="text-sm font-semibold text-slate-900">Easy Returns</p>
                <p className="text-xs text-slate-500">30-day policy</p>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section className="container mx-auto px-4 sm:px-6 py-16 md:py-24">
        <div className="flex items-end justify-between mb-10">
          <div>
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">Featured Products</h2>
            <p className="text-slate-500 mt-2">Handpicked selections just for you</p>
          </div>
          <Link href="/products">
            <Button variant="ghost" className="gap-1 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50">
              View All <ArrowRight className="h-4 w-4" />
            </Button>
          </Link>
        </div>
        {loading ? (
          <ProductGrid products={[]} loading />
        ) : (
          <ProductGrid products={featuredProducts} />
        )}
      </section>

      <section className="bg-slate-50 border-y border-slate-200">
        <div className="container mx-auto px-4 sm:px-6 py-16 md:py-24">
          <div className="text-center mb-10">
            <h2 className="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">Popular Categories</h2>
            <p className="text-slate-500 mt-2">Explore our wide range of products</p>
          </div>
          {loading ? (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
              {Array.from({ length: 6 }).map((_, i) => (
                <Skeleton key={i} className="h-40 rounded-2xl" />
              ))}
            </div>
          ) : (
            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
              {categories.map((category) => (
                <Link
                  key={category.id}
                  href={`/products?category=${category.slug}`}
                  className="group relative aspect-[3/4] rounded-2xl overflow-hidden bg-slate-200 flex items-end p-5 hover:ring-2 hover:ring-indigo-600 transition-all duration-200 hover:shadow-lg"
                >
                  <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent group-hover:from-black/80 transition-all" />
                  <div className="relative z-10">
                    <h3 className="font-semibold text-white text-sm md:text-base">{category.name}</h3>
                  </div>
                </Link>
              ))}
            </div>
          )}
        </div>
      </section>

      <section className="container mx-auto px-4 sm:px-6 py-16 md:py-24">
        <div className="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-3xl p-8 md:p-16 text-center relative overflow-hidden">
          <div className="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0.6))]" />
          <div className="relative z-10">
            <h2 className="text-3xl md:text-4xl font-bold text-white tracking-tight mb-4">
              Ready to start shopping?
            </h2>
            <p className="text-indigo-100 mb-8 max-w-xl mx-auto">
              Join thousands of happy customers. Get exclusive deals and updates straight to your inbox.
            </p>
            <div className="flex flex-col sm:flex-row gap-3 max-w-md mx-auto">
              <input
                type="email"
                placeholder="Enter your email"
                className="flex-1 h-12 px-4 rounded-xl bg-white/10 border border-white/20 text-white placeholder:text-indigo-200 focus:outline-none focus:ring-2 focus:ring-white/50"
              />
              <Button size="lg" className="rounded-xl bg-white text-indigo-700 hover:bg-indigo-50 shadow-lg">
                Subscribe
              </Button>
            </div>
          </div>
        </div>
      </section>
    </div>
  );
}
