import { Head, Link } from '@inertiajs/react';
import { LinkStatusBadges } from '@/components/link-status-badges';
import { DetailItem } from '@/components/detail-item';
import { RiskSubdomain } from '@/components/risk-subdomain';
import { FictionalCatalogAlert } from '@/components/fictional-catalog-alert';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { interpolateNodes, translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { uncertaintyBadgeClasses } from '@/lib/labels';
import { linkLabel, termLabel } from '@/lib/names';
import { rowLink } from '@/lib/row-link';
import { show as showLink } from '@/routes/links';
import { index, show } from '@/routes/mitigations';
import type { Link as RiskLink, Mitigation } from '@/types/models';

type SourceDocument = {
    key: string;
    title: string;
    first_author: string;
    year: number;
};

type Props = {
    mitigation: Mitigation;
    sourceDocument: SourceDocument | null;
    taxonomy: { citation: string; version: string; url: string };
    /** The MIT AI risk domain taxonomy the target subdomains come from. */
    riskTaxonomy: { citation: string; version: string; url: string };
    catalog: { fictional: boolean };
};

export default function MitigationsShow({
    mitigation,
    sourceDocument,
    taxonomy,
    riskTaxonomy,
    catalog,
}: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const links = mitigation.links ?? [];
    const subcategory = mitigation.saeri_subcategory;
    const category = subcategory?.parent;

    return (
        <>
            <Head title={mitigation.name} />
            <div className="mx-auto flex h-full w-full max-w-[1560px] flex-1 flex-col gap-5 bg-[#faf8ff] p-4 text-[#161616] dark:bg-background dark:text-foreground lg:p-8">
                <header className="grid gap-3 border-b-2 border-[#004ccd] bg-white p-6 dark:bg-card">
                    <h1 className="flex flex-wrap items-center gap-3 text-3xl font-light tracking-tight">
                        {mitigation.name}
                        {subcategory && (
                            <Badge variant="outline">
                                {termLabel(subcategory)}
                            </Badge>
                        )}
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        {t(
                            'An entry of the curated catalogue (C2). Mitigations are neither registered nor edited by the application.',
                        )}
                    </p>
                </header>

                {catalog.fictional && <FictionalCatalogAlert />}

                <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <MetricTile label={t('Suggested cost')} value={enumLabels.CostLevel[mitigation.suggested_cost]} tone="neutral" />
                    <MetricTile label={t('Uncertainty')} value={enumLabels.UncertaintyLevel[mitigation.uncertainty_level]} tone="warning" />
                    <MetricTile label={t('Links')} value={String(links.length)} tone="primary" />
                    <MetricTile label={t('Risk subdomains treated')} value={String(mitigation.target_risk_subdomains?.length ?? 0)} tone="success" />
                </div>

                <Card className="rounded-none border-[#e0e0e0] bg-white shadow-none dark:bg-card">
                    <CardHeader>
                        <CardTitle>{t('Description')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="whitespace-pre-line">
                            {mitigation.description}
                        </p>
                    </CardContent>
                </Card>

                <div className="grid items-start gap-5 lg:grid-cols-12">
                    {/* What comes from the source, kept apart from what the
                        framework adds, so each claim has its author. */}
                    <Card className="order-2 rounded-none border-[#e0e0e0] bg-white shadow-none dark:bg-card lg:col-span-4">
                        <CardHeader>
                            <CardTitle>
                                {t('Taxonomy of Saeri et al.')}
                            </CardTitle>
                            <CardDescription>
                                {interpolateNodes(
                                    t(
                                        'Classification and tracing back to the source. :version.',
                                    ),
                                    {
                                        version: (
                                            <a
                                                href={taxonomy.url}
                                                target="_blank"
                                                rel="noreferrer"
                                                className="hover:underline"
                                            >
                                                {taxonomy.version}
                                            </a>
                                        ),
                                    },
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4">
                                {category && (
                                    <DetailItem label={t('Category')}>
                                        {termLabel(category)}
                                        <span
                                            className="text-muted-foreground block text-sm font-normal"
                                            lang="en"
                                        >
                                            {category.original_name}
                                        </span>
                                    </DetailItem>
                                )}
                                {subcategory && (
                                    <DetailItem label={t('Subcategory')}>
                                        {termLabel(subcategory)}
                                        <span
                                            className="text-muted-foreground block text-sm font-normal"
                                            lang="en"
                                        >
                                            {subcategory.original_name}
                                        </span>
                                        {subcategory.description && (
                                            <span
                                                className="mt-1 block text-sm font-normal"
                                                lang="en"
                                            >
                                                {subcategory.description}
                                            </span>
                                        )}
                                    </DetailItem>
                                )}
                                <DetailItem label={t('Original name')}>
                                    <span lang="en">
                                        {mitigation.source_name}
                                    </span>
                                </DetailItem>
                                <DetailItem label={t('Source document')}>
                                    {sourceDocument ? (
                                        <span className="font-normal">
                                            {sourceDocument.title} (
                                            {sourceDocument.first_author},{' '}
                                            {sourceDocument.year})
                                        </span>
                                    ) : (
                                        mitigation.source_document
                                    )}
                                </DetailItem>
                                <DetailItem
                                    label={t('Identifier in the database')}
                                >
                                    <code className="text-sm">
                                        {mitigation.source_reference}
                                    </code>
                                </DetailItem>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card className="order-1 rounded-none border-[#e0e0e0] bg-white shadow-none dark:bg-card lg:col-span-8">
                        <CardHeader>
                            <CardTitle>
                                {t('Contribution of the framework (C2)')}
                            </CardTitle>
                            {/* RNF03: every qualitative estimate comes with
                                its source and its uncertainty. */}
                            <CardDescription>
                                {t(
                                    "Saeri et al. assess neither the risk treated, nor cost, nor effectiveness: these fields are the group's, with the source that grounds them. The actual cost of each application is on the link.",
                                )}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4">
                                <DetailItem
                                    label={t('Risk subdomains treated')}
                                >
                                    <ul className="grid gap-2">
                                        {mitigation.target_risk_subdomains?.map(
                                            (term) => (
                                                <li key={term.code}>
                                                    <RiskSubdomain
                                                        subdomain={term}
                                                    />
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                    <p className="text-muted-foreground mt-2 text-xs font-normal">
                                        {interpolateNodes(
                                            t(
                                                'Domain taxonomy of the :repository (:version).',
                                                {
                                                    version:
                                                        riskTaxonomy.version,
                                                },
                                            ),
                                            {
                                                repository: (
                                                    <a
                                                        href={riskTaxonomy.url}
                                                        target="_blank"
                                                        rel="noreferrer"
                                                        className="underline underline-offset-4"
                                                    >
                                                        MIT AI Risk Repository
                                                    </a>
                                                ),
                                            },
                                        )}
                                    </p>
                                </DetailItem>
                                <DetailItem label={t('Suggested target risk')}>
                                    <p className="font-normal whitespace-pre-line">
                                        {mitigation.suggested_target_risk}
                                    </p>
                                </DetailItem>
                                <DetailItem label={t('Expected evidence')}>
                                    <p className="font-normal whitespace-pre-line">
                                        {mitigation.expected_evidence}
                                    </p>
                                </DetailItem>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <DetailItem label={t('Suggested cost')}>
                                        {
                                            enumLabels.CostLevel[
                                                mitigation.suggested_cost
                                            ]
                                        }
                                    </DetailItem>
                                    <DetailItem label={t('Uncertainty')}>
                                        <Badge
                                            className={
                                                uncertaintyBadgeClasses[
                                                    mitigation.uncertainty_level
                                                ]
                                            }
                                        >
                                            {
                                                enumLabels.UncertaintyLevel[
                                                    mitigation.uncertainty_level
                                                ]
                                            }
                                        </Badge>
                                    </DetailItem>
                                </div>
                                <DetailItem label={t('Source of the estimate')}>
                                    <span className="font-normal">
                                        {mitigation.estimate_source}
                                    </span>
                                </DetailItem>
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card className="rounded-none border-[#e0e0e0] bg-white shadow-none dark:bg-card">
                    <CardHeader>
                        <CardTitle>
                            {t('Links that apply this mitigation')}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {links.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t(
                                    'This mitigation has not been applied to any risk yet.',
                                )}
                            </p>
                        ) : (
                            <LinksTable links={links} />
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function MetricTile({ label, value, tone }: { label: string; value: string; tone: 'neutral' | 'warning' | 'primary' | 'success' }) {
    const borders = { neutral: 'border-[#525252]', warning: 'border-[#f1c21b]', primary: 'border-[#004ccd]', success: 'border-[#24a148]' };
    return (
        <div className={`flex min-h-28 flex-col justify-between border-t-2 bg-white p-4 dark:bg-card ${borders[tone]}`}>
            <span className="text-xs font-medium uppercase tracking-wider text-muted-foreground">{label}</span>
            <span className="mt-3 text-2xl font-light tabular-nums">{value}</span>
        </div>
    );
}

function LinksTable({ links }: { links: RiskLink[] }) {
    const { t } = useTranslations();

    return (
        <Table>
            <TableHeader className="bg-[#f4f4f4] dark:bg-muted">
                <TableRow>
                    <TableHead>{t('Link')}</TableHead>
                    <TableHead>{t('Owner')}</TableHead>
                    <TableHead>{t('Status')}</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {links.map((link) => (
                    <TableRow
                        key={link.id}
                        className="cursor-pointer hover:bg-[#f4f4f4] dark:hover:bg-muted"
                        onClick={rowLink(showLink(link.id))}
                    >
                        <TableCell className="whitespace-normal">
                            <Link
                                href={showLink(link.id)}
                                className="font-medium hover:underline"
                            >
                                {linkLabel(link, t)}
                            </Link>
                        </TableCell>
                        <TableCell>
                            {link.owner && (
                                <span className="grid">
                                    <span>
                                        {link.owner.organizational_role}
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        {link.owner.area}
                                    </span>
                                </span>
                            )}
                        </TableCell>
                        <TableCell>
                            <LinkStatusBadges link={link} />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

MitigationsShow.layout = ({
    mitigation,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Mitigation catalogue'), href: index() },
            { title: mitigation.name, href: show(mitigation.id) },
        ],
    };
};
