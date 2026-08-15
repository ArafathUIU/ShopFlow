import { cn } from '@/lib/utils';

interface SeparatorProps extends React.ComponentProps<'div'> {
  orientation?: 'horizontal' | 'vertical';
}

function Separator({ className, orientation = 'horizontal', ...props }: SeparatorProps) {
  return (
    <div
      role="separator"
      aria-orientation={orientation}
      data-slot="separator"
      {...props}
      className={cn(
        'shrink-0 bg-border',
        orientation === 'horizontal' ? 'h-[1px] w-full' : 'h-full w-[1px]',
        className
      )}
    />
  );
}

export { Separator };
