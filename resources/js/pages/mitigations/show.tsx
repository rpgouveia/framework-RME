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
import {
    costLevelLabels,
    linkLabel,
    termLabel,
    uncertaintyBadgeClasses,
    uncertaintyLevelLabels,
} from '@/lib/labels';
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
    const links = mitigation.links ?? [];
    const subcategory = mitigation.saeri_subcategory;
    const category = subcategory?.parent;

    return (
        <>
            <Head title={mitigation.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-2">
                    <h1 className="flex flex-wrap items-center gap-3 text-xl font-semibold tracking-tight">
                        {mitigation.name}
                        {subcategory && (
                            <Badge variant="outline">
                                {termLabel(subcategory)}
                            </Badge>
                        )}
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Entrada do catálogo curado (C2). Mitigações não são
                        cadastradas nem editadas pela aplicação.
                    </p>
                </header>

                {catalog.fictional && <FictionalCatalogAlert />}

                <Card>
                    <CardHeader>
                        <CardTitle>Descrição</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="whitespace-pre-line">
                            {mitigation.description}
                        </p>
                    </CardContent>
                </Card>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* What comes from the source, kept apart from what the
                        framework adds, so each claim has its author. */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Taxonomia de Saeri et al.</CardTitle>
                            <CardDescription>
                                Classificação e rastreio até a fonte.{' '}
                                <a
                                    href={taxonomy.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="hover:underline"
                                >
                                    {taxonomy.version}
                                </a>
                                .
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4">
                                {category && (
                                    <DetailItem label="Categoria">
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
                                    <DetailItem label="Subcategoria">
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
                                <DetailItem label="Nome original">
                                    <span lang="en">
                                        {mitigation.source_name}
                                    </span>
                                </DetailItem>
                                <DetailItem label="Documento de origem">
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
                                <DetailItem label="Identificador na base">
                                    <code className="text-sm">
                                        {mitigation.source_reference}
                                    </code>
                                </DetailItem>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Contribuição do framework (C2)
                            </CardTitle>
                            {/* RNF03: every qualitative estimate comes with
                                its source and its uncertainty. */}
                            <CardDescription>
                                Saeri et al. não avaliam risco tratado, custo
                                nem eficácia: estes campos são do grupo, com a
                                fonte que os fundamenta. O custo real de cada
                                aplicação fica no vínculo.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid gap-4">
                                <DetailItem label="Subdomínios de risco tratados">
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
                                        Taxonomia de domínios do{' '}
                                        <a
                                            href={riskTaxonomy.url}
                                            target="_blank"
                                            rel="noreferrer"
                                            className="underline underline-offset-4"
                                        >
                                            MIT AI Risk Repository
                                        </a>{' '}
                                        ({riskTaxonomy.version}).
                                    </p>
                                </DetailItem>
                                <DetailItem label="Risco-alvo sugerido">
                                    <p className="font-normal whitespace-pre-line">
                                        {mitigation.suggested_target_risk}
                                    </p>
                                </DetailItem>
                                <DetailItem label="Evidência esperada">
                                    <p className="font-normal whitespace-pre-line">
                                        {mitigation.expected_evidence}
                                    </p>
                                </DetailItem>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <DetailItem label="Custo sugerido">
                                        {
                                            costLevelLabels[
                                                mitigation.suggested_cost
                                            ]
                                        }
                                    </DetailItem>
                                    <DetailItem label="Incerteza">
                                        <Badge
                                            className={
                                                uncertaintyBadgeClasses[
                                                    mitigation.uncertainty_level
                                                ]
                                            }
                                        >
                                            {
                                                uncertaintyLevelLabels[
                                                    mitigation.uncertainty_level
                                                ]
                                            }
                                        </Badge>
                                    </DetailItem>
                                </div>
                                <DetailItem label="Fonte da estimativa">
                                    <span className="font-normal">
                                        {mitigation.estimate_source}
                                    </span>
                                </DetailItem>
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            Vínculos que aplicam esta mitigação
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {links.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Esta mitigação ainda não foi aplicada a nenhum
                                risco.
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

function LinksTable({ links }: { links: RiskLink[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Vínculo</TableHead>
                    <TableHead>Responsável</TableHead>
                    <TableHead>Status</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {links.map((link) => (
                    <TableRow
                        key={link.id}
                        className="cursor-pointer"
                        onClick={rowLink(showLink(link.id))}
                    >
                        <TableCell className="whitespace-normal">
                            <Link
                                href={showLink(link.id)}
                                className="font-medium hover:underline"
                            >
                                {linkLabel(link)}
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

MitigationsShow.layout = ({ mitigation }: Props) => ({
    breadcrumbs: [
        { title: 'Catálogo de mitigações', href: index() },
        { title: mitigation.name, href: show(mitigation.id) },
    ],
});
