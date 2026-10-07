import { Head, Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    labelFor,
    saeriCategoryLabels,
    uncertaintyBadgeClasses,
    uncertaintyLevelLabels,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { index, show } from '@/routes/mitigations';
import type { EnumOption, Mitigation, Paginated } from '@/types/models';

type Filters = {
    saeri_category: string | null;
    q: string;
};

type Props = {
    mitigations: Paginated<Mitigation>;
    filters: Filters;
    saeriCategories: EnumOption[];
};

/** The catalogue URL for a set of filters, leaving out the empty ones. */
function catalogue(filters: Filters) {
    return index({
        query: {
            ...(filters.saeri_category
                ? { saeri_category: filters.saeri_category }
                : {}),
            ...(filters.q ? { q: filters.q } : {}),
        },
    });
}

export default function MitigationsIndex({
    mitigations,
    filters,
    saeriCategories,
}: Props) {
    // The server filters; the search keeps the category, and the category
    // links keep the search.
    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const value = new FormData(event.currentTarget).get('q');
        const q = typeof value === 'string' ? value.trim() : '';

        router.visit(catalogue({ ...filters, q }), { preserveState: true });
    }

    const filtered = filters.saeri_category !== null || filters.q !== '';

    return (
        <>
            <Head title="Catálogo de mitigações" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Catálogo de mitigações"
                    description="O catálogo curado (C2), organizado pelas quatro categorias de Saeri et al. Toda mitigação aplicada a um risco vem daqui."
                />

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <nav
                        aria-label="Filtrar por categoria SAERI"
                        className="flex flex-wrap gap-2"
                    >
                        {[
                            { value: null, label: 'Todas' },
                            ...saeriCategories.map((option) => ({
                                value: option.value,
                                label: labelFor(
                                    saeriCategoryLabels,
                                    option.value,
                                ),
                            })),
                        ].map((option) => {
                            const active =
                                filters.saeri_category === option.value;

                            return (
                                <Button
                                    key={option.label}
                                    size="sm"
                                    variant={active ? 'default' : 'outline'}
                                    asChild
                                >
                                    <Link
                                        href={catalogue({
                                            ...filters,
                                            saeri_category: option.value,
                                        })}
                                        preserveState
                                        preserveScroll
                                        aria-current={
                                            active ? 'page' : undefined
                                        }
                                    >
                                        {option.label}
                                    </Link>
                                </Button>
                            );
                        })}
                    </nav>

                    <form
                        onSubmit={search}
                        className="flex gap-2"
                        role="search"
                    >
                        <Input
                            name="q"
                            defaultValue={filters.q}
                            placeholder="Buscar pelo nome"
                            aria-label="Buscar mitigação pelo nome"
                            className="w-56"
                        />
                        <Button type="submit" variant="outline">
                            Buscar
                        </Button>
                    </form>
                </div>

                {mitigations.data.length === 0 ? (
                    <EmptyState filters={filters} />
                ) : (
                    <>
                        <MitigationsTable mitigations={mitigations.data} />
                        {mitigations.last_page > 1 && (
                            <PaginationLinks links={mitigations.links} />
                        )}
                    </>
                )}

                {filtered && mitigations.data.length > 0 && (
                    <p className="text-muted-foreground text-sm">
                        {mitigations.total}{' '}
                        {mitigations.total === 1
                            ? 'mitigação encontrada'
                            : 'mitigações encontradas'}
                        .{' '}
                        <Link
                            href={index()}
                            className="text-foreground hover:underline"
                        >
                            Limpar filtros
                        </Link>
                    </p>
                )}
            </div>
        </>
    );
}

function EmptyState({ filters }: { filters: Filters }) {
    const category = filters.saeri_category
        ? labelFor(saeriCategoryLabels, filters.saeri_category)
        : null;

    let message =
        'O catálogo está vazio. Ele é carregado a partir do arquivo de dados do projeto, pelo seeder.';

    if (filters.q !== '') {
        message = category
            ? `Nenhuma mitigação de ${category} com "${filters.q}" no nome.`
            : `Nenhuma mitigação com "${filters.q}" no nome.`;
    } else if (category) {
        message = `Nenhuma mitigação na categoria ${category}.`;
    }

    return (
        <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center">
            <p className="text-muted-foreground text-sm">{message}</p>
            {(filters.q !== '' || category) && (
                <Link
                    href={index()}
                    className="text-sm font-medium hover:underline"
                >
                    Ver o catálogo inteiro
                </Link>
            )}
        </div>
    );
}

function MitigationsTable({ mitigations }: { mitigations: Mitigation[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome</TableHead>
                        <TableHead>Categoria SAERI</TableHead>
                        <TableHead>Custo sugerido</TableHead>
                        <TableHead>Incerteza</TableHead>
                        <TableHead className="text-right">Vínculos</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {mitigations.map((mitigation) => (
                        <TableRow
                            key={mitigation.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(mitigation.id))}
                        >
                            <TableCell>
                                <Link
                                    href={show(mitigation.id)}
                                    className="font-medium hover:underline"
                                >
                                    {mitigation.name}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">
                                    {
                                        saeriCategoryLabels[
                                            mitigation.saeri_category
                                        ]
                                    }
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {costLevelLabels[mitigation.suggested_cost]}
                            </TableCell>
                            <TableCell>
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
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {mitigation.links_count ?? 0}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

MitigationsIndex.layout = {
    breadcrumbs: [{ title: 'Catálogo de mitigações', href: index() }],
};
