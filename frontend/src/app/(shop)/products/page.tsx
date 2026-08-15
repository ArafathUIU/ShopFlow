'use client';

import { useEffect, useState, useCallback, Suspense } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { productService } from '@/lib/services/product-service';
import type { Product, Category } from '@/lib/types';
import { ProductGrid } from '@/components/product/product-grid';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Skeleton } from '@/components/ui/skeleton';
import { Search, SlidersHorizontal, X } from 'lucide-react';

const sortOptions = [
  { value: 'featured', label: 'Featured' },
  { value: 'newest', label: 'Newest' },
  { value: 'price_asc', label: 'Price: Low to High' },
  { value: 'price_desc', label: 'Price: High to Low' },
];

function SimpleSelect({ value, onValueChange, options, placeholder }: {
  value: string;
  onValueChange: (val: string) => void;
  options: { value: string; label: string }[];
  placeholder?: string;
}) {
  const [open, setOpen] = useState(false);
  const selected = options.find((o) => o.value === value);
  const displayValue = selected?.label || placeholder || 'Select...';

  return (
    <div className="relative">
      <button
        type="button"
        onClick={() => setOpen(!open)}
        className="flex h-9 w-full items-center justify-between rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm ring-offset-background focus:outline-none focus:ring-1 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
      >
        <span className="truncate">{displayValue}</span>
        <SlidersHorizontal className="h-4 w-4 opacity-50" />
      </button>
      {open && (
        <>
          <div className="fixed inset-0 z-40" onClick={() => setOpen(false)} />
          <div className="absolute z-50 mt-1 w-full rounded-md border bg-popover p-1 text-popover-foreground shadow-md">
            {options.map((opt) => (
              <div
                key={opt.value}
                role="option"
                aria-selected={value === opt.value}
                className="relative flex w-full cursor-pointer select-none items-center rounded-sm px-2 py-1.5 text-sm outline-none hover:bg-accent hover:text-accent-foreground"
                onClick={() => {
                  onValueChange(opt.value);
                  setOpen(false);
                }}
              >
                {opt.label}
              </div>
            ))}
          </div>
        </>
      )}
    </div>
  );
}

function ProductsClient() {
  const router = useRouter();
  const searchParams = useSearchParams();

  const [products, setProducts] = useState<Product[]>([]);
  const [categories, setCategories] = useState<Category[]>([]);
  const [loading, setLoading] = useState(true);
  const [totalPages, setTotalPages] = useState(1);
  const [currentPage, setCurrentPage] = useState(1);

  const [search, setSearch] = useState(searchParams.get('search') || '');
  const [category, setCategory] = useState(searchParams.get('category') || '');
  const [sort, setSort] = useState(searchParams.get('sort') || 'featured');
  const [minPrice, setMinPrice] = useState(searchParams.get('min_price') || '');
  const [maxPrice, setMaxPrice] = useState(searchParams.get('max_price') || '');

  const loadProducts = useCallback(async () => {
    setLoading(true);
    try {
      const result = await productService.getProducts({
        search: search || undefined,
        category_id: category ? Number(category) : undefined,
        min_price: minPrice ? Number(minPrice) : undefined,
        max_price: maxPrice ? Number(maxPrice) : undefined,
        sort: sort === 'featured' ? undefined : sort === 'newest' ? 'newest' : 'price',
        order: sort === 'price_asc' ? 'asc' : sort === 'price_desc' ? 'desc' : undefined,
        page: currentPage,
        limit: 12,
      });
      if (result) {
        setProducts(result.data);
        setTotalPages(result.pagination.last_page);
      }
    } catch {
      // Error handled silently
    } finally {
      setLoading(false);
    }
  }, [search, category, sort, minPrice, maxPrice, currentPage]);

  useEffect(() => {
    loadProducts();
  }, [loadProducts]);

  useEffect(() => {
    const params = new URLSearchParams();
    if (search) params.set('search', search);
    if (category) params.set('category', category);
    if (sort && sort !== 'featured') params.set('sort', sort);
    if (minPrice) params.set('min_price', minPrice);
    if (maxPrice) params.set('max_price', maxPrice);
    router.replace(`/products?${params.toString()}`, { scroll: false });
  }, [search, category, sort, minPrice, maxPrice, router]);

  useEffect(() => {
    productService.getCategories().then(setCategories).catch(() => {});
  }, []);

  const clearFilters = () => {
    setSearch('');
    setCategory('');
    setSort('featured');
    setMinPrice('');
    setMaxPrice('');
    setCurrentPage(1);
  };

  const hasFilters = search || category || sort !== 'featured' || minPrice || maxPrice;

  const categoryOptions = [
    { value: '', label: 'All Categories' },
    ...categories.map((cat) => ({ value: String(cat.id), label: cat.name })),
  ];

  const sortOptionList = sortOptions.map((opt) => ({
    value: opt.value,
    label: opt.label,
  }));

  return (
    <div className="container mx-auto px-4 py-8">
      <div className="flex flex-col lg:flex-row gap-8">
        <aside className="w-full lg:w-64 shrink-0">
          <div className="flex items-center justify-between lg:hidden mb-4">
            <h2 className="text-lg font-semibold">Filters</h2>
            {hasFilters && (
              <Button variant="ghost" size="sm" onClick={clearFilters}>
                Clear All
              </Button>
            )}
          </div>

          <div className="space-y-6">
            <div>
              <Label className="text-sm font-medium mb-2 block">Search</Label>
              <div className="relative">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search products..."
                  value={search}
                  onChange={(e) => {
                    setSearch(e.target.value);
                    setCurrentPage(1);
                  }}
                  className="pl-9"
                />
              </div>
            </div>

            <div>
              <Label className="text-sm font-medium mb-2 block">Category</Label>
              <SimpleSelect
                value={category}
                onValueChange={(val) => { setCategory(val); setCurrentPage(1); }}
                options={categoryOptions}
                placeholder="All Categories"
              />
            </div>

            <div>
              <Label className="text-sm font-medium mb-2 block">Price Range</Label>
              <div className="flex items-center gap-2">
                <Input
                  type="number"
                  placeholder="Min"
                  value={minPrice}
                  onChange={(e) => { setMinPrice(e.target.value); setCurrentPage(1); }}
                  className="h-9"
                />
                <span className="text-muted-foreground">-</span>
                <Input
                  type="number"
                  placeholder="Max"
                  value={maxPrice}
                  onChange={(e) => { setMaxPrice(e.target.value); setCurrentPage(1); }}
                  className="h-9"
                />
              </div>
            </div>

            <div>
              <Label className="text-sm font-medium mb-2 block">Sort By</Label>
              <SimpleSelect
                value={sort}
                onValueChange={(val) => { setSort(val); setCurrentPage(1); }}
                options={sortOptionList}
                placeholder="Sort by"
              />
            </div>

            {hasFilters && (
              <Button variant="outline" size="sm" onClick={clearFilters} className="w-full">
                <X className="h-4 w-4 mr-2" />
                Clear Filters
              </Button>
            )}
          </div>
        </aside>

        <div className="flex-1">
          <div className="flex items-center justify-between mb-6">
            <h1 className="text-3xl font-bold">Products</h1>
            <span className="text-sm text-muted-foreground">
              {loading ? 'Loading...' : `${products.length} products`}
            </span>
          </div>
          <ProductGrid products={products} loading={loading} />

          {!loading && totalPages > 1 && (
            <div className="flex items-center justify-center gap-2 mt-8">
              <Button
                variant="outline"
                size="sm"
                disabled={currentPage <= 1}
                onClick={() => setCurrentPage((p) => p - 1)}
              >
                Previous
              </Button>
              <span className="text-sm text-muted-foreground">
                Page {currentPage} of {totalPages}
              </span>
              <Button
                variant="outline"
                size="sm"
                disabled={currentPage >= totalPages}
                onClick={() => setCurrentPage((p) => p + 1)}
              >
                Next
              </Button>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default function ProductsPage() {
  return (
    <Suspense fallback={<div className="container mx-auto px-4 py-8"><ProductGrid products={[]} loading /></div>}>
      <ProductsClient />
    </Suspense>
  );
}
