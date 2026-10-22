import { Head, Link } from '@inertiajs/react';
import { WaitingDays } from '@/components/waiting-days';
import { LinkStatusBadges } from '@/components/link-status-badges';
import { ReviewDate } from '@/components/review-date';
import { FilterLink } from '@/components/filter-link';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
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
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { linksWith } from '@/lib/link-filters';
import type { VerificationFilter } from '@/lib/link-filters';
import { rowLink } from '@/lib/row-link';
import { create, index, show } from '@/routes/links';
import { show as showMitigation } from '@/routes/mitigations';
import { show as showRisk } from '@/routes/risks';
import type { ChangeOrigin, Link as RiskLink, Paginated } from '@/types/models';

type Props = {
    links: Paginated<RiskLink>;
    filters: {
        verification: VerificationFilter | null;
        /** Within the reassessment list, the origin of the last reversal. */
        origin: ChangeOrigin | null;
    };
};

/** The origins of a reversal, in the order the dashboard shows them. */
const reversalOrigins: ChangeOrigin[] = [
    'review_due',
    'adverse_event',
    'system_reclassification',
    'model_version',
    'data_change',
    'manual',
];

export default function LinksIndex({ links, filters }: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const verificationFilters: {
        value: VerificationFilter | null;
        label: string;
        empty: string;
    }[] = [
        {
            value: null,
            label: t('All links'),
            empty: t('No link has been created yet.'),
        },
        {
            value: 'awaiting_verification',
            label: t('Awaiting verification'),
            empty: t('No link awaits verification.'),
        },
        {
            value: 'awaiting_reassessment',
            label: t('Awaiting reassessment'),
            empty: t('No link awaits reassessment.'),
        },
        {
            value: 'verified',
            label: t('Verified links'),
            empty: t('No link is verified.'),
        },
    ];
    const current =
        verificationFilters.find(
            (option) => option.value === filters.verification,
        ) ?? verificationFilters[0];

    return (
        <>
            <Head title={t('Links')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={t('Links')}
                        description={t(
                            'Mitigations applied to each risk, ordered by the next review.',
                        )}
                    />
                    <Button asChild>
                        <Link href={create()}>{t('Create link')}</Link>
                    </Button>
                </div>

                <nav
                    aria-label={t('Filter by verification')}
                    className="flex flex-wrap gap-2"
                >
                    {verificationFilters.map((option) => (
                        <FilterLink
                            key={option.label}
                            href={linksWith(option.value)}
                            active={filters.verification === option.value}
                        >
                            {option.label}
                        </FilterLink>
                    ))}
                </nav>
                {filters.verification === 'awaiting_reassessment' && (
                    <nav
                        aria-label={t(
                            'Filter by the origin of the last reversal',
                        )}
                        className="-mt-2 flex flex-wrap gap-2"
                    >
                        <FilterLink
                            href={linksWith('awaiting_reassessment')}
                            active={filters.origin === null}
                            subtle
                        >
                            {t('All origins')}
                        </FilterLink>
                        {reversalOrigins.map((origin) => (
                            <FilterLink
                                key={origin}
                                href={linksWith(
                                    'awaiting_reassessment',
                                    origin,
                                )}
                                active={filters.origin === origin}
                                subtle
                            >
                                {enumLabels.ChangeOrigin[origin]}
                            </FilterLink>
                        ))}
                    </nav>
                )}
                {filters.verification !== null && (
                    <p className="text-muted-foreground -mt-2 text-sm">
                        {filters.verification === 'verified'
                            ? t('Verified links that were not cancelled.')
                            : filters.verification === 'awaiting_reassessment'
                              ? t(
                                    'Reverted links whose last reversal has not been reassessed yet, longest waiting first. Those of systems in the unacceptable tier show here to plan the discontinuation.',
                                )
                              : t(
                                    'Declared links waiting for the proof: those awaiting their first verification and the reassessed ones not yet verified. Left out are the cancelled ones and those of systems in the unacceptable tier, which cannot be verified.',
                                )}
                    </p>
                )}

                {links.data.length === 0 ? (
                    filters.verification ? (
                        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                            <p className="text-muted-foreground text-sm">
                                {current.empty}
                            </p>
                            <Button variant="outline" asChild>
                                <Link href={linksWith(null)}>
                                    {t('See all links')}
                                </Link>
                            </Button>
                        </div>
                    ) : (
                        <EmptyState />
                    )
                ) : (
                    <>
                        <LinksTable
                            links={links.data}
                            waiting={
                                filters.verification === 'awaiting_reassessment'
                            }
                        />
                        {links.last_page > 1 && (
                            <PaginationLinks links={links.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EmptyState() {
    const { t } = useTranslations();

    return (
        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
            <p className="text-muted-foreground text-sm">
                {t('No link has been created yet.')}
            </p>
            <Button asChild>
                <Link href={create()}>{t('Create link')}</Link>
            </Button>
        </div>
    );
}

function LinksTable({
    links,
    waiting,
}: {
    links: RiskLink[];
    /** The reassessment list: how long each link has waited (0020). */
    waiting: boolean;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Risk')}</TableHead>
                        <TableHead>{t('Mitigation')}</TableHead>
                        <TableHead>{t('Owner')}</TableHead>
                        <TableHead>{t('Status')}</TableHead>
                        {waiting ? (
                            <TableHead>{t('Waiting for')}</TableHead>
                        ) : (
                            <TableHead>{t('Next review')}</TableHead>
                        )}
                        <TableHead>{t('Estimated cost')}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {links.map((link) => (
                        <TableRow
                            key={link.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(link.id))}
                        >
                            <TableCell>
                                <div className="grid max-w-xs">
                                    <Link
                                        href={showRisk(link.risk_id)}
                                        className="font-medium hover:underline"
                                    >
                                        {link.risk?.name}
                                    </Link>
                                    <span className="text-muted-foreground truncate text-xs">
                                        {link.risk?.ai_system?.name}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                <Link
                                    href={showMitigation(link.mitigation_id)}
                                    className="hover:underline"
                                >
                                    {link.mitigation?.name}
                                </Link>
                            </TableCell>
                            <TableCell>
                                {link.owner && (
                                    <div className="grid">
                                        <span>
                                            {link.owner.organizational_role}
                                        </span>
                                        <span className="text-muted-foreground text-xs">
                                            {link.owner.area}
                                        </span>
                                    </div>
                                )}
                            </TableCell>
                            <TableCell>
                                <LinkStatusBadges link={link} />
                            </TableCell>
                            <TableCell>
                                {waiting && link.last_reversal ? (
                                    <WaitingDays
                                        since={link.last_reversal.change_date}
                                    />
                                ) : (
                                    <ReviewDate
                                        date={link.next_review_date}
                                        verification={link.verification_status}
                                        unacceptable={
                                            link.risk?.ai_system?.category ===
                                            'unacceptable'
                                        }
                                    />
                                )}
                            </TableCell>
                            <TableCell>
                                {enumLabels.CostLevel[link.estimated_cost]}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

LinksIndex.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('Links'), href: index() }],
    };
};
