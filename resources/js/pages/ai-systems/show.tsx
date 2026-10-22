import { Head, Link } from '@inertiajs/react';
import AiSystemController from '@/actions/App/Http/Controllers/AiSystemController';
import { SystemChangesTable } from '@/components/system-changes-table';
import { DeleteDialog } from '@/components/delete-dialog';
import {
    UNACCEPTABLE_ALERT_ID,
    UnacceptableTierAlert,
} from '@/components/unacceptable-tier-alert';
import { DetailItem } from '@/components/detail-item';
import { RiskSubdomain } from '@/components/risk-subdomain';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
import { create as createAdverseEvent } from '@/routes/adverse-events';
import { categoryBadgeClasses } from '@/lib/labels';
import { edit, index, show } from '@/routes/ai-systems';
import {
    adverseEvents as adverseEventsCsv,
    csv as reportCsv,
    json as reportJson,
    systemChanges as systemChangesCsv,
} from '@/routes/ai-systems/report';
import { create as createSystemChange } from '@/routes/ai-systems/system-changes';
import { create as createRisk, show as showRisk } from '@/routes/risks';
import type { AiSystem, Risk } from '@/types/models';

type Props = {
    aiSystem: AiSystem;
};

export default function AiSystemsShow({ aiSystem }: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const risks = aiSystem.risks ?? [];

    return (
        <>
            <Head title={aiSystem.name} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="grid gap-1">
                        <div className="flex flex-wrap items-center gap-3">
                            <h1 className="text-[28px] leading-9 font-normal">
                                {aiSystem.name}
                            </h1>
                            <Badge
                                className={
                                    categoryBadgeClasses[aiSystem.category]
                                }
                            >
                                {enumLabels.AiSystemCategory[aiSystem.category]}
                            </Badge>
                        </div>
                        {aiSystem.application_domain && (
                            <p className="text-muted-foreground text-sm">
                                {aiSystem.application_domain}
                            </p>
                        )}
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link
                                href={createAdverseEvent({
                                    query: { ai_system: aiSystem.id },
                                })}
                            >
                                {t('Register adverse event')}
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={edit(aiSystem.id)}>{t('Edit')}</Link>
                        </Button>
                        {risks.length === 0 && (
                            <DeleteDialog
                                form={AiSystemController.destroy.form(
                                    aiSystem.id,
                                )}
                                title={t('Delete this AI system?')}
                                description={t(
                                    'The system ":name" will be removed from the portfolio. This action cannot be undone.',
                                    { name: aiSystem.name },
                                )}
                            />
                        )}
                    </div>
                </header>

                {aiSystem.category === 'unacceptable' && (
                    <UnacceptableTierAlert id={UNACCEPTABLE_ALERT_ID} />
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Details')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <DetailItem label={t('Origin')}>
                                {
                                    enumLabels.SystemSourceType[
                                        aiSystem.source_type
                                    ]
                                }
                            </DetailItem>
                            <DetailItem label={t('Risk tier')}>
                                {enumLabels.AiSystemCategory[aiSystem.category]}
                            </DetailItem>
                            <DetailItem label={t('Registration')}>
                                {formatDate(aiSystem.registration_date)}
                            </DetailItem>
                            <DetailItem label={t('Risks')}>
                                {risks.length}
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <CardTitle>{t('Risks')}</CardTitle>
                        <Button size="sm" asChild>
                            <Link
                                href={createRisk({
                                    query: { ai_system: aiSystem.id },
                                })}
                            >
                                {t('Register risk')}
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {risks.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t(
                                    'No risk has been mapped for this system yet.',
                                )}
                            </p>
                        ) : (
                            <RisksTable risks={risks} />
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <div className="grid gap-1.5">
                            <CardTitle>{t('System changes')}</CardTitle>
                            <CardDescription>
                                {t(
                                    'New model versions and changes to the database take the verified links to reassessment.',
                                )}
                            </CardDescription>
                        </div>
                        <Button size="sm" asChild>
                            <Link href={createSystemChange(aiSystem.id)}>
                                {t('Record change')}
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {(aiSystem.system_changes ?? []).length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t(
                                    'No change has been recorded for this system.',
                                )}
                            </p>
                        ) : (
                            <SystemChangesTable
                                changes={aiSystem.system_changes ?? []}
                            />
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Traceability report')}</CardTitle>
                        <CardDescription>
                            {t(
                                'The file holds the full chain of each risk of this system: the mitigations applied and the evidence proving them. The spreadsheets of adverse events and of system changes hold one row per record, including those that reverted no link.',
                            )}
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {/* Plain anchors: an Inertia Link expects an Inertia
                            response and would break on a file download. */}
                        <Button variant="outline" asChild>
                            <a href={reportCsv(aiSystem.id).url} download>
                                {t('Download CSV')}
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={reportJson(aiSystem.id).url} download>
                                {t('Download JSON')}
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a
                                href={adverseEventsCsv(aiSystem.id).url}
                                download
                            >
                                {t('Download adverse events (CSV)')}
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a
                                href={systemChangesCsv(aiSystem.id).url}
                                download
                            >
                                {t('Download system changes (CSV)')}
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function RisksTable({ risks }: { risks: Risk[] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>{t('Name')}</TableHead>
                    <TableHead>{t('Risk subdomain')}</TableHead>
                    <TableHead>{t('Lifecycle phase')}</TableHead>
                    <TableHead>{t('Uncertainty')}</TableHead>
                    <TableHead className="text-right">{t('Links')}</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {risks.map((risk) => (
                    <TableRow key={risk.id}>
                        <TableCell className="font-medium">
                            <Link
                                href={showRisk(risk.id)}
                                className="hover:underline"
                            >
                                {risk.name}
                            </Link>
                        </TableCell>
                        <TableCell>
                            <RiskSubdomain subdomain={risk.risk_subdomain} />
                        </TableCell>
                        <TableCell>
                            {enumLabels.LifecyclePhase[risk.lifecycle_phase]}
                        </TableCell>
                        <TableCell>
                            {
                                enumLabels.UncertaintyLevel[
                                    risk.uncertainty_level
                                ]
                            }
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {risk.links_count ?? 0}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

AiSystemsShow.layout = ({
    aiSystem,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('AI systems'), href: index() },
            { title: aiSystem.name, href: show(aiSystem.id) },
        ],
    };
};
