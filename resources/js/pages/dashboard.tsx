import { Head, Link } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import type { InertiaLinkProps } from '@inertiajs/react';
import Heading from '@/components/heading';
import { AiSystemName } from '@/components/ai-system-name';
import { ReviewDate } from '@/components/review-date';
import { RiskSubdomainBadges } from '@/components/risk-subdomain-badges';
import { unacceptableTone } from '@/components/unacceptable-tier-alert';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { formatDate } from '@/lib/format';
import {
    categoryBadgeClasses,
    linkLabel,
    linkStatusBadgeClasses,
    linkStatusLabels,
} from '@/lib/labels';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import {
    index as adverseEventsIndex,
    show as showAdverseEvent,
} from '@/routes/adverse-events';
import {
    create as createAiSystem,
    show as showAiSystem,
} from '@/routes/ai-systems';
import {
    create as createLink,
    index as linksIndex,
    show as showLink,
} from '@/routes/links';
import { create as createEvidence } from '@/routes/links/evidence';
import { index as risksIndex, show as showRisk } from '@/routes/risks';
import type {
    AdverseEvent,
    AiSystem,
    Link as RiskLink,
    LinkStatus,
    Risk,
} from '@/types/models';

type Pending<T> = { count: number; items: T[] };

type SystemSummary = Pick<
    AiSystem,
    'id' | 'name' | 'application_domain' | 'category'
> & {
    risks_count: number;
    unlinked_risks_count: number;
    links_count: number;
    due_reviews_count: number;
    recent_events_count: number;
};

type Props = {
    totals: {
        aiSystems: number;
        risks: number;
        links: number;
        mitigations: number;
        owners: number;
    };
    unlinkedRisks: Pending<Risk>;
    linksByStatus: { status: LinkStatus; count: number }[];
    linksWithoutEvidence: Pending<RiskLink>;
    recentEvents: Pending<AdverseEvent> & { days: number };
    reviews: {
        upcomingDays: number;
        dueCount: number;
        due: RiskLink[];
        upcomingCount: number;
        upcoming: RiskLink[];
    };
    systems: SystemSummary[];
    /** Systems that may not operate, left out of the review indicators. */
    unacceptableSystems: Pick<AiSystem, 'id' | 'name'>[];
};

/** Tones for the review labels of the reassessment screen (Tela 5). */
const reviewBadge = {
    due: 'border-transparent bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
    onTrack:
        'border-transparent bg-emerald-100 text-emerald-900 dark:bg-emerald-900/40 dark:text-emerald-200',
};

export default function Dashboard(props: Props) {
    const { totals } = props;

    return (
        <>
            <Head title="Painel" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title="Painel"
                    description="Onde a cadeia de rastreabilidade ainda está incompleta."
                />
                {totals.aiSystems === 0 ? <Welcome /> : <Overview {...props} />}
            </div>
        </>
    );
}

/** With nothing registered, zeroed cards say nothing: point to step one. */
function Welcome() {
    return (
        <div className="flex max-w-2xl flex-col items-start gap-4 rounded-xl border border-dashed p-8">
            <h2 className="text-lg font-semibold">Comece pelo sistema de IA</h2>
            <p className="text-muted-foreground text-sm">
                O ciclo do framework parte de um sistema de IA cadastrado: a ele
                se ligam os riscos, as mitigações aplicadas, as evidências e os
                eventos adversos. Cadastre o primeiro sistema para o painel
                começar a mostrar o que falta em cada etapa.
            </p>
            <Button asChild>
                <Link href={createAiSystem()}>Cadastrar sistema de IA</Link>
            </Button>
        </div>
    );
}

function Overview({
    totals,
    unlinkedRisks,
    linksByStatus,
    linksWithoutEvidence,
    recentEvents,
    reviews,
    systems,
    unacceptableSystems,
}: Props) {
    return (
        <>
            {unacceptableSystems.length > 0 && (
                <UnacceptableSystemsAlert systems={unacceptableSystems} />
            )}

            <dl className="text-muted-foreground flex flex-wrap gap-x-6 gap-y-1 text-sm">
                <Total label="Sistemas de IA" value={totals.aiSystems} />
                <Total label="Riscos" value={totals.risks} />
                <Total label="Vínculos" value={totals.links} />
                <Total
                    label="Mitigações no catálogo"
                    value={totals.mitigations}
                />
                <Total label="Responsáveis ativos" value={totals.owners} />
            </dl>

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <PendingCard
                    count={unlinkedRisks.count}
                    title="Riscos sem vínculo"
                    sentence="Riscos identificados sem nenhuma mitigação aplicada."
                    href={risksIndex()}
                    action="Ver riscos"
                />
                <PendingCard
                    count={linksWithoutEvidence.count}
                    title="Vínculos sem evidência"
                    sentence="Mitigações aplicadas sem nada que comprove a execução."
                    href={linksIndex()}
                    action="Ver vínculos"
                />
                <PendingCard
                    count={reviews.dueCount}
                    title="Revisões vencidas"
                    sentence="Vínculos de sistemas em operação cuja data de revisão já chegou."
                    href={linksIndex()}
                    action="Ver vínculos"
                />
                <PendingCard
                    count={recentEvents.count}
                    title={`Eventos nos últimos ${recentEvents.days} dias`}
                    sentence="Problemas recentes nos sistemas em operação."
                    href={adverseEventsIndex()}
                    action="Ver eventos"
                />
            </div>

            <div className="grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader>
                        <CardTitle>Riscos sem vínculo</CardTitle>
                        <CardDescription>
                            Os mais antigos primeiro.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {unlinkedRisks.items.length === 0 ? (
                            <Empty>
                                Todos os riscos têm ao menos um vínculo.
                            </Empty>
                        ) : (
                            <ul className="divide-y">
                                {unlinkedRisks.items.map((risk) => (
                                    <li
                                        key={risk.id}
                                        className="flex items-center justify-between gap-4 py-2"
                                    >
                                        <span className="grid gap-1">
                                            <Link
                                                href={showRisk(risk.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {risk.name}
                                            </Link>
                                            <span className="text-muted-foreground text-xs">
                                                {risk.ai_system?.name}
                                            </span>
                                        </span>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={createLink({
                                                    query: { risk: risk.id },
                                                })}
                                            >
                                                Criar vínculo
                                            </Link>
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Revisões</CardTitle>
                        <CardDescription>
                            Vencidas e as dos próximos {reviews.upcomingDays}{' '}
                            dias, das mais urgentes às menos.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {reviews.due.length === 0 &&
                        reviews.upcoming.length === 0 ? (
                            <Empty>
                                Nenhuma revisão vencida nem prevista para os
                                próximos {reviews.upcomingDays} dias.
                            </Empty>
                        ) : (
                            <ul className="divide-y">
                                {reviews.due.map((link) => (
                                    <ReviewItem key={link.id} link={link} due />
                                ))}
                                {reviews.upcoming.map((link) => (
                                    <ReviewItem key={link.id} link={link} />
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Vínculos sem evidência</CardTitle>
                        <CardDescription>
                            Os mais antigos primeiro.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {linksWithoutEvidence.items.length === 0 ? (
                            <Empty>
                                Todos os vínculos têm ao menos uma evidência.
                            </Empty>
                        ) : (
                            <ul className="divide-y">
                                {linksWithoutEvidence.items.map((link) => (
                                    <li
                                        key={link.id}
                                        className="flex items-center justify-between gap-4 py-2"
                                    >
                                        <span className="grid">
                                            <Link
                                                href={showLink(link.id)}
                                                className="font-medium hover:underline"
                                            >
                                                {linkLabel(link)}
                                            </Link>
                                            <span className="text-muted-foreground text-xs">
                                                Criado em{' '}
                                                {formatDate(link.creation_date)}
                                            </span>
                                        </span>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={createEvidence(link.id)}
                                            >
                                                Registrar evidência
                                            </Link>
                                        </Button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Eventos recentes</CardTitle>
                        <CardDescription>
                            Últimos {recentEvents.days} dias, do mais recente ao
                            mais antigo.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {recentEvents.items.length === 0 ? (
                            <Empty>
                                Nenhum evento adverso nos últimos{' '}
                                {recentEvents.days} dias.
                            </Empty>
                        ) : (
                            <ul className="divide-y">
                                {recentEvents.items.map((event) => (
                                    <li
                                        key={event.id}
                                        className="flex items-center justify-between gap-4 py-2"
                                    >
                                        <span className="grid">
                                            <Link
                                                href={showAdverseEvent(
                                                    event.id,
                                                )}
                                                className="font-medium hover:underline"
                                            >
                                                {event.ai_system?.name}
                                            </Link>
                                            <RiskSubdomainBadges
                                                subdomains={
                                                    event.risk_subdomains
                                                }
                                            />
                                        </span>
                                        <span className="text-muted-foreground text-sm whitespace-nowrap">
                                            {formatDate(event.occurrence_date)}
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </CardContent>
                </Card>
            </div>

            <Card>
                <CardHeader>
                    <CardTitle>Vínculos por status</CardTitle>
                    <CardDescription>
                        Progresso das mitigações aplicadas; cancelados ficam de
                        fora.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <StatusBars rows={linksByStatus} />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Por sistema de IA</CardTitle>
                </CardHeader>
                <CardContent>
                    <SystemsTable systems={systems} />
                </CardContent>
            </Card>
        </>
    );
}

function Total({ label, value }: { label: string; value: number }) {
    return (
        <div className="flex items-baseline gap-1.5">
            <dt>{label}</dt>
            <dd className="text-foreground font-semibold tabular-nums">
                {value}
            </dd>
        </div>
    );
}

/** A pending count; it stands out when there is something to resolve. */
function PendingCard({
    count,
    title,
    sentence,
    href,
    action,
}: {
    count: number;
    title: string;
    sentence: string;
    href: NonNullable<InertiaLinkProps['href']>;
    action: string;
}) {
    const pending = count > 0;

    return (
        <Card
            className={cn(
                pending &&
                    'border-amber-300 bg-amber-50/60 dark:border-amber-900 dark:bg-amber-950/30',
            )}
        >
            <CardHeader>
                <CardDescription>{title}</CardDescription>
                <CardTitle
                    className={cn(
                        'text-3xl tabular-nums',
                        pending
                            ? 'text-amber-700 dark:text-amber-300'
                            : 'text-muted-foreground',
                    )}
                >
                    {count}
                </CardTitle>
            </CardHeader>
            <CardContent className="grid gap-3">
                <p className="text-muted-foreground text-sm">{sentence}</p>
                <Link
                    href={href}
                    className="text-sm font-medium hover:underline"
                >
                    {action} →
                </Link>
            </CardContent>
        </Card>
    );
}

/**
 * Systems in the unacceptable tier: prohibited practices that may not
 * operate. They stay out of the review indicators, so the dashboard says so.
 */
function UnacceptableSystemsAlert({
    systems,
}: {
    systems: Props['unacceptableSystems'];
}) {
    return (
        <Alert className={unacceptableTone}>
            <TriangleAlertIcon aria-hidden />
            <AlertTitle>
                {systems.length === 1
                    ? '1 sistema na faixa inaceitável do EU AI Act'
                    : `${systems.length} sistemas na faixa inaceitável do EU AI Act`}
            </AlertTitle>
            <AlertDescription className="text-red-900 dark:text-red-200">
                <p>
                    Práticas proibidas: esses sistemas não são considerados em
                    operação e ficam fora dos indicadores de revisão. Seus
                    vínculos servem para planejar a descontinuação.
                </p>
                <ul className="flex flex-wrap gap-x-4 gap-y-1">
                    {systems.map((system) => (
                        <li key={system.id}>
                            <Link
                                href={showAiSystem(system.id)}
                                className="font-medium underline underline-offset-4"
                            >
                                {system.name}
                            </Link>
                        </li>
                    ))}
                </ul>
            </AlertDescription>
        </Alert>
    );
}

function ReviewItem({ link, due = false }: { link: RiskLink; due?: boolean }) {
    return (
        <li className="flex items-center justify-between gap-4 py-2">
            <span className="grid">
                <Link
                    href={showLink(link.id)}
                    className="font-medium hover:underline"
                >
                    {linkLabel(link)}
                </Link>
                <span className="text-muted-foreground text-xs">
                    Revisão em <ReviewDate date={link.next_review_date} />
                </span>
            </span>
            <Badge className={due ? reviewBadge.due : reviewBadge.onTrack}>
                {due ? 'SINALIZADO PARA REVISÃO' : 'EM DIA'}
            </Badge>
        </li>
    );
}

/** Plain Tailwind bars: enough to compare a handful of statuses. */
function StatusBars({ rows }: { rows: Props['linksByStatus'] }) {
    const max = Math.max(1, ...rows.map((row) => row.count));

    return (
        <ul className="grid gap-3">
            {rows.map((row) => (
                <li
                    key={row.status}
                    className="grid grid-cols-[9rem_1fr_2.5rem] items-center gap-3"
                >
                    <Badge
                        className={cn(
                            'justify-self-start',
                            linkStatusBadgeClasses[row.status],
                        )}
                    >
                        {linkStatusLabels[row.status]}
                    </Badge>
                    <div className="bg-muted h-2 overflow-hidden rounded-full">
                        <div
                            className="bg-primary/70 h-full rounded-full"
                            style={{ width: `${(row.count / max) * 100}%` }}
                        />
                    </div>
                    <span className="text-right text-sm tabular-nums">
                        {row.count}
                    </span>
                </li>
            ))}
        </ul>
    );
}

function SystemsTable({ systems }: { systems: SystemSummary[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Sistema</TableHead>
                    <TableHead className="text-right">Riscos</TableHead>
                    <TableHead className="text-right">Sem vínculo</TableHead>
                    <TableHead className="text-right">Vínculos</TableHead>
                    <TableHead className="text-right">
                        Revisões vencidas
                    </TableHead>
                    <TableHead className="text-right">
                        Eventos (30 dias)
                    </TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {systems.map((system) => (
                    <TableRow key={system.id}>
                        <TableCell>
                            <AiSystemName domain={system.application_domain}>
                                <span className="flex flex-wrap items-center gap-2">
                                    <Link
                                        href={showAiSystem(system.id)}
                                        className="font-medium hover:underline"
                                    >
                                        {system.name}
                                    </Link>
                                    {system.category === 'unacceptable' && (
                                        <Badge
                                            className={
                                                categoryBadgeClasses.unacceptable
                                            }
                                        >
                                            Inaceitável
                                        </Badge>
                                    )}
                                </span>
                            </AiSystemName>
                        </TableCell>
                        <NumberCell value={system.risks_count} />
                        <NumberCell
                            value={system.unlinked_risks_count}
                            pending
                        />
                        <NumberCell value={system.links_count} />
                        {system.category === 'unacceptable' ? (
                            // Never in operation, so it owes no review.
                            <TableCell className="text-muted-foreground text-right text-xs">
                                Não se aplica
                            </TableCell>
                        ) : (
                            <NumberCell
                                value={system.due_reviews_count}
                                pending
                            />
                        )}
                        <NumberCell
                            value={system.recent_events_count}
                            pending
                        />
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

function NumberCell({
    value,
    pending = false,
}: {
    value: number;
    /** A pending count is highlighted when it is not zero. */
    pending?: boolean;
}) {
    return (
        <TableCell
            className={cn(
                'text-right tabular-nums',
                pending &&
                    value > 0 &&
                    'font-semibold text-amber-700 dark:text-amber-300',
            )}
        >
            {value}
        </TableCell>
    );
}

function Empty({ children }: { children: React.ReactNode }) {
    return <p className="text-muted-foreground text-sm">{children}</p>;
}

Dashboard.layout = {
    breadcrumbs: [{ title: 'Painel', href: dashboard() }],
};
