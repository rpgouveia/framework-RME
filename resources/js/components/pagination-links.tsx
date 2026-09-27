import { Link } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
} from '@/components/ui/pagination';
import type { Paginated } from '@/types/models';

type Props = {
    links: Paginated<unknown>['links'];
};

/** Page navigation for a Laravel paginator. Render it only when there is more than one page. */
export function PaginationLinks({ links }: Props) {
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
