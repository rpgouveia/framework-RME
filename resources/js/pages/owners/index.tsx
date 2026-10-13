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
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { rowLink } from '@/lib/row-link';
import { create, index, show } from '@/routes/owners';
import type { Owner, Paginated } from '@/types/models';

type Props = {
    owners: Paginated<Owner>;
};

export default function OwnersIndex({ owners }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Owners')} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={t('Owners')}
                        description={t(
                            'Organizational roles accountable for the links.',
                        )}
                    />
                    <Button asChild>
                        <Link href={create()}>{t('Register owner')}</Link>
                    </Button>
                </div>

                {owners.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground text-sm">
                            {t(
                                'No owner has been registered yet. Every link needs a role accountable for it.',
                            )}
                        </p>
                        <Button asChild>
                            <Link href={create()}>{t('Register owner')}</Link>
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
    const { t } = useTranslations();

    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Position')}</TableHead>
                        <TableHead>{t('Area')}</TableHead>
                        <TableHead className="text-right">
                            {t('Links')}
                        </TableHead>
                        <TableHead>
                            <span className="sr-only">{t('Actions')}</span>
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
                                        <Badge variant="outline">
                                            {t('Inactive')}
                                        </Badge>
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
                                            title={t('Delete this owner?')}
                                            description={t(
                                                'The owner ":role · :area" will be removed. This action cannot be undone.',
                                                {
                                                    role: owner.organizational_role,
                                                    area: owner.area,
                                                },
                                            )}
                                            trigger={
                                                <button
                                                    type="button"
                                                    className="text-destructive text-sm hover:underline"
                                                >
                                                    {t('Delete')}
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

OwnersIndex.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('Owners'), href: index() }],
    };
};
