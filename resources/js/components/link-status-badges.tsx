import { Badge } from '@/components/ui/badge';
import {
    linkStatusBadgeClasses,
    linkStatusLabels,
    verificationBadgeClasses,
    verificationStatusLabels,
} from '@/lib/labels';
import type { Link, VerificationStatus } from '@/types/models';

/** A link's verification (0013): declared or verified on evidence. */
export function VerificationBadge({
    verification,
}: {
    verification: VerificationStatus;
}) {
    return (
        <Badge className={verificationBadgeClasses[verification]}>
            {verificationStatusLabels[verification]}
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
    return (
        <span className="flex flex-wrap items-center gap-1.5">
            <Badge className={linkStatusBadgeClasses[link.status]}>
                {linkStatusLabels[link.status]}
            </Badge>
            <VerificationBadge verification={link.verification_status} />
        </span>
    );
}
