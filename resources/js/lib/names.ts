import type { TranslateFn } from '@/lib/i18n';
import type { Link } from '@/types/models';

/**
 * Names a link by its risk and mitigation pair, which is its identity.
 * Falls back to the ids when the relations are not loaded.
 */
export function linkLabel(link: Link, t: TranslateFn): string {
    const risk = link.risk?.name ?? t('Risk #:id', { id: link.risk_id });
    const mitigation =
        link.mitigation?.name ??
        t('Mitigation #:id', { id: link.mitigation_id });

    return `${risk} → ${mitigation}`;
}

/** A taxonomy term as its code and name, such as "1.2 Gestão de riscos". */
export function termLabel(term: { code: string; name: string }): string {
    return `${term.code} ${term.name}`;
}

/** The codes of an adverse event's risk subdomains, such as "2.1, 2.2". */
export function subdomainCodes(event: {
    risk_subdomains?: { code: string }[];
}): string {
    return (event.risk_subdomains ?? [])
        .map((subdomain) => subdomain.code)
        .join(', ');
}
