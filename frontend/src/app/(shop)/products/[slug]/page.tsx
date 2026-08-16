'use client';

import { useEffect, useState } from 'react';
import { notFound } from 'next/navigation';
import Image from 'next/image';
import { ShoppingCart, Minus, Plus, Heart, Share2, Truck, Shield, RefreshCw, Check } from 'lucide-react';
import { productService } from '@/lib/services/product-service';
import type { Product } from '@/lib/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ProductGrid } from '@/components/product/product-grid';
import { Skeleton } from '@/components/ui/skeleton';
import { useCartStore } from '@/lib/stores/cart-store';

export default function ProductDetailPage({ params }: { params: { slug: string } }) {
  const [product, setProduct] = useState<Product | null>(null);
  const [loading, setLoading] = useState(true);
  const [selectedImage, setSelectedImage] = useState(0);
  const images = product?.images?.length ? product.images : [];
  const safeSelectedImage = Math.min(selectedImage, images.length - 1);
  const [quantity, setQuantity] = useState(1);
  const [relatedProducts, setRelatedProducts] = useState<Product[]>([]);
  const [addedToCart, setAddedToCart] = useState(false);
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
      setAddedToCart(true);
      setTimeout(() => setAddedToCart(false), 2000);
    } catch {
      // Error handled in store
    }
  };

  if (loading) {
    return (
      <div className="container mx-auto px-4 sm:px-6 py-8">
        <div className="grid md:grid-cols-2 gap-8 lg:gap-12">
          <Skeleton className="aspect-square rounded-2xl" />
          <div className="space-y-6">
            <Skeleton className="h-8 w-3/4 rounded-lg" />
            <Skeleton className="h-6 w-1/4 rounded-lg" />
            <Skeleton className="h-4 w-full rounded-lg" />
            <Skeleton className="h-4 w-full rounded-lg" />
            <Skeleton className="h-12 w-full rounded-lg" />
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
    <div className="container mx-auto px-4 sm:px-6 py-8">
      <div className="grid md:grid-cols-2 gap-8 lg:gap-16 mb-16 md:mb-24">
        <div className="space-y-4">
          <div className="relative aspect-square rounded-2xl overflow-hidden bg-slate-100 border border-slate-200">
            <Image
              src={images[safeSelectedImage]?.url || '/placeholder-product.svg'}
              alt={images[safeSelectedImage]?.alt_text || product.name}
              fill
              className="object-cover"
              priority
              sizes="(max-width: 768px) 100vw, 50vw"
            />
            {hasDiscount && (
              <Badge className="absolute top-4 left-4 bg-gradient-to-r from-red-600 to-red-500 text-white border-0 rounded-full px-3 py-1 text-xs font-bold shadow-lg">
                Sale
              </Badge>
            )}
            <div className="absolute top-4 right-4 flex flex-col gap-2">
              <Button variant="ghost" size="icon" className="h-10 w-10 rounded-full bg-white/80 backdrop-blur-sm hover:bg-white shadow-sm">
                <Heart className="h-4 w-4 text-slate-600" />
              </Button>
              <Button variant="ghost" size="icon" className="h-10 w-10 rounded-full bg-white/80 backdrop-blur-sm hover:bg-white shadow-sm">
                <Share2 className="h-4 w-4 text-slate-600" />
              </Button>
            </div>
          </div>
          {images.length > 1 && (
            <div className="flex gap-3 overflow-x-auto pb-2">
              {images.map((image, idx) => (
                <button
                  key={image.id}
                  onClick={() => setSelectedImage(idx)}
                  className={`relative h-20 w-20 shrink-0 rounded-xl overflow-hidden border-2 transition-all duration-200 ${
                    selectedImage === idx ? 'border-indigo-600 ring-2 ring-indigo-600/20' : 'border-transparent hover:border-slate-300'
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
            <p className="text-sm font-medium text-indigo-600 mb-2 uppercase tracking-wider">{product.category.name}</p>
            <h1 className="text-3xl md:text-4xl font-bold text-slate-900 tracking-tight">{product.name}</h1>
            <p className="text-sm text-slate-500 mt-2 font-mono">SKU: {product.sku}</p>
          </div>

          <div className="flex items-baseline gap-4">
            <span className="text-3xl md:text-4xl font-bold text-slate-900">{displayPrice}</span>
            {hasDiscount && comparePrice && (
              <span className="text-lg text-slate-400 line-through">{comparePrice}</span>
            )}
            {hasDiscount && (
              <Badge className="bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-50">
                Save {Math.round((1 - (product.price.cents / (product.compare_at_price?.cents || product.price.cents))) * 100)}%
              </Badge>
            )}
          </div>

          <div className="flex items-center gap-3">
            <Badge variant={inStock ? 'default' : 'destructive'} className="rounded-full px-3 py-1">
              {inStock ? 'In Stock' : 'Out of Stock'}
            </Badge>
            {inStock && (
              <span className="text-sm text-slate-500">Available for immediate shipping</span>
            )}
          </div>

          <p className="text-slate-600 leading-relaxed">{product.description}</p>

          <div className="flex items-center gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200">
            <div className="flex items-center border border-slate-200 rounded-lg bg-white">
              <Button
                variant="ghost"
                size="icon"
                className="h-10 w-10 rounded-lg hover:bg-slate-100"
                onClick={() => setQuantity((q) => Math.max(1, q - 1))}
                disabled={quantity <= 1}
              >
                <Minus className="h-4 w-4" />
              </Button>
              <span className="w-12 text-center text-sm font-semibold">{quantity}</span>
              <Button
                variant="ghost"
                size="icon"
                className="h-10 w-10 rounded-lg hover:bg-slate-100"
                onClick={() => setQuantity((q) => q + 1)}
              >
                <Plus className="h-4 w-4" />
              </Button>
            </div>
            <Button
              size="lg"
              className="flex-1 rounded-xl shadow-lg shadow-indigo-600/20 hover:shadow-xl hover:shadow-indigo-600/30"
              onClick={handleAddToCart}
              disabled={!inStock || isLoading}
            >
              {addedToCart ? (
                <>
                  <Check className="h-5 w-5 mr-2" />
                  Added to Cart
                </>
              ) : (
                <>
                  <ShoppingCart className="h-5 w-5 mr-2" />
                  {inStock ? 'Add to Cart' : 'Out of Stock'}
                </>
              )}
            </Button>
          </div>

          <div className="grid grid-cols-3 gap-3 pt-4 border-t border-slate-200">
            <div className="flex items-center gap-2 text-xs text-slate-500">
              <Truck className="h-4 w-4 text-indigo-600" />
              Free shipping
            </div>
            <div className="flex items-center gap-2 text-xs text-slate-500">
              <Shield className="h-4 w-4 text-indigo-600" />
              Secure checkout
            </div>
            <div className="flex items-center gap-2 text-xs text-slate-500">
              <RefreshCw className="h-4 w-4 text-indigo-600" />
              30-day returns
            </div>
          </div>
        </div>
      </div>

      {relatedProducts.length > 0 && (
        <section>
          <div className="flex items-center justify-between mb-8">
            <h2 className="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">Related Products</h2>
          </div>
          <ProductGrid products={relatedProducts} />
        </section>
      )}
    </div>
  );
}
