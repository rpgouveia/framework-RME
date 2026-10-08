import { index } from '@/routes/links';

/** The verification filters of the link list: the pending lists of Tela 3. */
export type VerificationFilter =
    | 'awaiting_first'
    | 'awaiting_reassessment'
    | 'verified';

/** The link list URL for a verification filter, or for every link. */
export function linksWith(verification: VerificationFilter | null) {
    return index({ query: verification ? { verification } : {} });
}
