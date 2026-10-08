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
import { formatDate } from '@/lib/format';
import {
    linkLabel,
    reassessmentOutcomeLabels,
    systemChangeTypeLabels,
} from '@/lib/labels';
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
    const subdomains = systemChange.risk_subdomains ?? [];
    const reversals = systemChange.reversals ?? [];
    const reassessed = reversals.filter(
        (reversal) => reversal.reassessment,
    ).length;
    const title = systemChangeTypeLabels[systemChange.type];

    return (
        <>
            <Head title={title} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="grid gap-1">
                    <h1 className="text-xl font-semibold tracking-tight">
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
                    Mudanças do sistema não podem ser editadas nem excluídas:
                    elas registram o que mudou e explicam as reversões que
                    causaram.
                </p>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <DetailItem label="Tipo">{title}</DetailItem>
                            <DetailItem label="Data da mudança">
                                {formatDate(systemChange.change_date)}
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label="Descrição">
                                <p className="font-normal whitespace-pre-line">
                                    {systemChange.description}
                                </p>
                            </DetailItem>
                        </dl>
                        <dl>
                            <DetailItem label="Subdomínios de risco afetados">
                                {subdomains.length === 0 ? (
                                    <p className="text-muted-foreground font-normal">
                                        Nenhum informado: a mudança reverteu
                                        todos os vínculos verificados do
                                        sistema.
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
                                                        Sem risco cadastrado
                                                        neste sistema.
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
                                                            Cadastrar risco
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
                        <CardTitle>Vínculos revertidos pela mudança</CardTitle>
                        <CardDescription>
                            Ao registrar a mudança, os vínculos verificados que
                            ela alcança voltaram a declarados. Cada um é
                            reavaliado separadamente.
                        </CardDescription>
                        {reversals.length > 0 && (
                            <p className="text-sm font-medium">
                                {reassessed} de {reversals.length}{' '}
                                {reversals.length === 1
                                    ? 'reavaliado'
                                    : 'reavaliados'}
                            </p>
                        )}
                    </CardHeader>
                    <CardContent>
                        {reversals.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Nenhum vínculo verificado foi revertido por esta
                                mudança.
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
                                                {linkLabel(reversal.link)}
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
                                                        reassessmentOutcomeLabels[
                                                            reversal
                                                                .reassessment
                                                                .outcome
                                                        ]
                                                    }
                                                </Badge>
                                                em{' '}
                                                {formatDate(
                                                    reversal.reassessment
                                                        .reassessment_date,
                                                )}
                                            </Link>
                                        ) : (
                                            <span className="text-sm text-amber-700 dark:text-amber-300">
                                                Aguardando reavaliação
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

SystemChangesShow.layout = ({ systemChange }: Props) => ({
    breadcrumbs: [
        { title: 'Sistemas de IA', href: aiSystemsIndex() },
        ...(systemChange.ai_system
            ? [
                  {
                      title: systemChange.ai_system.name,
                      href: showAiSystem(systemChange.ai_system.id),
                  },
                  {
                      title: 'Mudanças',
                      href: changesIndex(systemChange.ai_system.id),
                  },
              ]
            : []),
        {
            title: systemChangeTypeLabels[systemChange.type],
            href: show(systemChange.id),
        },
    ],
});
