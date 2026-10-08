import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { formatDate } from '@/lib/format';
import type { VerificationStatus } from '@/types/models';

/**
 * A link's next review date. There is none for a link of a system in the
 * unacceptable tier, which never operates, nor for a declared link: the
 * periodic review starts at its first verification (0018). The reason shows
 * on hover or keyboard focus.
 */
export function ReviewDate({
    date,
    verification,
    unacceptable = false,
}: {
    date: string | null;
    verification: VerificationStatus;
    /** Whether the link's system is in the unacceptable tier. */
    unacceptable?: boolean;
}) {
    if (date !== null) {
        return <>{formatDate(date)}</>;
    }

    const [label, reason] =
        unacceptable || verification === 'verified'
            ? [
                  'Não se aplica',
                  'O sistema está na faixa inaceitável do EU AI Act e não pode operar, então o vínculo não tem revisão periódica.',
              ]
            : [
                  'Aguardando verificação',
                  'A revisão periódica começa na primeira verificação do vínculo, com o intervalo da faixa do sistema.',
              ];

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <span
                    tabIndex={0}
                    className="text-muted-foreground cursor-help font-normal underline decoration-dotted underline-offset-4"
                >
                    {label}
                </span>
            </TooltipTrigger>
            <TooltipContent className="max-w-xs">{reason}</TooltipContent>
        </Tooltip>
    );
}
