'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';
import { ChevronRight } from 'lucide-react';

interface DropdownMenuContextValue {
  open: boolean;
  setOpen: (open: boolean) => void;
}

const DropdownMenuContext = React.createContext<DropdownMenuContextValue | null>(null);

interface DropdownMenuProps {
  children: React.ReactNode;
}

function DropdownMenu({ children }: DropdownMenuProps) {
  const [open, setOpen] = React.useState(false);

  return (
    <DropdownMenuContext.Provider value={{ open, setOpen }}>
      <div className="relative inline-block">{children}</div>
    </DropdownMenuContext.Provider>
  );
}

interface DropdownMenuTriggerProps extends React.ComponentProps<'button'> {}

function DropdownMenuTrigger({ className, children, ...props }: DropdownMenuTriggerProps) {
  const ctx = React.useContext(DropdownMenuContext);
  if (!ctx) throw new Error('DropdownMenuTrigger must be used within DropdownMenu');

  const { open, setOpen } = ctx;

  return (
    <button
      aria-haspopup="menu"
      aria-expanded={open}
      data-slot="dropdown-menu-trigger"
      onClick={() => setOpen(!open)}
      className={cn('inline-flex items-center justify-center', className)}
      {...props}
    >
      {children}
    </button>
  );
}

interface DropdownMenuContentProps extends React.ComponentProps<'div'> {
  align?: 'start' | 'center' | 'end';
}

function DropdownMenuContent({ className, align = 'end', ...props }: DropdownMenuContentProps) {
  const ctx = React.useContext(DropdownMenuContext);
  if (!ctx || !ctx.open) return null;

  const { setOpen } = ctx;

  React.useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      const target = event.target as HTMLElement;
      if (!target.closest('[data-slot="dropdown-menu"]')) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handleClickOutside);
    return () => document.removeEventListener('mousedown', handleClickOutside);
  }, [setOpen]);

  const alignmentClass =
    align === 'start' ? 'left-0' : align === 'center' ? 'left-1/2 -translate-x-1/2' : 'right-0';

  return (
    <div
      data-slot="dropdown-menu-content"
      className={cn(
        'bg-background z-50 min-w-32 rounded-md border shadow-md outline-none',
        'overflow-hidden',
        alignmentClass,
        'absolute top-full mt-2',
        className
      )}
      {...props}
    />
  );
}

interface DropdownMenuItemProps extends React.ComponentProps<'div'> {
  disabled?: boolean;
  onSelect?: () => void;
}

function DropdownMenuItem({ className, disabled, onSelect, children, ...props }: DropdownMenuItemProps) {
  const ctx = React.useContext(DropdownMenuContext);
  if (!ctx) throw new Error('DropdownMenuItem must be used within DropdownMenu');

  return (
    <div
      role="menuitem"
      data-slot="dropdown-menu-item"
      className={cn(
        'flex cursor-default items-center gap-2 rounded-sm px-2 py-1.5 text-sm transition-colors',
        'hover:bg-accent hover:text-accent-foreground',
        'focus:bg-accent focus:text-accent-foreground focus:outline-none',
        disabled && 'pointer-events-none opacity-50',
        className
      )}
      onClick={() => {
        if (!disabled) {
          onSelect?.();
          ctx.setOpen(false);
        }
      }}
      {...props}
    >
      {children}
      <ChevronRight className="ml-auto h-4 w-4 opacity-50" />
    </div>
  );
}

function DropdownMenuSeparator({ className, ...props }: React.ComponentProps<'div'>) {
  return (
    <div
      data-slot="dropdown-menu-separator"
      className={cn('bg-border -mx-1 my-1 h-px', className)}
      {...props}
    />
  );
}

export {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
};
