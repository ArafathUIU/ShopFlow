'use client';

import { useEffect, useState } from 'react';
import { notFound } from 'next/navigation';
import Image from 'next/image';
import Link from 'next/link';
import { ShoppingCart, Minus, Plus } from 'lucide-react';
import { productService } from '@/lib/services/product-service';
import type { Product } from '@/lib/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ProductGrid } from '@/components/product/product-grid';
import { Skeleton } from '@/components/ui/skeleton';
import { useCartStore } from '@/lib/stores/cart-store';

function formatPrice(cents: number): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
  }).format(cents / 100);
}

export default function ProductDetailPage({ params }: { params: { slug: string } }) {
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedImage, setSelectedImage] = useState(0);
  const [quantity, setQuantity] = useState(1);
  const [relatedProducts, setRelatedProducts] = useState<Product[]>([]);
  const addItem = useCartStore((state) => state.addItem);
  const isLoading = useCartStore((state) => state.isLoading);

  useEffect(() => {
    async function loadProduct() {
      try {
        const allProducts = await productService.getProducts({ limit: 100 });
        if (!allProducts) {
          notFound();
          return;
        }
        const found = allProducts.data.find((p) => p.slug === params.slug);
        if (!found) {
          notFound();
        }
        setProduct(found);
        const related = allProducts.data
          .filter((p) => p.category.id === found.category.id && p.id !== found.id)
          .slice(0, 4);
        setRelatedProducts(related);
      } catch {
        notFound();
      } finally {
        setLoading(false);
      }
    }
    loadProduct();
  }, [params.slug]);

  const handleAddToCart = async () => {
    if (!product) return;
    try {
      await addItem(product.id, quantity);
    } catch {
      // Error handled in store
    }
  };

  if (loading) {
    return (
      <div className="container mx-auto px-4 py-8">
        <div className="grid md:grid-cols-2 gap-8">
          <Skeleton className="aspect-square rounded-lg" />
          <div className="space-y-4">
            <Skeleton className="h-8 w-3/4" />
            <Skeleton className="h-6 w-1/4" />
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-4 w-full" />
            <Skeleton className="h-10 w-full" />
          </div>
        </div>
      </div>
    );
  }

  if (!product) {
    return notFound();
  }

  const displayPrice = product.price.formatted;
  const comparePrice = product.compare_at_price?.formatted;
  const hasDiscount = product.is_on_sale || (product.compare_at_price !== null && product.compare_at_price !== undefined);
  const inStock = product.status === 'active';

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="grid md:grid-cols-2 gap-8 mb-16">
        <div className="space-y-4">
          <div className="relative aspect-square rounded-lg overflow-hidden bg-muted">
            <Image
              src={product.images[selectedImage]?.url || '/placeholder-product.png'}
              alt={product.images[selectedImage]?.alt_text || product.name}
              fill
              className="object-cover"
              priority
              sizes="(max-width: 768px) 100vw, 50vw"
            />
            {hasDiscount && (
              <Badge className="absolute top-4 left-4" variant="destructive">
                Sale
              </Badge>
            )}
          </div>
          {product.images.length > 1 && (
            <div className="flex gap-2 overflow-x-auto pb-2">
              {product.images.map((image, idx) => (
                <button
                  key={image.id}
                  onClick={() => setSelectedImage(idx)}
                  className={`relative h-20 w-20 shrink-0 rounded-md overflow-hidden border-2 transition-colors ${
                    selectedImage === idx ? 'border-primary' : 'border-transparent'
                  }`}
                >
                  <Image
                    src={image.url}
                    alt={image.alt_text || `${product.name} ${idx + 1}`}
                    fill
                    className="object-cover"
                    sizes="80px"
                  />
                </button>
              ))}
            </div>
          )}
        </div>

        <div className="space-y-6">
          <div>
            <p className="text-sm text-muted-foreground mb-2">{product.category.name}</p>
            <h1 className="text-3xl md:text-4xl font-bold">{product.name}</h1>
            <p className="text-sm text-muted-foreground mt-2">SKU: {product.sku}</p>
          </div>

          <div className="flex items-baseline gap-3">
            <span className="text-3xl font-bold">{displayPrice}</span>
            {hasDiscount && comparePrice && (
              <span className="text-lg text-muted-foreground line-through">{comparePrice}</span>
            )}
          </div>

          <div className="flex items-center gap-2">
            <Badge variant={inStock ? 'default' : 'destructive'}>
              {inStock ? 'In Stock' : 'Out of Stock'}
            </Badge>
            {inStock && (
              <span className="text-sm text-muted-foreground">Available</span>
            )}
          </div>

          <p className="text-muted-foreground leading-relaxed">{product.description}</p>

          <div className="flex items-center gap-4">
            <div className="flex items-center border rounded-md">
              <Button
                variant="ghost"
                size="icon"
                className="h-10 w-10"
                onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                disabled={quantity <= 1}
              >
                <Minus className="h-4 w-4" />
              </Button>
              <span className="w-12 text-center text-sm font-medium">{quantity}</span>
              <Button
                variant="ghost"
                size="icon"
                className="h-10 w-10"
                onClick={() => setQuantity((q) => q + 1)}
              >
                <Plus className="h-4 w-4" />
              </Button>
            </div>
            <Button
              size="lg"
              className="flex-1"
              onClick={handleAddToCart}
              disabled={!inStock || isLoading}
            >
              <ShoppingCart className="h-5 w-5 mr-2" />
              {inStock ? 'Add to Cart' : 'Out of Stock'}
            </Button>
          </div>
        </div>
      </div>

      {relatedProducts.length > 0 && (
        <section>
          <h2 className="text-2xl font-bold mb-6">Related Products</h2>
          <ProductGrid products={relatedProducts} />
        </section>
      )}
    </div>
  );
}
