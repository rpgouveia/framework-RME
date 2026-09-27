import { Head, Link } from '@inertiajs/react';
import RiskController from '@/actions/App/Http/Controllers/RiskController';
import { DeleteDialog } from '@/components/delete-dialog';
import { DetailItem } from '@/components/detail-item';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import {
    costLevelLabels,
    lifecyclePhaseLabels,
    linkStatusLabels,
    riskCategoryLabels,
    uncertaintyBadgeClasses,
    uncertaintyLevelLabels,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { show as showAiSystem } from '@/routes/ai-systems';
import { create as createLink, show as showLink } from '@/routes/links';
import { edit, index, show } from '@/routes/risks';
import type { Link as RiskLink, Risk } from '@/types/models';

type Props = {
    risk: Risk;
};

export default function RisksShow({ risk }: Props) {
    const links = risk.links ?? [];

    return (
        <>
            <Head title={risk.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <h1 className="max-w-3xl text-xl font-semibold tracking-tight">
                        {risk.name}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(risk.id)}>Editar</Link>
                        </Button>
                        {links.length === 0 && (
                            <DeleteDialog
                                form={RiskController.destroy.form(risk.id)}
                                title="Excluir este risco?"
                                description={`O risco "${risk.name}" será removido. Esta ação não pode ser desfeita.`}
                            />
                        )}
                    </div>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl>
                            <DetailItem label="Descrição">
                                <p className="max-w-prose font-normal whitespace-pre-line">
                                    {risk.description}
                                </p>
                            </DetailItem>
                        </dl>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <DetailItem label="Sistema de IA">
                                {risk.ai_system && (
                                    <Link
                                        href={showAiSystem(risk.ai_system.id)}
                                        className="hover:underline"
                                    >
                                        {risk.ai_system.name}
                                    </Link>
                                )}
                            </DetailItem>
                            <DetailItem label="Categoria">
                                {riskCategoryLabels[risk.category]}
                            </DetailItem>
                            <DetailItem label="Fase do ciclo de vida">
                                {lifecyclePhaseLabels[risk.lifecycle_phase]}
                            </DetailItem>
                            <DetailItem label="Incerteza">
                                <Badge
                                    className={
                                        uncertaintyBadgeClasses[
                                            risk.uncertainty_level
                                        ]
                                    }
                                >
                                    {
                                        uncertaintyLevelLabels[
                                            risk.uncertainty_level
                                        ]
                                    }
                                </Badge>
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <CardTitle>Vínculos</CardTitle>
                        <Button size="sm" asChild>
                            <Link href={createLink()}>Criar vínculo</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {links.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Este risco foi identificado, mas ainda não tem
                                nenhuma mitigação associada. Crie um vínculo
                                para definir como ele será tratado e quem
                                responde por isso.
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
                    <TableHead>Mitigação</TableHead>
                    <TableHead>Responsável</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Custo estimado</TableHead>
                    <TableHead>Próxima revisão</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {links.map((link) => (
                    <TableRow
                        key={link.id}
                        className="cursor-pointer"
                        onClick={rowLink(showLink(link.id))}
                    >
                        <TableCell className="max-w-md whitespace-normal">
                            <Link
                                href={showLink(link.id)}
                                className="font-medium hover:underline"
                            >
                                {link.mitigation?.description}
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
                            <Badge variant="outline">
                                {linkStatusLabels[link.status]}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            {costLevelLabels[link.estimated_cost]}
                        </TableCell>
                        <TableCell>
                            {formatDate(link.next_review_date)}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

RisksShow.layout = ({ risk }: Props) => ({
    breadcrumbs: [
        { title: 'Riscos', href: index() },
        { title: risk.name, href: show(risk.id) },
    ],
});
