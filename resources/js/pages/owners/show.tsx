import { Form, Head, Link } from '@inertiajs/react';
import OwnerController from '@/actions/App/Http/Controllers/OwnerController';
import { LinkStatusBadges } from '@/components/link-status-badges';
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
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { linkLabel } from '@/lib/names';
import { rowLink } from '@/lib/row-link';
import { show as showLink } from '@/routes/links';
import { edit, index, show } from '@/routes/owners';
import type { Link as RiskLink, Owner } from '@/types/models';

type Props = {
    owner: Owner;
};

export default function OwnersShow({ owner }: Props) {
    const { t } = useTranslations();
    const links = owner.links ?? [];
    const used =
        (owner.links_count ?? 0) > 0 || (owner.status_histories_count ?? 0) > 0;
    const active = owner.deactivated_at === null;

    return (
        <>
            <Head title={owner.organizational_role} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="grid gap-1">
                        <h1 className="flex flex-wrap items-center gap-3 text-[28px] leading-9 font-normal">
                            {owner.organizational_role}
                            {!active && (
                                <Badge variant="outline">{t('Inactive')}</Badge>
                            )}
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            {owner.deactivated_at === null
                                ? owner.area
                                : t(':area · deactivated on :date', {
                                      area: owner.area,
                                      date: formatDate(owner.deactivated_at),
                                  })}
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {/* Never used: free to edit or delete. Used: locked
                            and permanent, so it can only be retired. */}
                        {!used && (
                            <>
                                <Button variant="outline" asChild>
                                    <Link href={edit(owner.id)}>
                                        {t('Edit')}
                                    </Link>
                                </Button>
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
                                        {t('Reactivate')}
                                    </Button>
                                )}
                            </Form>
                        )}
                    </div>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Links it answers for')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {links.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {active
                                    ? t(
                                          'This owner does not answer for any link yet. It can be chosen when creating a link.',
                                      )
                                    : t(
                                          'This owner does not answer for any link yet. It can be chosen when creating a link, once reactivated.',
                                      )}
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
    const { t, tc } = useTranslations();
    const activeLinks = owner.active_links_count ?? 0;

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="outline">{t('Deactivate')}</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{t('Deactivate this owner?')}</DialogTitle>
                {activeLinks > 0 ? (
                    <>
                        {/* The server refuses it too; say why up front. */}
                        <DialogDescription>
                            {tc(
                                'This owner still answers for :count active link. Reassign it before deactivating.|This owner still answers for :count active links. Reassign them before deactivating.',
                                activeLinks,
                            )}
                        </DialogDescription>
                        <DialogFooter>
                            <DialogClose asChild>
                                <Button variant="secondary">
                                    {t('Got it')}
                                </Button>
                            </DialogClose>
                        </DialogFooter>
                    </>
                ) : (
                    <>
                        <DialogDescription>
                            {t(
                                'It is no longer offered when creating links or recording status changes. The history keeps showing what it recorded, and it can be reactivated later.',
                            )}
                        </DialogDescription>
                        <Form {...OwnerController.deactivate.form(owner.id)}>
                            {({ processing }) => (
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">
                                            {t('Cancel')}
                                        </Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={processing}>
                                        {t('Deactivate')}
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
    const { t } = useTranslations();

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>{t('Link')}</TableHead>
                    <TableHead>{t('Status')}</TableHead>
                    <TableHead>{t('Next review')}</TableHead>
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
                                {linkLabel(link, t)}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <LinkStatusBadges link={link} />
                        </TableCell>
                        <TableCell>
                            <ReviewDate
                                date={link.next_review_date}
                                verification={link.verification_status}
                                unacceptable={
                                    link.risk?.ai_system?.category ===
                                    'unacceptable'
                                }
                            />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

OwnersShow.layout = ({ owner, translations }: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Owners'), href: index() },
            { title: owner.organizational_role, href: show(owner.id) },
        ],
    };
};
