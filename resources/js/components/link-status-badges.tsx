import { CheckIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import {
    linkStatusBadgeClasses,
    uncertaintyBadgeClasses,
    uncertaintyLevelBars,
    verificationBadgeClasses,
} from '@/lib/labels';
import type {
    Link,
    UncertaintyLevel,
    VerificationStatus,
} from '@/types/models';

/**
 * A link's verification (0013): declared or verified on evidence. Verified
 * carries a check, so it does not rely on colour alone.
 */
export function VerificationBadge({
    verification,
}: {
    verification: VerificationStatus;
}) {
    const enumLabels = useEnumLabels();
    return (
        <Badge className={verificationBadgeClasses[verification]}>
            {verification === 'verified' && <CheckIcon aria-hidden />}
            {enumLabels.VerificationStatus[verification]}
        </Badge>
    );
}

/**
 * The two dimensions of a link side by side: progress of the implementation
 * and verification. Labelled, each badge sits under its dimension's name, as
 * on the link page; unlabelled, the table column names them.
 */
export function LinkStatusBadges({
    link,
    labelled = false,
}: {
    link: Pick<Link, 'status' | 'verification_status'>;
    labelled?: boolean;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const progress = (
        <Badge className={linkStatusBadgeClasses[link.status]}>
            {enumLabels.LinkStatus[link.status]}
        </Badge>
    );
    const verification = (
        <VerificationBadge verification={link.verification_status} />
    );

    if (!labelled) {
        return (
            <span className="flex flex-wrap items-center gap-1.5">
                {progress}
                {verification}
            </span>
        );
    }

    return (
        <span className="flex flex-wrap items-start gap-8">
            <span className="grid gap-1">
                <span className="text-muted-foreground text-xs tracking-[0.32px]">
                    {t('Progress')}
                </span>
                {progress}
            </span>
            <span className="grid gap-1">
                <span className="text-muted-foreground text-xs tracking-[0.32px]">
                    {t('Verification')}
                </span>
                {verification}
            </span>
        </span>
    );
}

/**
 * An uncertainty level (0028): the confidence in an assessment, shown as a
 * neutral badge with one to three bars rather than a severity colour.
 */
export function UncertaintyBadge({ level }: { level: UncertaintyLevel }) {
    const enumLabels = useEnumLabels();
    const bars = uncertaintyLevelBars[level];
    return (
        <Badge className={uncertaintyBadgeClasses[level]}>
            <span className="inline-flex gap-0.5" aria-hidden>
                {[1, 2, 3].map((bar) => (
                    <span
                        key={bar}
                        className={
                            bar <= bars
                                ? 'h-2.5 w-1.5 bg-current'
                                : 'h-2.5 w-1.5 bg-current opacity-30'
                        }
                    />
                ))}
            </span>
            {enumLabels.UncertaintyLevel[level]}
        </Badge>
    );
}
