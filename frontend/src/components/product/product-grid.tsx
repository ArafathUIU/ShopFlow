'use client';

import type { Product } from '@/lib/types';
import { ProductCard } from '@/components/product/product-card';
import { Skeleton } from '@/components/ui/skeleton';

interface ProductGridProps {
  products: Product[];
  loading?: boolean;
}

export function ProductGrid({ products, loading }: ProductGridProps) {
  if (loading) {
    return (
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {Array.from({ length: 8 }).map((_, i) => (
          <div key={i} className="flex flex-col gap-4">
            <Skeleton className="aspect-[4/3] w-full rounded-2xl" />
            <div className="space-y-2">
              <Skeleton className="h-3 w-16 rounded-lg" />
              <Skeleton className="h-5 w-3/4 rounded-lg" />
              <Skeleton className="h-6 w-1/3 rounded-lg" />
              <Skeleton className="h-10 w-full rounded-xl" />
            </div>
          </div>
        ))}
      </div>
    );
  }

  if ((products ?? []).length === 0) {
    return (
      <div className="text-center py-16">
        <p className="text-slate-500 text-lg">No products found.</p>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
      {(products ?? []).map((product) => (
        <ProductCard key={product.id} product={product} />
      ))}
    </div>
  );
}
