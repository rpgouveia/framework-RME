import { Head, Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
} from '@/components/ui/pagination';
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
    categoryBadgeClasses,
    categoryLabels,
    sourceTypeLabels,
} from '@/lib/labels';
import { create, edit, index, show } from '@/routes/ai-systems';
import type { AiSystem, Paginated } from '@/types/models';

type Props = {
    aiSystems: Paginated<AiSystem>;
};

export default function AiSystemsIndex({ aiSystems }: Props) {
    return (
        <>
            <Head title="Sistemas de IA" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title="Sistemas de IA" />
                    <Button asChild>
                        <Link href={create()}>Cadastrar sistema de IA</Link>
                    </Button>
                </div>

                {aiSystems.data.length === 0 ? (
                    <EmptyState />
                ) : (
                    <>
                        <SystemsTable systems={aiSystems.data} />
                        {aiSystems.last_page > 1 && (
                            <SystemsPagination links={aiSystems.links} />
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
                Nenhum sistema de IA foi cadastrado ainda.
            </p>
            <Button asChild>
                <Link href={create()}>Cadastrar sistema de IA</Link>
            </Button>
        </div>
    );
}

function SystemsTable({ systems }: { systems: AiSystem[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome</TableHead>
                        <TableHead>Origem</TableHead>
                        <TableHead>Categoria</TableHead>
                        <TableHead>Cadastro</TableHead>
                        <TableHead className="text-right">Riscos</TableHead>
                        <TableHead>
                            <span className="sr-only">Ações</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {systems.map((system) => (
                        <TableRow key={system.id}>
                            <TableCell className="font-medium">
                                <Link
                                    href={show(system.id)}
                                    className="hover:underline"
                                >
                                    {system.name}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">
                                    {sourceTypeLabels[system.source_type]}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    className={
                                        categoryBadgeClasses[system.category]
                                    }
                                >
                                    {categoryLabels[system.category]}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {formatDate(system.registration_date)}
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {system.risks_count ?? 0}
                            </TableCell>
                            <TableCell className="text-right">
                                <Link
                                    href={edit(system.id)}
                                    className="text-muted-foreground hover:text-foreground text-sm hover:underline"
                                >
                                    Editar
                                </Link>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function SystemsPagination({ links }: { links: Paginated<AiSystem>['links'] }) {
    const lastIndex = links.length - 1;

    return (
        <Pagination>
            <PaginationContent>
                {links.map((link, i) => {
                    // Laravel puts "previous" first and "next" last; the rest
                    // are page numbers plus "..." separators with no url.
                    const isPrevious = i === 0;
                    const isNext = i === lastIndex;

                    if (!isPrevious && !isNext && link.url === null) {
                        return (
                            <PaginationItem key={i}>
                                <PaginationEllipsis />
                            </PaginationItem>
                        );
                    }

                    const content = isPrevious ? (
                        <>
                            <ChevronLeftIcon />
                            <span className="hidden sm:block">Anterior</span>
                        </>
                    ) : isNext ? (
                        <>
                            <span className="hidden sm:block">Próxima</span>
                            <ChevronRightIcon />
                        </>
                    ) : (
                        link.label
                    );

                    return (
                        <PaginationItem key={i}>
                            <PaginationLink
                                asChild
                                isActive={link.active}
                                size={isPrevious || isNext ? 'default' : 'icon'}
                                aria-label={
                                    isPrevious
                                        ? 'Ir para a página anterior'
                                        : isNext
                                          ? 'Ir para a próxima página'
                                          : undefined
                                }
                                className={
                                    isPrevious || isNext
                                        ? 'gap-1 px-2.5'
                                        : undefined
                                }
                            >
                                {link.url === null ? (
                                    <span
                                        aria-disabled="true"
                                        className="pointer-events-none opacity-50"
                                    >
                                        {content}
                                    </span>
                                ) : (
                                    <Link
                                        href={link.url}
                                        preserveScroll
                                        preserveState
                                    >
                                        {content}
                                    </Link>
                                )}
                            </PaginationLink>
                        </PaginationItem>
                    );
                })}
            </PaginationContent>
        </Pagination>
    );
}

AiSystemsIndex.layout = {
    breadcrumbs: [{ title: 'Sistemas de IA', href: index() }],
};
