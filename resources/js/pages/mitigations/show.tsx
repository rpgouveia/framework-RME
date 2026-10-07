import { Head, Link } from '@inertiajs/react';
import { DetailItem } from '@/components/detail-item';
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
    linkStatusBadgeClasses,
    linkStatusLabels,
    saeriCategoryLabels,
    uncertaintyBadgeClasses,
    uncertaintyLevelLabels,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { show as showLink } from '@/routes/links';
import { index, show } from '@/routes/mitigations';
import type { Link as RiskLink, Mitigation } from '@/types/models';

type Props = {
    mitigation: Mitigation;
};

export default function MitigationsShow({ mitigation }: Props) {
    const links = mitigation.links ?? [];

    return (
        <>
            <Head title={mitigation.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-2">
                    <h1 className="flex flex-wrap items-center gap-3 text-xl font-semibold tracking-tight">
                        {mitigation.name}
                        <Badge variant="outline">
                            {saeriCategoryLabels[mitigation.saeri_category]}
                        </Badge>
                    </h1>
                    <p className="text-muted-foreground text-sm">
                        Entrada do catálogo curado (C2). Mitigações não são
                        cadastradas nem editadas pela aplicação.
                    </p>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>Conteúdo</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-6">
                            <DetailItem label="Descrição">
                                <p className="font-normal whitespace-pre-line">
                                    {mitigation.description}
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
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Estimativas</CardTitle>
                        {/* RNF03: a qualitative estimate always comes with
                            its source and its uncertainty. */}
                        <CardDescription>
                            Sugestões do catálogo, com a fonte e a margem de
                            incerteza. O custo real de cada aplicação fica no
                            vínculo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label="Custo sugerido">
                                {costLevelLabels[mitigation.suggested_cost]}
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
                            <DetailItem label="Fonte">
                                <span className="font-normal">
                                    {mitigation.bibliography_source}
                                </span>
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

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
                            <Badge
                                className={linkStatusBadgeClasses[link.status]}
                            >
                                {linkStatusLabels[link.status]}
                            </Badge>
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
