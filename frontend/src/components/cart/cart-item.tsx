'use client';

import Image from 'next/image';
import Link from 'next/link';
import { Minus, Plus, Trash2 } from 'lucide-react';
import type { CartItem as CartItemType } from '@/lib/types';
import { useCartStore } from '@/lib/stores/cart-store';
import { cn, formatPrice } from '@/lib/utils';
import { Button } from '@/components/ui/button';

interface CartItemProps {
  item: CartItemType;
}

export function CartItemRow({ item }: CartItemProps) {
  const updateItem = useCartStore((state) => state.updateItem);
  const removeItem = useCartStore((state) => state.removeItem);
  const isLoading = useCartStore((state) => state.isLoading);

  const primaryImage = item.product.images[0]?.url || '/placeholder-product.png';
  const itemTotal = item.unit_price * item.quantity;

  const handleQuantityChange = async (newQuantity: number) => {
    if (newQuantity < 1) return;
    try {
      await updateItem(item.id, newQuantity);
    } catch {
      // Error handled in store
    }
  };

  const handleRemove = async () => {
    try {
      await removeItem(item.id);
    } catch {
      // Error handled in store
    }
  };

  return (
    <div className="flex items-start gap-4 py-4 border-b">
      <Link href={`/products/${item.product.slug}`} className="shrink-0">
        <div className="relative h-20 w-20 rounded-md overflow-hidden bg-muted">
          <Image
            src={primaryImage}
            alt={item.product.name}
            fill
            className="object-cover"
            sizes="80px"
          />
        </div>
      </Link>
      <div className="flex-1 min-w-0">
        <Link href={`/products/${item.product.slug}`}>
          <h3 className="font-medium truncate hover:text-primary transition-colors">
            {item.product.name}
          </h3>
        </Link>
        <p className="text-sm text-muted-foreground mt-1">
          {formatPrice(item.unit_price)} each
        </p>
        <div className="flex items-center gap-2 mt-2">
          <Button
            variant="outline"
            size="icon"
            className="h-8 w-8"
            onClick={() => handleQuantityChange(item.quantity - 1)}
            disabled={isLoading || item.quantity <= 1}
          >
            <Minus className="h-3 w-3" />
          </Button>
          <span className="w-8 text-center text-sm">{item.quantity}</span>
          <Button
            variant="outline"
            size="icon"
            className="h-8 w-8"
            onClick={() => handleQuantityChange(item.quantity + 1)}
            disabled={isLoading}
          >
            <Plus className="h-3 w-3" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="h-8 w-8 ml-2 text-destructive hover:text-destructive"
            onClick={handleRemove}
            disabled={isLoading}
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      </div>
      <div className="text-right">
        <p className="font-semibold">{formatPrice(itemTotal)}</p>
      </div>
    </div>
  );
}
