import * as React from 'react';
import { cn } from '@/lib/utils';

function Input({ className, type, ...props }: React.ComponentProps<'input'>) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground flex h-9 w-full min-w-0 rounded-md border border-input bg-transparent px-3 py-1 text-base shadow-sm transition-all outline-none focus-visible:border-ring focus-visible:ring-1 focus-visible:ring-ring disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
        'file:inline-flex file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium',
        'md:text-sm',
        className
      )}
      {...props}
    />
  );
}

export { Input };
