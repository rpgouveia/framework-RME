import { Head, Link } from '@inertiajs/react';
import RiskController from '@/actions/App/Http/Controllers/RiskController';
import { DeleteDialog } from '@/components/delete-dialog';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { RiskSubdomain } from '@/components/risk-subdomain';
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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { uncertaintyBadgeClasses } from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { show as showAiSystem } from '@/routes/ai-systems';
import { create, edit, index, show } from '@/routes/risks';
import type { Paginated, Risk } from '@/types/models';

type Props = {
    risks: Paginated<Risk>;
};

export default function RisksIndex({ risks }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Risks')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <div className="flex items-start justify-between gap-4">
                    <Heading title={t('Risks')} />
                    <Button asChild>
                        <Link href={create()}>{t('Register risk')}</Link>
                    </Button>
                </div>

                {risks.data.length === 0 ? (
                    <EmptyState />
                ) : (
                    <>
                        <RisksTable risks={risks.data} />
                        {risks.last_page > 1 && (
                            <PaginationLinks links={risks.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EmptyState() {
    const { t } = useTranslations();

    return (
        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
            <p className="text-muted-foreground text-sm">
                {t('No risk has been registered yet.')}
            </p>
            <Button asChild>
                <Link href={create()}>{t('Register risk')}</Link>
            </Button>
        </div>
    );
}

function RisksTable({ risks }: { risks: Risk[] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Name')}</TableHead>
                        <TableHead>{t('System')}</TableHead>
                        <TableHead>{t('Risk subdomain')}</TableHead>
                        <TableHead>{t('Lifecycle phase')}</TableHead>
                        <TableHead>{t('Uncertainty')}</TableHead>
                        <TableHead className="text-right">
                            {t('Links')}
                        </TableHead>
                        <TableHead>
                            <span className="sr-only">{t('Actions')}</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {risks.map((risk) => (
                        <TableRow
                            key={risk.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(risk.id))}
                        >
                            <TableCell>
                                {/* The name identifies the risk; one line of
                                    the description gives context without
                                    taking a column of its own. */}
                                <div className="grid max-w-xs">
                                    <Link
                                        href={show(risk.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {risk.name}
                                    </Link>
                                    <p className="text-muted-foreground truncate text-xs">
                                        {risk.description}
                                    </p>
                                </div>
                            </TableCell>
                            <TableCell>
                                {risk.ai_system && (
                                    <Link
                                        href={showAiSystem(risk.ai_system.id)}
                                        className="hover:underline"
                                    >
                                        {risk.ai_system.name}
                                    </Link>
                                )}
                            </TableCell>
                            <TableCell>
                                <RiskSubdomain
                                    subdomain={risk.risk_subdomain}
                                />
                            </TableCell>
                            <TableCell>
                                {
                                    enumLabels.LifecyclePhase[
                                        risk.lifecycle_phase
                                    ]
                                }
                            </TableCell>
                            <TableCell>
                                <Badge
                                    className={
                                        uncertaintyBadgeClasses[
                                            risk.uncertainty_level
                                        ]
                                    }
                                >
                                    {
                                        enumLabels.UncertaintyLevel[
                                            risk.uncertainty_level
                                        ]
                                    }
                                </Badge>
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {risk.links_count ?? 0}
                            </TableCell>
                            <TableCell>
                                <div className="flex items-center justify-end gap-3">
                                    <Link
                                        href={edit(risk.id)}
                                        className="text-muted-foreground hover:text-foreground text-sm hover:underline"
                                    >
                                        {t('Edit')}
                                    </Link>
                                    {risk.links_count === 0 && (
                                        <DeleteRisk risk={risk} />
                                    )}
                                </div>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

function DeleteRisk({ risk }: { risk: Risk }) {
    const { t } = useTranslations();

    return (
        <DeleteDialog
            form={RiskController.destroy.form(risk.id)}
            title={t('Delete this risk?')}
            description={t(
                'The risk ":name" will be removed. This action cannot be undone.',
                { name: risk.name },
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
    );
}

RisksIndex.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('Risks'), href: index() }],
    };
};
