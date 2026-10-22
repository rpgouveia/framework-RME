import { Head, Link } from '@inertiajs/react';
import { CostHelp } from '@/components/cost-help';
import {
    ProgressBadge,
    UncertaintyBadge,
    VerificationBadge,
} from '@/components/link-status-badges';
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
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="grid gap-2">
                    <h1 className="text-[28px] leading-9 font-normal">
                        {mitigation.name}
                    </h1>
                    <p className="text-muted-foreground flex flex-wrap items-center gap-2">
                        {subcategory && (
                            <Badge className="bg-tag-gray text-tag-gray-foreground border-transparent">
                                {termLabel(subcategory)}
                            </Badge>
                        )}
                        <span lang="en">{mitigation.source_name}</span>
                    </p>
                </header>

                {catalog.fictional && <FictionalCatalogAlert />}

                <div className="grid items-start gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(300px,1fr)]">
                    <div className="grid min-w-0 gap-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('What it is')}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="whitespace-pre-line">
                                    {mitigation.description}
                                </p>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Risks it treats')}</CardTitle>
                                <CardDescription>
                                    {interpolateNodes(
                                        t(
                                            'Subdomains of the :repository (:version). When creating a link, it shows as recommended for risks in these subdomains.',
                                            { version: riskTaxonomy.version },
                                        ),
                                        {
                                            repository: (
                                                <a
                                                    href={riskTaxonomy.url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="text-link hover:text-link-hover hover:underline"
                                                >
                                                    MIT AI Risk Repository
                                                </a>
                                            ),
                                        },
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-4">
                                <ul className="divide-y border-y">
                                    {mitigation.target_risk_subdomains?.map(
                                        (term) => (
                                            <li
                                                key={term.code}
                                                className="py-3"
                                            >
                                                <RiskSubdomain
                                                    subdomain={term}
                                                />
                                            </li>
                                        ),
                                    )}
                                </ul>
                                <dl>
                                    <DetailItem
                                        label={t('Suggested target risk')}
                                    >
                                        <p className="whitespace-pre-line">
                                            {mitigation.suggested_target_risk}
                                        </p>
                                    </DetailItem>
                                </dl>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>
                                    {t('Links that apply this mitigation')}{' '}
                                    <span className="text-muted-foreground font-normal tabular-nums">
                                        {links.length}
                                    </span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {links.length === 0 ? (
                                    <p className="text-muted-foreground border border-dashed p-6 text-center text-sm">
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

                    <aside className="grid gap-6">
                        {/* What the framework adds, kept apart from what
                            comes from the source, so each claim has its
                            author. */}
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('To apply')}</CardTitle>
                                <CardDescription>
                                    {t(
                                        "Saeri et al. assess neither the risk treated, nor cost, nor effectiveness: these fields are the group's, with the source that grounds them. The actual cost of each application is on the link.",
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="grid gap-5">
                                <dl className="grid grid-cols-2 gap-4">
                                    <DetailItem
                                        label={t('Suggested cost')}
                                        help={<CostHelp />}
                                    >
                                        {
                                            enumLabels.CostLevel[
                                                mitigation.suggested_cost
                                            ]
                                        }
                                    </DetailItem>
                                    <DetailItem label={t('Uncertainty')}>
                                        <UncertaintyBadge
                                            level={mitigation.uncertainty_level}
                                        />
                                    </DetailItem>
                                </dl>
                                <dl>
                                    <DetailItem label={t('Expected evidence')}>
                                        <p className="whitespace-pre-line">
                                            {mitigation.expected_evidence}
                                        </p>
                                    </DetailItem>
                                </dl>
                                <details className="group">
                                    <summary className="text-link hover:text-link-hover cursor-pointer text-sm">
                                        {t('Source of the estimate')}
                                    </summary>
                                    <p className="text-muted-foreground mt-2 text-sm">
                                        {mitigation.estimate_source}
                                    </p>
                                </details>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Provenance')}</CardTitle>
                                <CardDescription>
                                    {interpolateNodes(
                                        t(
                                            'Taxonomy of Saeri et al., :version.',
                                        ),
                                        {
                                            version: (
                                                <a
                                                    href={taxonomy.url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="text-link hover:text-link-hover hover:underline"
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
                                                className="text-muted-foreground block text-xs tracking-[0.32px]"
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
                                                className="text-muted-foreground block text-xs tracking-[0.32px]"
                                                lang="en"
                                            >
                                                {subcategory.original_name}
                                            </span>
                                            {subcategory.description && (
                                                <span
                                                    className="mt-1 block text-sm"
                                                    lang="en"
                                                >
                                                    {subcategory.description}
                                                </span>
                                            )}
                                        </DetailItem>
                                    )}
                                    <DetailItem label={t('Source document')}>
                                        {sourceDocument ? (
                                            <span lang="en">
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
                                        <code className="font-mono text-sm">
                                            {mitigation.source_reference}
                                        </code>
                                    </DetailItem>
                                </dl>
                            </CardContent>
                        </Card>
                    </aside>
                </div>
            </div>
        </>
    );
}

function LinksTable({ links }: { links: RiskLink[] }) {
    const { t } = useTranslations();

    return (
        <div className="overflow-x-auto border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Link')}</TableHead>
                        <TableHead>{t('Owner')}</TableHead>
                        <TableHead>{t('Progress')}</TableHead>
                        <TableHead>{t('Verification')}</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {links.map((link) => (
                        <TableRow
                            key={link.id}
                            className="cursor-pointer"
                            onClick={rowLink(showLink(link.id))}
                        >
                            <TableCell className="min-w-56 whitespace-normal">
                                <Link
                                    href={showLink(link.id)}
                                    className="text-link hover:text-link-hover hover:underline"
                                >
                                    {linkLabel(link, t)}
                                </Link>
                            </TableCell>
                            <TableCell className="whitespace-normal">
                                {link.owner && (
                                    <span className="grid gap-0.5">
                                        <span>
                                            {link.owner.organizational_role}
                                        </span>
                                        <span className="text-muted-foreground text-xs tracking-[0.32px]">
                                            {link.owner.area}
                                        </span>
                                    </span>
                                )}
                            </TableCell>
                            <TableCell>
                                <ProgressBadge status={link.status} />
                            </TableCell>
                            <TableCell>
                                <VerificationBadge
                                    verification={link.verification_status}
                                />
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
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
