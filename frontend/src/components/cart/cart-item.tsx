'use client';

import Link from 'next/link';
import { Minus, Plus, Trash2 } from 'lucide-react';
import type { CartItem as CartItemType } from '@/lib/types';
import { useCartStore } from '@/lib/stores/cart-store';
import { Button } from '@/components/ui/button';

interface CartItemProps {
  item: CartItemType;
}

export function CartItemRow({ item }: CartItemProps) {
  const updateItem = useCartStore((state) => state.updateItem);
  const removeItem = useCartStore((state) => state.removeItem);
  const isLoading = useCartStore((state) => state.isLoading);

  const primaryImage = item.product.images?.[0]?.url || '/placeholder-product.svg';
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
    <div className="flex items-start gap-5 p-5 border-b border-slate-100 last:border-b-0">
      <Link href={`/products/${item.product.slug}`} className="shrink-0">
        <div className="relative h-24 w-24 rounded-xl overflow-hidden bg-slate-100 border border-slate-200">
          <img
            src={primaryImage}
            alt={item.product.name}
            className="object-cover w-full h-full"
          />
        </div>
      </Link>
      <div className="flex-1 min-w-0">
        <Link href={`/products/${item.product.slug}`}>
          <h3 className="font-semibold text-slate-900 truncate hover:text-indigo-600 transition-colors">
            {item.product.name}
          </h3>
        </Link>
        <p className="text-sm text-slate-500 mt-1">
          {formatPrice(item.unit_price)} each
        </p>
        <div className="flex items-center gap-3 mt-3">
          <div className="flex items-center border border-slate-200 rounded-lg bg-white">
            <Button
              variant="ghost"
              size="icon"
              className="h-9 w-9 rounded-lg hover:bg-slate-100"
              onClick={() => handleQuantityChange(item.quantity - 1)}
              disabled={isLoading || item.quantity <= 1}
            >
              <Minus className="h-3.5 w-3.5" />
            </Button>
            <span className="w-10 text-center text-sm font-semibold">{item.quantity}</span>
            <Button
              variant="ghost"
              size="icon"
              className="h-9 w-9 rounded-lg hover:bg-slate-100"
              onClick={() => handleQuantityChange(item.quantity + 1)}
              disabled={isLoading}
            >
              <Plus className="h-3.5 w-3.5" />
            </Button>
          </div>
          <Button
            variant="ghost"
            size="icon"
            className="h-9 w-9 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50"
            onClick={handleRemove}
            disabled={isLoading}
          >
            <Trash2 className="h-4 w-4" />
          </Button>
        </div>
      </div>
      <div className="text-right">
        <p className="font-bold text-slate-900">{formatPrice(itemTotal)}</p>
      </div>
    </div>
  );
}
