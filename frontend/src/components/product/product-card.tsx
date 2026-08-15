'use client';

import Image from 'next/image';
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

function formatPrice(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(cents / 100);
}

export function ProductCard({ product }: ProductCardProps) {
  const addItem = useCartStore((state) => state.addItem);
  const isLoading = useCartStore((state) => state.isLoading);

  const primaryImage = product.images[0]?.url || '/placeholder-product.png';
  const hasDiscount = product.price > 0 && product.price < (product.price * 1.2);
  const displayPrice = formatPrice(product.price);
  const comparePrice = formatPrice(product.price * 1.2);

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
        <Image
          src={primaryImage}
          alt={product.images[0]?.alt_text || product.name}
          fill
          className="object-cover transition-transform group-hover:scale-105"
          sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 25vw"
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
          {hasDiscount && (
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
