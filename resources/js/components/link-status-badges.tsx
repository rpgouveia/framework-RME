import { Badge } from '@/components/ui/badge';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { linkStatusBadgeClasses, verificationBadgeClasses } from '@/lib/labels';
import type { Link, VerificationStatus } from '@/types/models';

/** A link's verification (0013): declared or verified on evidence. */
export function VerificationBadge({
    verification,
}: {
    verification: VerificationStatus;
}) {
    const enumLabels = useEnumLabels();
    return (
        <Badge className={verificationBadgeClasses[verification]}>
            {enumLabels.VerificationStatus[verification]}
        </Badge>
    );
}

/**
 * The two dimensions of a link side by side: progress of the implementation
 * and verification.
 */
export function LinkStatusBadges({
    link,
}: {
    link: Pick<Link, 'status' | 'verification_status'>;
}) {
    const enumLabels = useEnumLabels();
    return (
        <span className="flex flex-wrap items-center gap-1.5">
            <Badge className={linkStatusBadgeClasses[link.status]}>
                {enumLabels.LinkStatus[link.status]}
            </Badge>
            <VerificationBadge verification={link.verification_status} />
        </span>
    );
}
