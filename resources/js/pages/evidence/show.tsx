import { Head, Link } from '@inertiajs/react';
import { DetailItem } from '@/components/detail-item';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithEnumLabels } from '@/lib/enum-labels';
import type { WithTranslations } from '@/lib/i18n';
import { linkLabel } from '@/lib/names';
import { show } from '@/routes/evidence';
import { index as linksIndex, show as showLink } from '@/routes/links';
import {
    create as createEvidence,
    index as evidenceIndex,
} from '@/routes/links/evidence';
import type { Evidence, Link as RiskLink } from '@/types/models';

type Props = {
    evidence: Evidence & { link: RiskLink };
};

export default function EvidenceShow({ evidence }: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <>
            <Head title={enumLabels.EvidenceType[evidence.type]} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="grid gap-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {enumLabels.EvidenceType[evidence.type]}
                        </h1>
                        <Link
                            href={showLink(evidence.link_id)}
                            className="text-muted-foreground text-sm hover:underline"
                        >
                            {linkLabel(evidence.link, t)}
                        </Link>
                    </div>
                    {/* Evidence is append only: a mistake is corrected by
                        registering more, never by changing what was recorded. */}
                    <Button variant="outline" asChild>
                        <Link href={createEvidence(evidence.link_id)}>
                            {t('Register new evidence')}
                        </Link>
                    </Button>
                </header>

                <p className="text-muted-foreground -mt-2 text-sm">
                    {t(
                        'Evidence can be neither edited nor deleted. To correct this one, register a new one.',
                    )}
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Details')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label={t('Type')}>
                                {enumLabels.EvidenceType[evidence.type]}
                            </DetailItem>
                            <DetailItem label={t('Registered on')}>
                                {formatDate(evidence.registration_date)}
                            </DetailItem>
                            <DetailItem label={t('Observed cost')}>
                                {evidence.observed_cost ? (
                                    enumLabels.CostLevel[evidence.observed_cost]
                                ) : (
                                    <span className="text-muted-foreground font-normal">
                                        {t('Not reported')}
                                    </span>
                                )}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label={t('Description')}>
                                <p className="max-w-prose font-normal whitespace-pre-line">
                                    {evidence.description}
                                </p>
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

EvidenceShow.layout = ({
    evidence,
    translations,
    enumLabels,
}: Props & WithTranslations & WithEnumLabels) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: linksIndex() },
            {
                title: linkLabel(evidence.link, t),
                href: showLink(evidence.link_id),
            },
            { title: t('Evidence'), href: evidenceIndex(evidence.link_id) },
            {
                title: enumLabels.EvidenceType[evidence.type],
                href: show(evidence.id),
            },
        ],
    };
};
