import { Head, Link } from '@inertiajs/react';
import { DetailItem } from '@/components/detail-item';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDate } from '@/lib/format';
import { costLevelLabels, evidenceTypeLabels, linkLabel } from '@/lib/labels';
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
    return (
        <>
            <Head title={evidenceTypeLabels[evidence.type]} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="grid gap-1">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {evidenceTypeLabels[evidence.type]}
                        </h1>
                        <Link
                            href={showLink(evidence.link_id)}
                            className="text-muted-foreground text-sm hover:underline"
                        >
                            {linkLabel(evidence.link)}
                        </Link>
                    </div>
                    {/* Evidence is append only: a mistake is corrected by
                        registering more, never by changing what was recorded. */}
                    <Button variant="outline" asChild>
                        <Link href={createEvidence(evidence.link_id)}>
                            Registrar nova evidência
                        </Link>
                    </Button>
                </header>

                <p className="text-muted-foreground -mt-2 text-sm">
                    Evidências não podem ser editadas nem excluídas. Para
                    corrigir esta, registre uma nova.
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label="Tipo">
                                {evidenceTypeLabels[evidence.type]}
                            </DetailItem>
                            <DetailItem label="Registrada em">
                                {formatDate(evidence.registration_date)}
                            </DetailItem>
                            <DetailItem label="Custo observado">
                                {evidence.observed_cost ? (
                                    costLevelLabels[evidence.observed_cost]
                                ) : (
                                    <span className="text-muted-foreground font-normal">
                                        Não informado
                                    </span>
                                )}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label="Descrição">
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

EvidenceShow.layout = ({ evidence }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(evidence.link), href: showLink(evidence.link_id) },
        { title: 'Evidências', href: evidenceIndex(evidence.link_id) },
        { title: evidenceTypeLabels[evidence.type], href: show(evidence.id) },
    ],
});
