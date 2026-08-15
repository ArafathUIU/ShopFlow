'use client';

import Link from 'next/link';
import { ShoppingCart } from 'lucide-react';
import type { Product } from '@/lib/types';
import { useCartStore } from '@/lib/stores/cart-store';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

interface ProductCardProps {
  product: Product;
}

export function ProductCard({ product }: ProductCardProps) {
  const addItem = useCartStore((state) => state.addItem);
  const isLoading = useCartStore((state) => state.isLoading);

  const primaryImage = product.primary_image?.url || product.images?.[0]?.url || '/placeholder-product.svg';
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
    <div className="group relative flex flex-col overflow-hidden rounded-lg border bg-background transition-shadow hover:shadow-lg">
      <Link href={`/products/${product.slug}`} className="relative aspect-square overflow-hidden bg-muted">
        <img
          src={primaryImage}
          alt={product.primary_image?.alt_text || product.images?.[0]?.alt_text || product.name}
          className="object-cover transition-transform group-hover:scale-105 w-full h-full"
        />
        {hasDiscount && (
          <Badge className="absolute top-2 left-2" variant="destructive">
            Sale
          </Badge>
        )}
      </Link>
      <div className="flex flex-1 flex-col p-4 gap-2">
        <div className="flex-1">
          <p className="text-xs text-muted-foreground mb-1">{product.category.name}</p>
          <Link href={`/products/${product.slug}`}>
            <h3 className="font-semibold leading-tight line-clamp-2 hover:text-primary transition-colors">
              {product.name}
            </h3>
          </Link>
        </div>
        <div className="flex items-center gap-2">
          <span className="font-bold text-lg">{displayPrice}</span>
          {hasDiscount && comparePrice && (
            <span className="text-sm text-muted-foreground line-through">{comparePrice}</span>
          )}
        </div>
        <Button
          className="w-full mt-2"
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
