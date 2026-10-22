import type { ReactNode } from 'react';
import { cn } from '@/lib/utils';

/**
 * One numbered part of a long form, so the fields read in the order the
 * decision is made.
 */
export function FormSection({
    step,
    title,
    description,
    className,
    children,
}: {
    step?: number;
    title: string;
    description?: string;
    className?: string;
    children: ReactNode;
}) {
    return (
        <fieldset className={cn('grid min-w-0 gap-6 border-t pt-6', className)}>
            <legend className="float-left mb-0 w-full p-0">
                <span className="block text-xl leading-7">
                    {step !== undefined && `${step}. `}
                    {title}
                </span>
                {description && (
                    <span className="text-muted-foreground mt-1 block text-sm">
                        {description}
                    </span>
                )}
            </legend>
            {children}
        </fieldset>
    );
}
