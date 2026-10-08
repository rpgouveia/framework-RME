import { index } from '@/routes/links';
import type { ChangeOrigin } from '@/types/models';

/** The verification filters of the link list: the pending lists of Tela 3. */
export type VerificationFilter =
    | 'awaiting_verification'
    | 'awaiting_reassessment'
    | 'verified';

/**
 * The link list URL for a verification filter, or for every link. Within
 * the reassessment list, an origin narrows it to the links whose last
 * reversal came from it (0019).
 */
export function linksWith(
    verification: VerificationFilter | null,
    origin: ChangeOrigin | null = null,
) {
    return index({
        query: {
            ...(verification ? { verification } : {}),
            ...(verification === 'awaiting_reassessment' && origin
                ? { origin }
                : {}),
        },
    });
}
