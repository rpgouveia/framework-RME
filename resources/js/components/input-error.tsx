import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        <p
            {...props}
            className={cn(
                'text-destructive-foreground text-xs tracking-[0.32px]',
                className,
            )}
        >
            {message}
        </p>
    ) : null;
}
