import { Head, Link, router } from '@inertiajs/react';
import { SearchIcon } from 'lucide-react';
import type { FormEvent } from 'react';
import { FictionalCatalogAlert } from '@/components/fictional-catalog-alert';
import { FilterLink } from '@/components/filter-link';
import { UncertaintyBadge } from '@/components/link-status-badges';
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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { termLabel } from '@/lib/names';
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
    const { t, tc } = useTranslations();

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
            <Head title={t('Mitigation catalogue')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-6 px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Mitigation catalogue')}
                    description={t(
                        'The catalogue curated by the group, organized by the taxonomy of Saeri et al. (2025): 4 categories and 23 subcategories. Every mitigation applied to a risk comes from here; the catalogue is read only.',
                    )}
                />

                {catalog.fictional && <FictionalCatalogAlert />}

                <div className="grid gap-3">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <nav
                            aria-label={t('Filter by category')}
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
                                {t('All')}
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
                            className="flex w-full gap-0 sm:w-auto"
                            role="search"
                        >
                            <div className="relative flex-1">
                                <SearchIcon
                                    className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                    aria-hidden
                                />
                                <Input
                                    type="search"
                                    name="q"
                                    defaultValue={filters.q}
                                    placeholder={t('Search by name')}
                                    aria-label={t(
                                        'Search by the name in Portuguese or by the original name',
                                    )}
                                    className="pl-9 sm:w-96"
                                />
                            </div>
                            <Button type="submit" variant="secondary">
                                {t('Search')}
                            </Button>
                        </form>
                    </div>

                    {/* The second level only makes sense inside a category. */}
                    {category && (
                        <nav
                            aria-label={t('Filter by subcategory')}
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
                                {t('All of :category', {
                                    category: category.name,
                                })}
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
                        {tc(
                            ':count mitigation found.|:count mitigations found.',
                            mitigations.total,
                        )}{' '}
                        <Link
                            href={index()}
                            className="text-link hover:text-link-hover hover:underline"
                        >
                            {t('Clear filters')}
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
    const { t } = useTranslations();
    const category = categories.find(
        (option) => option.code === filters.category,
    );
    const subcategory = category?.children.find(
        (option) => option.code === filters.subcategory,
    );
    const scope = subcategory
        ? t('in the subcategory :term', { term: termLabel(subcategory) })
        : category
          ? t('in the category :term', { term: termLabel(category) })
          : null;

    let message = t(
        "The catalogue is empty. It is loaded from the project's data file, by the seeder.",
    );

    if (filters.q !== '') {
        message = scope
            ? t('No mitigation with ":q" in the name :scope.', {
                  q: filters.q,
                  scope,
              })
            : t('No mitigation with ":q" in the name.', { q: filters.q });
    } else if (scope) {
        message = t('No mitigation :scope.', { scope });
    }

    return (
        <div className="flex flex-col items-center gap-3 border border-dashed p-12 text-center">
            <p className="text-muted-foreground text-sm">{message}</p>
            {(filters.q !== '' || scope) && (
                <Link
                    href={index()}
                    className="text-link hover:text-link-hover text-sm hover:underline"
                >
                    {t('See the whole catalogue')}
                </Link>
            )}
        </div>
    );
}

function MitigationsTable({ mitigations }: { mitigations: Mitigation[] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <div className="overflow-x-auto border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Name')}</TableHead>
                        <TableHead>{t('Subcategory')}</TableHead>
                        <TableHead>{t('Suggested cost')}</TableHead>
                        <TableHead>{t('Uncertainty')}</TableHead>
                        <TableHead className="text-right">
                            {t('Links')}
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {mitigations.map((mitigation) => (
                        <TableRow
                            key={mitigation.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(mitigation.id))}
                        >
                            <TableCell className="min-w-64 whitespace-normal">
                                <div className="grid max-w-sm gap-0.5">
                                    <Link
                                        href={show(mitigation.id)}
                                        className="text-link hover:text-link-hover hover:underline"
                                    >
                                        {mitigation.name}
                                    </Link>
                                    <span
                                        className="text-muted-foreground text-xs tracking-[0.32px]"
                                        lang="en"
                                    >
                                        {mitigation.source_name}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell>
                                {mitigation.saeri_subcategory && (
                                    <Badge className="bg-tag-gray text-tag-gray-foreground max-w-xs border-transparent whitespace-normal">
                                        {termLabel(
                                            mitigation.saeri_subcategory,
                                        )}
                                    </Badge>
                                )}
                            </TableCell>
                            <TableCell>
                                {
                                    enumLabels.CostLevel[
                                        mitigation.suggested_cost
                                    ]
                                }
                            </TableCell>
                            <TableCell>
                                <UncertaintyBadge
                                    level={mitigation.uncertainty_level}
                                />
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

MitigationsIndex.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('Mitigation catalogue'), href: index() }],
    };
};
