import { Head, Link } from '@inertiajs/react';
import { ShieldCheckIcon } from 'lucide-react';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { linkLabel } from '@/lib/names';
import { rowLink } from '@/lib/row-link';
import { show } from '@/routes/evidence';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { create, index } from '@/routes/links/evidence';
import type { Evidence, Link as RiskLink, Paginated } from '@/types/models';

type Props = {
    link: RiskLink;
    evidence: Paginated<Evidence>;
    /** The link is declared and its evidence now allows verifying it. */
    canVerify: boolean;
};

export default function EvidenceIndex({ link, evidence, canVerify }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Evidence')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={t('Evidence')}
                        description={linkLabel(link, t)}
                    />
                    <Button asChild>
                        <Link href={create(link.id)}>
                            {t('Register evidence')}
                        </Link>
                    </Button>
                </div>

                {canVerify && (
                    // The shortcut after registering evidence on a declared
                    // link (0018).
                    <Alert>
                        <ShieldCheckIcon aria-hidden />
                        <AlertTitle>
                            {t('This link can be verified')}
                        </AlertTitle>
                        <AlertDescription>
                            <p>
                                {t(
                                    'The link is declared and already has the evidence the verification requires.',
                                )}
                            </p>
                            <Button size="sm" asChild>
                                <Link
                                    href={showLink(link.id, {
                                        query: { verify: 1 },
                                    })}
                                >
                                    {t('Verify link')}
                                </Link>
                            </Button>
                        </AlertDescription>
                    </Alert>
                )}

                {evidence.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground max-w-md text-sm">
                            {t(
                                'No evidence has been registered for this link. Without evidence, there is no way to prove the mitigation was applied.',
                            )}
                        </p>
                        <Button asChild>
                            <Link href={create(link.id)}>
                                {t('Register evidence')}
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
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Type')}</TableHead>
                        <TableHead>{t('Description')}</TableHead>
                        <TableHead>{t('Observed cost')}</TableHead>
                        <TableHead>{t('Registered on')}</TableHead>
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
                                    {enumLabels.EvidenceType[item.type]}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <p className="max-w-md truncate">
                                    {item.description}
                                </p>
                            </TableCell>
                            <TableCell>
                                {item.observed_cost ? (
                                    enumLabels.CostLevel[item.observed_cost]
                                ) : (
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                )}
                            </TableCell>
                            <TableCell>
                                {formatDate(item.registration_date)}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

EvidenceIndex.layout = ({ link, translations }: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: linksIndex() },
            { title: linkLabel(link, t), href: showLink(link.id) },
            { title: t('Evidence'), href: index(link.id) },
        ],
    };
};
