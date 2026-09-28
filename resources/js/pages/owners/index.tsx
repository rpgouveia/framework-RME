import { Head, Link } from '@inertiajs/react';
import OwnerController from '@/actions/App/Http/Controllers/OwnerController';
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
import { rowLink } from '@/lib/row-link';
import { create, index, show } from '@/routes/owners';
import type { Owner, Paginated } from '@/types/models';

type Props = {
    owners: Paginated<Owner>;
};

export default function OwnersIndex({ owners }: Props) {
    return (
        <>
            <Head title="Responsáveis" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Responsáveis"
                        description="Papéis organizacionais que respondem pelos vínculos."
                    />
                    <Button asChild>
                        <Link href={create()}>Cadastrar responsável</Link>
                    </Button>
                </div>

                {owners.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground text-sm">
                            Nenhum responsável foi cadastrado ainda. Todo
                            vínculo precisa de um papel que responda por ele.
                        </p>
                        <Button asChild>
                            <Link href={create()}>Cadastrar responsável</Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <OwnersTable owners={owners.data} />
                        {owners.last_page > 1 && (
                            <PaginationLinks links={owners.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function OwnersTable({ owners }: { owners: Owner[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Cargo</TableHead>
                        <TableHead>Área</TableHead>
                        <TableHead className="text-right">Vínculos</TableHead>
                        <TableHead>
                            <span className="sr-only">Ações</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {owners.map((owner) => (
                        <TableRow
                            key={owner.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(owner.id))}
                        >
                            <TableCell>
                                <span className="flex flex-wrap items-center gap-2">
                                    <Link
                                        href={show(owner.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {owner.organizational_role}
                                    </Link>
                                    {owner.deactivated_at !== null && (
                                        <Badge variant="outline">Inativo</Badge>
                                    )}
                                </span>
                            </TableCell>
                            <TableCell>{owner.area}</TableCell>
                            <TableCell className="text-right tabular-nums">
                                {owner.links_count ?? 0}
                            </TableCell>
                            <TableCell className="text-right">
                                {/* Only an owner the chain never used can go. */}
                                {(owner.links_count ?? 0) === 0 &&
                                    (owner.status_histories_count ?? 0) ===
                                        0 && (
                                        <DeleteDialog
                                            form={OwnerController.destroy.form(
                                                owner.id,
                                            )}
                                            title="Excluir este responsável?"
                                            description={`O responsável "${owner.organizational_role} · ${owner.area}" será removido. Esta ação não pode ser desfeita.`}
                                            trigger={
                                                <button
                                                    type="button"
                                                    className="text-destructive text-sm hover:underline"
                                                >
                                                    Excluir
                                                </button>
                                            }
                                        />
                                    )}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

OwnersIndex.layout = {
    breadcrumbs: [{ title: 'Responsáveis', href: index() }],
};
