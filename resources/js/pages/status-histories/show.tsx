import { Head, Link } from '@inertiajs/react';
import { EntryAuthor } from '@/components/entry-author';
import { DetailItem } from '@/components/detail-item';
import { RiskSubdomain } from '@/components/risk-subdomain';
import { StatusTransition } from '@/components/status-transition';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { linkLabel } from '@/lib/names';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { index as historyIndex } from '@/routes/links/status-histories';
import { show as showEvidence } from '@/routes/evidence';
import { show } from '@/routes/status-histories';
import type { Evidence, Link as RiskLink, StatusHistory } from '@/types/models';

type Props = {
    statusHistory: StatusHistory & { link: RiskLink };
    /** For a verification: the evidence it rested on. */
    supportingEvidence: Evidence[];
};

export default function StatusHistoriesShow({
    statusHistory,
    supportingEvidence,
}: Props) {
    const { t } = useTranslations();
    const event = statusHistory.adverse_event;

    // Each entry changes one dimension (0013).
    const title =
        statusHistory.previous_verification === 'verified' &&
        statusHistory.new_verification === 'verified'
            ? t('Verification renewal')
            : statusHistory.new_verification !== null
              ? t('Verification change')
              : t('Status change');

    return (
        <>
            <Head title={title} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <header className="grid gap-2">
                    <h1 className="text-[28px] leading-9 font-normal">
                        {title}
                    </h1>
                    <StatusTransition entry={statusHistory} />
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Details')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label={t('Link')}>
                                <Link
                                    href={showLink(statusHistory.link_id)}
                                    className="hover:underline"
                                >
                                    {linkLabel(statusHistory.link, t)}
                                </Link>
                            </DetailItem>
                            <DetailItem label={t('Change date')}>
                                {formatDate(statusHistory.change_date)}
                            </DetailItem>
                            <DetailItem label={t('Recorded by')}>
                                <EntryAuthor entry={statusHistory} />
                            </DetailItem>
                        </dl>
                        <dl>
                            {statusHistory.new_verification === 'verified' ? (
                                // A verification has no reason: it rests on
                                // the evidence that counted then (0013).
                                <DetailItem
                                    label={t(
                                        'Evidence the verification rested on',
                                    )}
                                >
                                    <SupportingEvidence
                                        evidence={supportingEvidence}
                                    />
                                </DetailItem>
                            ) : (
                                <DetailItem label={t('Reason')}>
                                    {statusHistory.trigger_reason ? (
                                        <p className="max-w-prose font-normal whitespace-pre-line">
                                            {statusHistory.trigger_reason}
                                        </p>
                                    ) : (
                                        <span className="text-muted-foreground font-normal">
                                            {t('Not reported')}
                                        </span>
                                    )}
                                </DetailItem>
                            )}
                        </dl>
                    </CardContent>
                </Card>

                {event && (
                    <Card>
                        <CardHeader>
                            <CardTitle>{t('Related adverse event')}</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-6">
                            <dl className="grid gap-4 sm:grid-cols-3">
                                <DetailItem label={t('Risk subdomains')}>
                                    <ul className="grid gap-2">
                                        {event.risk_subdomains?.map(
                                            (subdomain) => (
                                                <li key={subdomain.code}>
                                                    <RiskSubdomain
                                                        subdomain={subdomain}
                                                    />
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </DetailItem>
                                <DetailItem label={t('Occurrence')}>
                                    {formatDate(event.occurrence_date)}
                                </DetailItem>
                                <DetailItem label={t('AI system')}>
                                    {event.ai_system?.name}
                                </DetailItem>
                            </dl>
                            <dl>
                                <DetailItem label={t('Description')}>
                                    <p className="max-w-prose font-normal whitespace-pre-line">
                                        {event.description}
                                    </p>
                                </DetailItem>
                            </dl>
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

function SupportingEvidence({ evidence }: { evidence: Evidence[] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    if (evidence.length === 0) {
        return (
            <span className="text-muted-foreground font-normal">
                {t('No evidence found')}
            </span>
        );
    }

    return (
        <ul className="grid gap-2 font-normal">
            {evidence.map((item) => (
                <li key={item.id} className="grid">
                    <Link
                        href={showEvidence(item.id)}
                        className="font-medium hover:underline"
                    >
                        {t(':type of :date', {
                            type: enumLabels.EvidenceType[item.type],
                            date: formatDate(item.registration_date),
                        })}
                    </Link>
                    <span className="text-muted-foreground text-sm">
                        {item.description}
                    </span>
                </li>
            ))}
        </ul>
    );
}

StatusHistoriesShow.layout = ({
    statusHistory,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: linksIndex() },
            {
                title: linkLabel(statusHistory.link, t),
                href: showLink(statusHistory.link_id),
            },
            { title: t('History'), href: historyIndex(statusHistory.link_id) },
            {
                title: formatDate(statusHistory.change_date),
                href: show(statusHistory.id),
            },
        ],
    };
};
