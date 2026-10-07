import { Head, Link, router } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { FictionalCatalogAlert } from '@/components/fictional-catalog-alert';
import { FilterLink } from '@/components/filter-link';
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
    termLabel,
    uncertaintyBadgeClasses,
    uncertaintyLevelLabels,
} from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { index, show } from '@/routes/mitigations';
import type { Mitigation, Paginated, TaxonomyCategory } from '@/types/models';

type Filters = {
    category: string | null;
    subcategory: string | null;
    q: string;
};

type Props = {
    mitigations: Paginated<Mitigation>;
    filters: Filters;
    categories: TaxonomyCategory[];
    catalog: { fictional: boolean };
};

/** The catalogue URL for a set of filters, leaving out the empty ones. */
function catalogue(filters: Filters) {
    return index({
        query: {
            ...(filters.category ? { category: filters.category } : {}),
            ...(filters.subcategory
                ? { subcategory: filters.subcategory }
                : {}),
            ...(filters.q ? { q: filters.q } : {}),
        },
    });
}

export default function MitigationsIndex({
    mitigations,
    filters,
    categories,
    catalog,
}: Props) {
    // The server filters; every link and the search keep the other filters.
    function search(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        const value = new FormData(event.currentTarget).get('q');
        const q = typeof value === 'string' ? value.trim() : '';

        router.visit(catalogue({ ...filters, q }), { preserveState: true });
    }

    const category = categories.find(
        (option) => option.code === filters.category,
    );
    const filtered = filters.category !== null || filters.q !== '';

    return (
        <>
            <Head title="Catálogo de mitigações" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <Heading
                    title="Catálogo de mitigações"
                    description="O catálogo curado (C2), organizado pela taxonomia de Saeri et al. (2025): 4 categorias e 23 subcategorias. Toda mitigação aplicada a um risco vem daqui."
                />

                {catalog.fictional && <FictionalCatalogAlert />}

                <div className="grid gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <nav
                            aria-label="Filtrar por categoria"
                            className="flex flex-wrap gap-2"
                        >
                            <FilterLink
                                href={catalogue({
                                    ...filters,
                                    category: null,
                                    subcategory: null,
                                })}
                                active={filters.category === null}
                            >
                                Todas
                            </FilterLink>
                            {categories.map((option) => (
                                <FilterLink
                                    key={option.code}
                                    href={catalogue({
                                        ...filters,
                                        category: option.code,
                                        subcategory: null,
                                    })}
                                    active={filters.category === option.code}
                                >
                                    {termLabel(option)}
                                </FilterLink>
                            ))}
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
                                aria-label="Buscar pelo nome em português ou pelo nome original"
                                className="w-56"
                            />
                            <Button type="submit" variant="outline">
                                Buscar
                            </Button>
                        </form>
                    </div>

                    {/* The second level only makes sense inside a category. */}
                    {category && (
                        <nav
                            aria-label="Filtrar por subcategoria"
                            className="flex flex-wrap gap-2"
                        >
                            <FilterLink
                                href={catalogue({
                                    ...filters,
                                    subcategory: null,
                                })}
                                active={filters.subcategory === null}
                                subtle
                            >
                                Todas de {category.name}
                            </FilterLink>
                            {category.children.map((option) => (
                                <FilterLink
                                    key={option.code}
                                    href={catalogue({
                                        ...filters,
                                        subcategory: option.code,
                                    })}
                                    active={filters.subcategory === option.code}
                                    subtle
                                >
                                    {termLabel(option)}
                                </FilterLink>
                            ))}
                        </nav>
                    )}
                </div>

                {mitigations.data.length === 0 ? (
                    <EmptyState filters={filters} categories={categories} />
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

function EmptyState({
    filters,
    categories,
}: {
    filters: Filters;
    categories: TaxonomyCategory[];
}) {
    const category = categories.find(
        (option) => option.code === filters.category,
    );
    const subcategory = category?.children.find(
        (option) => option.code === filters.subcategory,
    );
    const scope = subcategory
        ? `na subcategoria ${termLabel(subcategory)}`
        : category
          ? `na categoria ${termLabel(category)}`
          : null;

    let message =
        'O catálogo está vazio. Ele é carregado a partir do arquivo de dados do projeto, pelo seeder.';

    if (filters.q !== '') {
        message = `Nenhuma mitigação com "${filters.q}" no nome${scope ? ` ${scope}` : ''}.`;
    } else if (scope) {
        message = `Nenhuma mitigação ${scope}.`;
    }

    return (
        <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed p-12 text-center">
            <p className="text-muted-foreground text-sm">{message}</p>
            {(filters.q !== '' || scope) && (
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
                        <TableHead>Subcategoria</TableHead>
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
                                <div className="grid max-w-sm">
                                    <Link
                                        href={show(mitigation.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {mitigation.name}
                                    </Link>
                                    <span
                                        className="text-muted-foreground truncate text-xs"
                                        lang="en"
                                    >
                                        {mitigation.source_name}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                {mitigation.saeri_subcategory && (
                                    <Badge
                                        variant="outline"
                                        className="max-w-xs whitespace-normal"
                                    >
                                        {termLabel(
                                            mitigation.saeri_subcategory,
                                        )}
                                    </Badge>
                                )}
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
