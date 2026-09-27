import { Head, Link } from '@inertiajs/react';
import RiskController from '@/actions/App/Http/Controllers/RiskController';
import { DeleteDialog } from '@/components/delete-dialog';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
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
import { truncate } from '@/lib/format';
import {
    lifecyclePhaseLabels,
    riskCategoryLabels,
    uncertaintyBadgeClasses,
    uncertaintyLevelLabels,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { show as showAiSystem } from '@/routes/ai-systems';
import { create, edit, index, show } from '@/routes/risks';
import type { Paginated, Risk } from '@/types/models';

type Props = {
    risks: Paginated<Risk>;
};

export default function RisksIndex({ risks }: Props) {
    return (
        <>
            <Head title="Riscos" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title="Riscos" />
                    <Button asChild>
                        <Link href={create()}>Cadastrar risco</Link>
                    </Button>
                </div>

                {risks.data.length === 0 ? (
                    <EmptyState />
                ) : (
                    <>
                        <RisksTable risks={risks.data} />
                        {risks.last_page > 1 && (
                            <PaginationLinks links={risks.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EmptyState() {
    return (
        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
            <p className="text-muted-foreground text-sm">
                Nenhum risco foi cadastrado ainda.
            </p>
            <Button asChild>
                <Link href={create()}>Cadastrar risco</Link>
            </Button>
        </div>
    );
}

function RisksTable({ risks }: { risks: Risk[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Descrição</TableHead>
                        <TableHead>Sistema</TableHead>
                        <TableHead>Categoria</TableHead>
                        <TableHead>Fase do ciclo de vida</TableHead>
                        <TableHead>Incerteza</TableHead>
                        <TableHead className="text-right">Vínculos</TableHead>
                        <TableHead>
                            <span className="sr-only">Ações</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {risks.map((risk) => (
                        <TableRow
                            key={risk.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(risk.id))}
                        >
                            <TableCell className="max-w-md font-medium whitespace-normal">
                                <Link
                                    href={show(risk.id)}
                                    className="line-clamp-2 hover:underline"
                                >
                                    {risk.description}
                                </Link>
                            </TableCell>
                            <TableCell>
                                {risk.ai_system && (
                                    <Link
                                        href={showAiSystem(risk.ai_system.id)}
                                        className="hover:underline"
                                    >
                                        {risk.ai_system.name}
                                    </Link>
                                )}
                            </TableCell>
                            <TableCell>
                                {riskCategoryLabels[risk.category]}
                            </TableCell>
                            <TableCell>
                                {lifecyclePhaseLabels[risk.lifecycle_phase]}
                            </TableCell>
                            <TableCell>
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
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {risk.links_count ?? 0}
                            </TableCell>
                            <TableCell>
                                <div className="flex items-center justify-end gap-3">
                                    <Link
                                        href={edit(risk.id)}
                                        className="text-muted-foreground hover:text-foreground text-sm hover:underline"
                                    >
                                        Editar
                                    </Link>
                                    {risk.links_count === 0 && (
                                        <DeleteRisk risk={risk} />
                                    )}
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function DeleteRisk({ risk }: { risk: Risk }) {
    return (
        <DeleteDialog
            form={RiskController.destroy.form(risk.id)}
            title="Excluir este risco?"
            description={`O risco "${truncate(risk.description, 80)}" será removido. Esta ação não pode ser desfeita.`}
            trigger={
                <button
                    type="button"
                    className="text-destructive text-sm hover:underline"
                >
                    Excluir
                </button>
            }
        />
    );
}

RisksIndex.layout = {
    breadcrumbs: [{ title: 'Riscos', href: index() }],
};
