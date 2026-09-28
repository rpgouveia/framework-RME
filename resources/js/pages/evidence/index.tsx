import { Head, Link } from '@inertiajs/react';
import EvidenceController from '@/actions/App/Http/Controllers/EvidenceController';
import { DeleteDialog } from '@/components/delete-dialog';
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
import { formatDate } from '@/lib/format';
import { evidenceTypeLabels, linkLabel } from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { edit, show } from '@/routes/evidence';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { create, index } from '@/routes/links/evidence';
import type { Evidence, Link as RiskLink, Paginated } from '@/types/models';

type Props = {
    link: RiskLink;
    evidence: Paginated<Evidence>;
};

export default function EvidenceIndex({ link, evidence }: Props) {
    return (
        <>
            <Head title="Evidências" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title="Evidências" description={linkLabel(link)} />
                    <Button asChild>
                        <Link href={create(link.id)}>Registrar evidência</Link>
                    </Button>
                </div>

                {evidence.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground max-w-md text-sm">
                            Nenhuma evidência foi registrada para este vínculo.
                            Sem evidência, não há como comprovar que a mitigação
                            foi aplicada.
                        </p>
                        <Button asChild>
                            <Link href={create(link.id)}>
                                Registrar evidência
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <EvidenceTable evidence={evidence.data} />
                        {evidence.last_page > 1 && (
                            <PaginationLinks links={evidence.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EvidenceTable({ evidence }: { evidence: Evidence[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Descrição</TableHead>
                        <TableHead>Registrada em</TableHead>
                        <TableHead>
                            <span className="sr-only">Ações</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {evidence.map((item) => (
                        <TableRow
                            key={item.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(item.id))}
                        >
                            <TableCell className="font-medium">
                                <Link
                                    href={show(item.id)}
                                    className="hover:underline"
                                >
                                    {evidenceTypeLabels[item.type]}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <p className="max-w-md truncate">
                                    {item.description}
                                </p>
                            </TableCell>
                            <TableCell>
                                {formatDate(item.registration_date)}
                            </TableCell>
                            <TableCell>
                                <div className="flex items-center justify-end gap-3">
                                    <Link
                                        href={edit(item.id)}
                                        className="text-muted-foreground hover:text-foreground text-sm hover:underline"
                                    >
                                        Editar
                                    </Link>
                                    <DeleteDialog
                                        form={EvidenceController.destroy.form(
                                            item.id,
                                        )}
                                        title="Excluir esta evidência?"
                                        description="A evidência será removida do vínculo. Esta ação não pode ser desfeita."
                                        trigger={
                                            <button
                                                type="button"
                                                className="text-destructive text-sm hover:underline"
                                            >
                                                Excluir
                                            </button>
                                        }
                                    />
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

EvidenceIndex.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(link), href: showLink(link.id) },
        { title: 'Evidências', href: index(link.id) },
    ],
});
