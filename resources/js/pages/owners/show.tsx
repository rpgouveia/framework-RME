import { Form, Head, Link } from '@inertiajs/react';
import OwnerController from '@/actions/App/Http/Controllers/OwnerController';
import { ReviewDate } from '@/components/review-date';
import { DeleteDialog } from '@/components/delete-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
    linkLabel,
    linkStatusBadgeClasses,
    linkStatusLabels,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { show as showLink } from '@/routes/links';
import { edit, index, show } from '@/routes/owners';
import type { Link as RiskLink, Owner } from '@/types/models';

type Props = {
    owner: Owner;
};

export default function OwnersShow({ owner }: Props) {
    const links = owner.links ?? [];
    const used =
        (owner.links_count ?? 0) > 0 || (owner.status_histories_count ?? 0) > 0;
    const active = owner.deactivated_at === null;

    return (
        <>
            <Head title={owner.organizational_role} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="grid gap-1">
                        <h1 className="flex flex-wrap items-center gap-3 text-xl font-semibold tracking-tight">
                            {owner.organizational_role}
                            {!active && (
                                <Badge variant="outline">Inativo</Badge>
                            )}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {owner.area}
                            {!active &&
                                owner.deactivated_at !== null &&
                                ` · desativado em ${formatDate(owner.deactivated_at)}`}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {/* Never used: free to edit or delete. Used: locked
                            and permanent, so it can only be retired. */}
                        {!used && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={edit(owner.id)}>Editar</Link>
                                </Button>
                                <DeleteDialog
                                    form={OwnerController.destroy.form(
                                        owner.id,
                                    )}
                                    title="Excluir este responsável?"
                                    description={`O responsável "${owner.organizational_role} · ${owner.area}" será removido. Esta ação não pode ser desfeita.`}
                                />
                            </>
                        )}
                        {used && active && <DeactivateOwner owner={owner} />}
                        {!active && (
                            <Form
                                {...OwnerController.reactivate.form(owner.id)}
                            >
                                {({ processing }) => (
                                    <Button type="submit" disabled={processing}>
                                        Reativar
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>Vínculos pelos quais responde</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {links.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Este responsável ainda não responde por nenhum
                                vínculo. Ele pode ser escolhido ao criar um
                                vínculo{active ? '' : ', depois de reativado'}.
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

function DeactivateOwner({ owner }: { owner: Owner }) {
    const activeLinks = owner.active_links_count ?? 0;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline">Desativar</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Desativar este responsável?</DialogTitle>
                {activeLinks > 0 ? (
                    <>
                        {/* The server refuses it too; say why up front. */}
                        <DialogDescription>
                            Este responsável ainda responde por {activeLinks}{' '}
                            {activeLinks === 1
                                ? 'vínculo ativo. Reatribua-o'
                                : 'vínculos ativos. Reatribua-os'}{' '}
                            antes de desativar.
                        </DialogDescription>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="secondary">Entendi</Button>
                            </DialogClose>
                        </DialogFooter>
                    </>
                ) : (
                    <>
                        <DialogDescription>
                            Ele deixa de ser oferecido na criação de vínculos e
                            no registro de mudanças de status. O histórico
                            continua mostrando o que ele registrou, e ele pode
                            ser reativado depois.
                        </DialogDescription>
                        <Form {...OwnerController.deactivate.form(owner.id)}>
                            {({ processing }) => (
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">
                                            Cancelar
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={processing}>
                                        Desativar
                                    </Button>
                                </DialogFooter>
                            )}
                        </Form>
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}

function LinksTable({ links }: { links: RiskLink[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Vínculo</TableHead>
                    <TableHead>Status</TableHead>
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
                        <TableCell className="whitespace-normal">
                            <Link
                                href={showLink(link.id)}
                                className="font-medium hover:underline"
                            >
                                {linkLabel(link)}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <Badge
                                className={linkStatusBadgeClasses[link.status]}
                            >
                                {linkStatusLabels[link.status]}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <ReviewDate date={link.next_review_date} />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

OwnersShow.layout = ({ owner }: Props) => ({
    breadcrumbs: [
        { title: 'Responsáveis', href: index() },
        { title: owner.organizational_role, href: show(owner.id) },
    ],
});
