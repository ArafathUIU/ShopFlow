'use client';

import Link from 'next/link';
import { ShoppingCart, Heart } from 'lucide-react';
import type { Product } from '@/lib/types';
import { useCartStore } from '@/lib/stores/cart-store';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';

interface ProductCardProps {
  product: Product;
}

export function ProductCard({ product }: ProductCardProps) {
  const addItem = useCartStore((state) => state.addItem);
  const isLoading = useCartStore((state) => state.isLoading);

  const primaryImage = product.images?.[0]?.url || '/placeholder-product.svg';
  const hasDiscount = product.is_on_sale || (product.compare_at_price !== null && product.compare_at_price !== undefined);
  const displayPrice = product.price.formatted;
  const comparePrice = product.compare_at_price?.formatted;

  const handleAddToCart = async (e: React.MouseEvent) => {
    e.preventDefault();
    try {
      await addItem(product.id, 1);
    } catch {
      // Error handled in store
    }
  };

  return (
    <div className="group relative flex flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white transition-all duration-200 hover:shadow-xl hover:shadow-slate-200/50 hover:border-slate-300">
      <Link href={`/products/${product.slug}`} className="relative aspect-[4/3] overflow-hidden bg-slate-100">
        <img
          src={primaryImage}
          alt={product.images?.[0]?.alt_text || product.name}
          className="object-cover transition-transform duration-300 group-hover:scale-105 w-full h-full"
        />
        {hasDiscount && (
          <Badge className="absolute top-3 left-3 bg-gradient-to-r from-red-600 to-red-500 text-white border-0 rounded-full px-2.5 py-1 text-xs font-bold shadow-lg">
            Sale
          </Badge>
        )}
        <div className="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition-colors duration-200" />
        <div className="absolute top-3 right-3 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
          <Button variant="ghost" size="icon" className="h-9 w-9 rounded-full bg-white/90 backdrop-blur-sm hover:bg-white shadow-sm">
            <Heart className="h-4 w-4 text-slate-600" />
          </Button>
        </div>
      </Link>
      <div className="flex flex-1 flex-col p-5 gap-3">
        <div className="flex-1">
          <p className="text-xs font-medium text-indigo-600 mb-1.5 uppercase tracking-wider">{product.category.name}</p>
          <Link href={`/products/${product.slug}`}>
            <h3 className="font-semibold leading-tight line-clamp-2 text-slate-900 hover:text-indigo-600 transition-colors">
              {product.name}
            </h3>
          </Link>
        </div>
        <div className="flex items-center gap-3">
          <span className="font-bold text-lg text-slate-900">{displayPrice}</span>
          {hasDiscount && comparePrice && (
            <span className="text-sm text-slate-400 line-through">{comparePrice}</span>
          )}
        </div>
        <Button
          className="w-full rounded-xl shadow-sm hover:shadow-md transition-all duration-200"
          onClick={handleAddToCart}
          disabled={isLoading}
        >
          <ShoppingCart className="h-4 w-4 mr-2" />
          Add to Cart
        </Button>
      </div>
    </div>
  );
}
