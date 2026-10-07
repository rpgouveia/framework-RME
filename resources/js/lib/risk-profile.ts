/**
 * How the adverse event form orders the MIT risk subdomains for a system.
 *
 * The risk profile of a system is the set of subdomains of its risks. Those
 * are the events expected for it and come first; every other subdomain stays
 * on offer, since an event outside the profile may reveal a risk not yet
 * identified.
 */

/** A subdomain of a system's risk profile, as the server sends it. */
export type ExpectedSubdomain = { code: string; risks_count: number };

type Domain<S> = { code: string; name: string; children: S[] };

/** The taxonomy split for one system: its profile, then the rest. */
export type RiskProfileSplit<S> = {
    /** In taxonomy order, each with its domain and how many risks it has. */
    expected: (S & {
        risks_count: number;
        domain: { code: string; name: string };
    })[];
    /** Grouped by domain; a domain left with nothing is dropped. */
    others: Domain<S>[];
};

/**
 * Split the taxonomy into the subdomains a system expects and the others.
 * With no risks registered, nothing is expected and every domain is kept
 * whole.
 */
export function splitByRiskProfile<S extends { code: string }>(
    domains: Domain<S>[],
    expected: ExpectedSubdomain[],
): RiskProfileSplit<S> {
    const counts = new Map(
        expected.map((subdomain) => [subdomain.code, subdomain.risks_count]),
    );

    return {
        expected: domains.flatMap((domain) =>
            domain.children.flatMap((subdomain) => {
                const risks = counts.get(subdomain.code);

                return risks === undefined
                    ? []
                    : [
                          {
                              ...subdomain,
                              risks_count: risks,
                              domain: { code: domain.code, name: domain.name },
                          },
                      ];
            }),
        ),
        others: domains
            .map((domain) => ({
                ...domain,
                children: domain.children.filter(
                    (subdomain) => !counts.has(subdomain.code),
                ),
            }))
            .filter((domain) => domain.children.length > 0),
    };
}
