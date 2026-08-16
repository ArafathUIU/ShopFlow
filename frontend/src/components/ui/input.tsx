import * as React from 'react';
import { cn } from '@/lib/utils';

function Input({ className, type, ...props }: React.ComponentProps<'input'>) {
  return (
    <input
      type={type}
      data-slot="input"
      className={cn(
        'file:text-slate-950 placeholder:text-slate-400 selection:bg-indigo-600 selection:text-white flex h-10 w-full min-w-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-base shadow-sm transition-all duration-200 outline-none focus-visible:border-indigo-600 focus-visible:ring-2 focus-visible:ring-indigo-600/20 disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
        'file:inline-flex file:h-10 file:border-0 file:bg-transparent file:text-sm file:font-medium',
        'md:text-sm',
        className
      )}
      {...props}
    />
  );
}

export { Input };
