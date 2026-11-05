import { Head, Link } from '@inertiajs/react';
import { FilterLink } from '@/components/filter-link';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { RiskSubdomainBadges } from '@/components/risk-subdomain-badges';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { labelFor } from '@/lib/enum-labels';
import { adverseEventNatureBadgeClasses } from '@/lib/labels';
import { termLabel } from '@/lib/names';
import { rowLink } from '@/lib/row-link';
import { create, index, show } from '@/routes/adverse-events';
import { show as showAiSystem } from '@/routes/ai-systems';
import type {
    AdverseEvent,
    AdverseEventNature,
    EnumOption,
    Paginated,
    TaxonomyCategory,
} from '@/types/models';

type Filters = {
    /** The MIT risk domain the list is narrowed to, if any. */
    domain: string | null;
    /** Incident or near miss, if any. */
    nature: AdverseEventNature | null;
};

type Props = {
    adverseEvents: Paginated<AdverseEvent>;
    filters: Filters;
    riskDomains: TaxonomyCategory[];
    natures: EnumOption[];
};

/** The list URL for a set of filters, leaving out the empty ones. */
function eventsWith(filters: Filters) {
    return index({
        query: {
            ...(filters.domain ? { domain: filters.domain } : {}),
            ...(filters.nature ? { nature: filters.nature } : {}),
        },
    });
}

export default function AdverseEventsIndex({
    adverseEvents,
    filters,
    riskDomains,
    natures,
}: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const domain = riskDomains.find((option) => option.code === filters.domain);
    const filtered = filters.domain !== null || filters.nature !== null;

    return (
        <>
            <Head title={t('Adverse events')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={t('Adverse events')}
                        description={t(
                            'What went wrong in the systems in operation, from the most recent to the oldest.',
                        )}
                    />
                    <Button asChild>
                        <Link href={create()}>
                            {t('Register adverse event')}
                        </Link>
                    </Button>
                </div>

                <nav
                    aria-label={t('Filter by nature')}
                    className="flex flex-wrap gap-2"
                >
                    <FilterLink
                        href={eventsWith({ ...filters, nature: null })}
                        active={filters.nature === null}
                    >
                        {t('All natures')}
                    </FilterLink>
                    {natures.map((option) => (
                        <FilterLink
                            key={option.value}
                            href={eventsWith({
                                ...filters,
                                nature: option.value as AdverseEventNature,
                            })}
                            active={filters.nature === option.value}
                        >
                            {labelFor(
                                enumLabels.AdverseEventNature,
                                option.value,
                            )}
                        </FilterLink>
                    ))}
                </nav>

                <nav
                    aria-label={t('Filter by risk domain')}
                    className="-mt-2 flex flex-wrap gap-2"
                >
                    <FilterLink
                        href={eventsWith({ ...filters, domain: null })}
                        active={filters.domain === null}
                        subtle
                    >
                        {t('All domains')}
                    </FilterLink>
                    {riskDomains.map((option) => (
                        <FilterLink
                            key={option.code}
                            href={eventsWith({
                                ...filters,
                                domain: option.code,
                            })}
                            active={filters.domain === option.code}
                            subtle
                        >
                            {termLabel(option)}
                        </FilterLink>
                    ))}
                </nav>

                {adverseEvents.data.length === 0 ? (
                    filtered ? (
                        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                            <p className="text-muted-foreground text-sm">
                                {domain
                                    ? t(
                                          'No adverse event in this filter materializes risks of the domain :domain.',
                                          { domain: termLabel(domain) },
                                      )
                                    : t('No adverse event in this filter.')}
                            </p>
                            <Button variant="outline" asChild>
                                <Link
                                    href={eventsWith({
                                        domain: null,
                                        nature: null,
                                    })}
                                >
                                    {t('See all events')}
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                            <p className="text-muted-foreground text-sm">
                                {t('No adverse event has been recorded.')}
                            </p>
                            <Button asChild>
                                <Link href={create()}>
                                    {t('Register adverse event')}
                                </Link>
                            </Button>
                        </div>
                    )
                ) : (
                    <>
                        <EventsTable events={adverseEvents.data} />
                        {adverseEvents.last_page > 1 && (
                            <PaginationLinks links={adverseEvents.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EventsTable({ events }: { events: AdverseEvent[] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <div className="border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Nature')}</TableHead>
                        <TableHead>{t('Risk subdomains')}</TableHead>
                        <TableHead>{t('System')}</TableHead>
                        <TableHead>{t('Occurrence')}</TableHead>
                        <TableHead>{t('Description')}</TableHead>
                        <TableHead className="text-right">
                            {t('Changes triggered')}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {events.map((event) => (
                        <TableRow
                            key={event.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(event.id))}
                        >
                            <TableCell>
                                <Badge
                                    className={
                                        adverseEventNatureBadgeClasses[
                                            event.nature
                                        ]
                                    }
                                >
                                    {
                                        enumLabels.AdverseEventNature[
                                            event.nature
                                        ]
                                    }
                                </Badge>
                            </TableCell>
                            <TableCell className="min-w-48 whitespace-normal">
                                <RiskSubdomainBadges
                                    subdomains={event.risk_subdomains}
                                />
                            </TableCell>
                            <TableCell className="min-w-40 whitespace-normal">
                                {event.ai_system && (
                                    <Link
                                        href={showAiSystem(event.ai_system.id)}
                                        className="hover:underline"
                                    >
                                        {event.ai_system.name}
                                    </Link>
                                )}
                            </TableCell>
                            <TableCell>
                                {formatDate(event.occurrence_date)}
                            </TableCell>
                            <TableCell className="min-w-56 whitespace-normal">
                                <Link
                                    href={show(event.id)}
                                    className="text-link hover:text-link-hover line-clamp-2 hover:underline"
                                >
                                    {event.description}
                                </Link>
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {event.status_histories_count ?? 0}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

AdverseEventsIndex.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('Adverse events'), href: index() }],
    };
};
