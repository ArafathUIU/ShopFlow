'use client';

import { useEffect, useState } from 'react';
import Link from 'next/link';
import { Search, ArrowRight } from 'lucide-react';
import { productService } from '@/lib/services/product-service';
import type { Product, Category } from '@/lib/types';
import { Button } from '@/components/ui/button';
import { ProductGrid } from '@/components/product/product-grid';
import { Skeleton } from '@/components/ui/skeleton';

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
      <section className="relative bg-muted/50">
        <div className="container mx-auto px-4 py-24 md:py-32">
          <div className="max-w-2xl">
            <h1 className="text-4xl md:text-6xl font-bold tracking-tight mb-6">
              Discover Quality Products
            </h1>
            <p className="text-lg md:text-xl text-muted-foreground mb-8">
              Shop the latest trends with fast shipping and exceptional customer service.
            </p>
            <div className="flex flex-col sm:flex-row gap-4">
              <Link href="/products">
                <Button size="lg" className="w-full sm:w-auto">
                  Shop Now
                  <ArrowRight className="ml-2 h-4 w-4" />
                </Button>
              </Link>
              <Link href="/products">
                <Button variant="outline" size="lg" className="w-full sm:w-auto">
                  Browse Categories
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </section>

      <section className="container mx-auto px-4 py-16">
        <div className="flex items-center justify-between mb-8">
          <h2 className="text-3xl font-bold">Featured Products</h2>
          <Link href="/products">
            <Button variant="ghost">
              View All
              <ArrowRight className="ml-2 h-4 w-4" />
            </Button>
          </Link>
        </div>
        {loading ? (
          <ProductGrid products={[]} loading />
        ) : (
          <ProductGrid products={featuredProducts} />
        )}
      </section>

      <section className="container mx-auto px-4 py-16">
        <h2 className="text-3xl font-bold mb-8">Popular Categories</h2>
        {loading ? (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {Array.from({ length: 6 }).map((_, i) => (
              <Skeleton key={i} className="h-32 rounded-lg" />
            ))}
          </div>
        ) : (
          <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
            {categories.map((category) => (
              <Link
                key={category.id}
                href={`/products?category=${category.slug}`}
                className="group relative aspect-square rounded-lg overflow-hidden bg-muted flex items-end p-4 hover:ring-2 hover:ring-primary transition-all"
              >
                <div className="relative z-10">
                  <h3 className="font-semibold text-sm md:text-base">{category.name}</h3>
                </div>
                <div className="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent group-hover:from-black/80 transition-all" />
              </Link>
            ))}
          </div>
        )}
      </section>
    </div>
  );
}
