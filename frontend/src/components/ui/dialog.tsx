'use client';

import * as React from 'react';
import { cn } from '@/lib/utils';
import { X } from 'lucide-react';

interface DialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  children?: React.ReactNode;
}

interface DialogContentProps extends React.ComponentProps<'div'> {
  showClose?: boolean;
}

const DialogContext = React.createContext<DialogProps | null>(null);

function Dialog({ open, onOpenChange, children }: DialogProps) {
  React.useEffect(() => {
    if (open) {
      document.body.style.overflow = 'hidden';
    } else {
      document.body.style.overflow = '';
    }
    return () => {
      document.body.style.overflow = '';
    };
  }, [open]);

  if (!open) return null;

  return (
    <DialogContext.Provider value={{ open, onOpenChange }}>
      <div className="fixed inset-0 z-50">
        <div className="fixed inset-0 bg-black/80" onClick={() => onOpenChange(false)} />
        <div className="fixed inset-0 overflow-y-auto">
          <div className="flex min-h-full items-center justify-center p-4">
            {children}
          </div>
        </div>
      </div>
    </DialogContext.Provider>
  );
}

function DialogContent({ className, showClose = true, children, ...props }: DialogContentProps) {
  return (
    <div
      role="dialog"
      aria-modal="true"
      data-slot="dialog-content"
      className={cn(
        'bg-background relative z-50 grid w-full max-w-lg gap-4 rounded-lg border p-6 shadow-lg duration-200',
        className
      )}
      {...props}
    >
      {children}
      {showClose && (
        <button
          onClick={() => {
            const ctx = React.useContext(DialogContext);
            ctx?.onOpenChange(false);
          }}
          className="ring-offset-background focus:ring-ring absolute right-4 top-4 rounded-sm opacity-70 transition-opacity hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:pointer-events-none"
        >
          <X className="h-4 w-4" />
          <span className="sr-only">Close</span>
        </button>
      )}
    </div>
  );
}

function DialogHeader({ className, ...props }: React.ComponentProps<'div'>) {
  return (
    <div
      data-slot="dialog-header"
      className={cn('flex flex-col gap-2', className)}
      {...props}
    />
  );
}

function DialogTitle({ className, ...props }: React.ComponentProps<'h2'>) {
  return (
    <h2
      data-slot="dialog-title"
      className={cn('text-lg leading-none font-semibold', className)}
      {...props}
    />
  );
}

function DialogDescription({ className, ...props }: React.ComponentProps<'p'>) {
  return (
    <p
      data-slot="dialog-description"
      className={cn('text-muted-foreground text-sm', className)}
      {...props}
    />
  );
}

function DialogFooter({ className, ...props }: React.ComponentProps<'div'>) {
  return (
    <div
      data-slot="dialog-footer"
      className={cn('flex flex-col-reverse sm:flex-row sm:justify-end gap-2', className)}
      {...props}
    />
  );
}

export { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription, DialogFooter };
