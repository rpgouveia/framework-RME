import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatDate } from '@/lib/format';

/**
 * A link's next review date. A link of a system in the unacceptable tier has
 * none: the system never operates, so there is no periodic review. The
 * reason shows on hover or keyboard focus.
 */
export function ReviewDate({ date }: { date: string | null }) {
    if (date !== null) {
        return <>{formatDate(date)}</>;
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    tabIndex={0}
                    className="text-muted-foreground cursor-help font-normal underline decoration-dotted underline-offset-4"
                >
                    Não se aplica
                </span>
            </TooltipTrigger>
            <TooltipContent className="max-w-xs">
                O sistema está na faixa inaceitável do EU AI Act e não pode
                operar, então o vínculo não tem revisão periódica.
            </TooltipContent>
        </Tooltip>
    );
}
