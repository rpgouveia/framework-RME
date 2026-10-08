/**
 * What recording a change of the system does to its links (0021, item 2),
 * worked out on the form so the confirmation can say it. The server works it
 * out again, inside the transaction that records the change.
 */

/** A verified link of the system, with its risk's MIT subdomain. */
export type VerifiedLink = {
    id: number;
    risk: string;
    mitigation: string;
    subdomain: string;
};

/**
 * The verified links a change reverts: those whose risk is in one of the
 * affected subdomains when any is given, every one of them otherwise, since
 * without an analysis of its reach no evidence can be said to still hold.
 * A system in the unacceptable tier has no verified link to revert.
 */
export function systemChangeReassessment<T extends VerifiedLink>(
    links: T[],
    subdomains: string[],
): T[] {
    return subdomains.length === 0
        ? links
        : links.filter((link) => subdomains.includes(link.subdomain));
}
