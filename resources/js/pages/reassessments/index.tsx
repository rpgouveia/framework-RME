import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
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
import { index as linksIndex, show as showLink } from '@/routes/links';
import { index } from '@/routes/links/reassessments';
import { show } from '@/routes/reassessments';
import type { Link as RiskLink, Paginated, Reassessment } from '@/types/models';

type Props = {
    link: RiskLink;
    reassessments: Paginated<Reassessment>;
};

export default function ReassessmentsIndex({ link, reassessments }: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Reassessments')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Reassessments')}
                    description={t(
                        ':link. Each reassessment concludes a reversal and can be neither edited nor deleted.',
                        { link: linkLabel(link, t) },
                    )}
                />

                {reassessments.data.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        {t('This link has not been reassessed yet.')}
                    </p>
                ) : (
                    <>
                        <div className="rounded-xl border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>{t('Date')}</TableHead>
                                        <TableHead>{t('Outcome')}</TableHead>
                                        <TableHead>{t('Reversal')}</TableHead>
                                        <TableHead>{t('Cause')}</TableHead>
                                        <TableHead>
                                            {t('Who reassessed')}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {reassessments.data.map((reassessment) => (
                                        <TableRow
                                            key={reassessment.id}
                                            className="cursor-pointer"
                                            onClick={rowLink(
                                                show(reassessment.id),
                                            )}
                                        >
                                            <TableCell>
                                                <Link
                                                    href={show(reassessment.id)}
                                                    className="font-medium hover:underline"
                                                >
                                                    {formatDate(
                                                        reassessment.reassessment_date,
                                                    )}
                                                </Link>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="outline">
                                                    {
                                                        enumLabels
                                                            .ReassessmentOutcome[
                                                            reassessment.outcome
                                                        ]
                                                    }
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {reassessment.reversal &&
                                                    enumLabels.ChangeOrigin[
                                                        reassessment.reversal
                                                            .origin
                                                    ]}
                                            </TableCell>
                                            <TableCell>
                                                {
                                                    enumLabels.CauseStatus[
                                                        reassessment
                                                            .cause_status
                                                    ]
                                                }
                                            </TableCell>
                                            <TableCell>
                                                {
                                                    reassessment.owner
                                                        ?.organizational_role
                                                }
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        {reassessments.last_page > 1 && (
                            <PaginationLinks links={reassessments.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

ReassessmentsIndex.layout = ({
    link,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: linksIndex() },
            { title: linkLabel(link, t), href: showLink(link.id) },
            { title: t('Reassessments'), href: index(link.id) },
        ],
    };
};
