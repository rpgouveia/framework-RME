/**
 * What recording an adverse event does to the links of its system (0019,
 * item 3), worked out on the form so the confirmation can say it. The server
 * works it out again, inside the transaction that records the event.
 */

/** A link of the system as the event form receives it. */
export type SystemLink = {
    id: number;
    risk: string;
    mitigation: string;
    /** The MIT subdomain of the link's risk. */
    subdomain: string;
    verification_status: 'declared' | 'verified';
};

export type EventReassessment<T> = {
    /** The verified links the event reverts to declared. */
    reverted: T[];
    /** The links that may be named as having intercepted a near miss. */
    interceptors: T[];
};

/**
 * The links an event on these subdomains reverts, and those it may name as
 * its interceptor. Both are the system's links still in the chain whose
 * risk is in one of the subdomains; the reverted ones are the verified ones,
 * except the interceptor, which the event shows to have worked.
 */
export function eventReassessment<T extends SystemLink>(
    links: T[],
    subdomains: string[],
    interceptorId: number | null,
): EventReassessment<T> {
    const touched = links.filter((link) => subdomains.includes(link.subdomain));

    return {
        reverted: touched.filter(
            (link) =>
                link.verification_status === 'verified' &&
                link.id !== interceptorId,
        ),
        interceptors: touched,
    };
}
