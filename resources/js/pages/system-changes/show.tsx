import { Head, Link } from '@inertiajs/react';
import { DetailItem } from '@/components/detail-item';
import { RiskSubdomain } from '@/components/risk-subdomain';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithEnumLabels } from '@/lib/enum-labels';
import type { WithTranslations } from '@/lib/i18n';
import { linkLabel } from '@/lib/names';
import {
    index as aiSystemsIndex,
    show as showAiSystem,
} from '@/routes/ai-systems';
import { index as changesIndex } from '@/routes/ai-systems/system-changes';
import { show as showLink } from '@/routes/links';
import { show as showReassessment } from '@/routes/reassessments';
import { create as createRisk } from '@/routes/risks';
import { show } from '@/routes/system-changes';
import type { SystemChange } from '@/types/models';

type Props = {
    systemChange: SystemChange;
    /** The affected subdomains with no risk registered for the system. */
    unmappedSubdomainCodes: string[];
};

export default function SystemChangesShow({
    systemChange,
    unmappedSubdomainCodes,
}: Props) {
    const enumLabels = useEnumLabels();
    const { t, tc } = useTranslations();
    const subdomains = systemChange.risk_subdomains ?? [];
    const reversals = systemChange.reversals ?? [];
    const reassessed = reversals.filter(
        (reversal) => reversal.reassessment,
    ).length;
    const title = enumLabels.SystemChangeType[systemChange.type];

    return (
        <>
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="grid gap-1">
                    <h1 className="text-[28px] leading-9 font-normal">
                        {title}
                    </h1>
                    {systemChange.ai_system && (
                        <Link
                            href={showAiSystem(systemChange.ai_system.id)}
                            className="text-muted-foreground text-sm hover:underline"
                        >
                            {systemChange.ai_system.name}
                        </Link>
                    )}
                </header>

                <p className="text-muted-foreground -mt-2 text-sm">
                    {t(
                        'System changes can be neither edited nor deleted: they record what changed and explain the reversals they caused.',
                    )}
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Details')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <DetailItem label={t('Type')}>{title}</DetailItem>
                            <DetailItem label={t('Change date')}>
                                {formatDate(systemChange.change_date)}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label={t('Description')}>
                                <p className="font-normal whitespace-pre-line">
                                    {systemChange.description}
                                </p>
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label={t('Risk subdomains affected')}>
                                {subdomains.length === 0 ? (
                                    <p className="text-muted-foreground font-normal">
                                        {t(
                                            'None given: the change reverted every verified link of the system.',
                                        )}
                                    </p>
                                ) : (
                                    <ul className="grid gap-3">
                                        {subdomains.map((subdomain) => (
                                            <li
                                                key={subdomain.code}
                                                className="grid gap-1"
                                            >
                                                <RiskSubdomain
                                                    subdomain={subdomain}
                                                />
                                                {unmappedSubdomainCodes.includes(
                                                    subdomain.code,
                                                ) && (
                                                    // A risk not yet mapped
                                                    // (0021, item 4).
                                                    <span className="flex flex-wrap items-center gap-2 text-sm font-normal text-amber-700 dark:text-amber-300">
                                                        {t(
                                                            'No risk registered in this system.',
                                                        )}
                                                        <Link
                                                            href={createRisk({
                                                                query: {
                                                                    ai_system:
                                                                        systemChange.ai_system_id,
                                                                    subdomain:
                                                                        subdomain.code,
                                                                },
                                                            })}
                                                            className="font-medium underline underline-offset-4"
                                                        >
                                                            {t('Register risk')}
                                                        </Link>
                                                    </span>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>
                            {t('Links reverted by the change')}
                        </CardTitle>
                        <CardDescription>
                            {t(
                                'On recording the change, the verified links it reaches went back to declared. Each one is reassessed separately.',
                            )}
                        </CardDescription>
                        {reversals.length > 0 && (
                            <p className="text-sm font-medium">
                                {tc(
                                    ':reassessed of :count reassessed',
                                    reversals.length,
                                    { reassessed },
                                )}
                            </p>
                        )}
                    </CardHeader>
                    <CardContent>
                        {reversals.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t(
                                    'No verified link was reverted by this change.',
                                )}
                            </p>
                        ) : (
                            <ul className="divide-y">
                                {reversals.map((reversal) => (
                                    <li
                                        key={reversal.id}
                                        className="flex flex-wrap items-center justify-between gap-4 py-2"
                                    >
                                        {reversal.link && (
                                            <Link
                                                href={showLink(
                                                    reversal.link_id,
                                                )}
                                                className="font-medium hover:underline"
                                            >
                                                {linkLabel(reversal.link, t)}
                                            </Link>
                                        )}
                                        {reversal.reassessment ? (
                                            <Link
                                                href={showReassessment(
                                                    reversal.reassessment.id,
                                                )}
                                                className="flex items-center gap-2 text-sm hover:underline"
                                            >
                                                <Badge variant="outline">
                                                    {
                                                        enumLabels
                                                            .ReassessmentOutcome[
                                                            reversal
                                                                .reassessment
                                                                .outcome
                                                        ]
                                                    }
                                                </Badge>
                                                {t('on :date', {
                                                    date: formatDate(
                                                        reversal.reassessment
                                                            .reassessment_date,
                                                    ),
                                                })}
                                            </Link>
                                        ) : (
                                            <span className="text-sm text-amber-700 dark:text-amber-300">
                                                {t('Awaiting reassessment')}
                                            </span>
                                        )}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

SystemChangesShow.layout = ({
    systemChange,
    translations,
    enumLabels,
}: Props & WithTranslations & WithEnumLabels) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('AI systems'), href: aiSystemsIndex() },
            ...(systemChange.ai_system
                ? [
                      {
                          title: systemChange.ai_system.name,
                          href: showAiSystem(systemChange.ai_system.id),
                      },
                      {
                          title: t('Changes'),
                          href: changesIndex(systemChange.ai_system.id),
                      },
                  ]
                : []),
            {
                title: enumLabels.SystemChangeType[systemChange.type],
                href: show(systemChange.id),
            },
        ],
    };
};
