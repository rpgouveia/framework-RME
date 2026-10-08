import { TriangleAlertIcon } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { cn } from '@/lib/utils';

/**
 * Red tones measured for WCAG 2.1 AA: at least 9:1 in light mode and 13:1 in
 * dark mode. shadcn's destructive alert falls to 3.6:1 in dark mode.
 */
export const unacceptableTone =
    'border-red-300 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-100';

/**
 * The warning for a system in the unacceptable tier of the EU AI Act: it
 * may be registered and planned for, but never operates. The short version
 * is for forms, next to the choice that led to it.
 */
export function UnacceptableTierAlert({
    short = false,
    className,
}: {
    short?: boolean;
    className?: string;
}) {
    return (
        <Alert className={cn(unacceptableTone, className)}>
            <TriangleAlertIcon aria-hidden />
            <AlertTitle>Sistema na faixa inaceitável do EU AI Act</AlertTitle>
            <AlertDescription className="text-red-900 dark:text-red-200">
                {short ? (
                    <p>
                        O sistema não pode operar: este vínculo não terá revisão
                        periódica e serve para planejar a descontinuação.
                    </p>
                ) : (
                    <p>
                        A faixa corresponde a práticas proibidas pelo EU AI Act.
                        O sistema não é considerado em operação, e seus vínculos
                        não têm revisão periódica: servem para planejar a
                        descontinuação.
                    </p>
                )}
            </AlertDescription>
        </Alert>
    );
}
