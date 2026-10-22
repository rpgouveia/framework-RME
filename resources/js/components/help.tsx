import { InfoIcon } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';

/**
 * A domain term with a short definition: dotted underline, opens on hover or
 * keyboard focus. For longer help, lists or links, use Toggletip.
 */
export function Term({
    children,
    definition,
    className,
}: {
    children: ReactNode;
    definition: ReactNode;
    className?: string;
}) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    tabIndex={0}
                    className={cn(
                        'focus-visible:outline-ring cursor-help underline decoration-dotted underline-offset-4 outline-none focus-visible:outline-2',
                        className,
                    )}
                >
                    {children}
                </span>
            </TooltipTrigger>
            <TooltipContent>{definition}</TooltipContent>
        </Tooltip>
    );
}

/**
 * An information button beside a label or title that opens longer help on
 * click, so it also works on touch.
 */
export function Toggletip({
    label,
    children,
    className,
}: {
    /** Accessible name of the button, such as "What is the estimated cost". */
    label: string;
    children: ReactNode;
    className?: string;
}) {
    return (
        <Popover>
            <PopoverTrigger
                aria-label={label}
                className={cn(
                    'text-muted-foreground hover:text-foreground focus-visible:outline-ring inline-grid size-5 shrink-0 place-items-center align-middle outline-none focus-visible:outline-2',
                    className,
                )}
            >
                <InfoIcon className="size-4" aria-hidden />
            </PopoverTrigger>
            <PopoverContent className="grid gap-2">{children}</PopoverContent>
        </Popover>
    );
}
