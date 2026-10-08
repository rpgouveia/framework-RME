import { Form, Head, Link, usePage } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import AdverseEventController from '@/actions/App/Http/Controllers/AdverseEventController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { dateInputValue } from '@/lib/format';
import { eventReassessment } from '@/lib/event-reassessment';
import type { SystemLink } from '@/lib/event-reassessment';
import { adverseEventNatureLabels, labelFor, termLabel } from '@/lib/labels';
import { splitByRiskProfile } from '@/lib/risk-profile';
import type { ExpectedSubdomain } from '@/lib/risk-profile';
import { create, index } from '@/routes/adverse-events';
import type { AiSystem, EnumOption, RiskDomainOption } from '@/types/models';

type Props = {
    aiSystems: Pick<AiSystem, 'id' | 'name' | 'application_domain'>[];
    /** Every MIT subdomain, grouped by domain: all are always on offer. */
    riskDomains: RiskDomainOption[];
    /** RF04: each system's risk profile, keyed by system id. */
    expectedRiskSubdomainsBySystem: Record<string, ExpectedSubdomain[]>;
    natures: EnumOption[];
    /** Each system's links still in the chain, keyed by system id (0019). */
    linksBySystem: Record<string, SystemLink[]>;
};

/** Radix Select items cannot be empty, so "none" needs a stand-in. */
const NO_INTERCEPTOR = 'none';

/** Lets the confirm button, rendered in a dialog outside the form, submit it. */
const FORM_ID = 'adverse-event-form';

export default function AdverseEventsCreate({
    aiSystems,
    riskDomains,
    expectedRiskSubdomainsBySystem,
    natures,
    linksBySystem,
}: Props) {
    const { url } = usePage();

    // The AI system page links here with `?ai_system=<id>`.
    const requested = new URLSearchParams(url.split('?')[1]).get('ai_system');
    const [aiSystemId, setAiSystemId] = useState(
        aiSystems.some((system) => String(system.id) === requested)
            ? (requested ?? '')
            : '',
    );
    const [selected, setSelected] = useState<string[]>([]);
    const [missingSubdomain, setMissingSubdomain] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [nature, setNature] = useState('');
    const [occurrenceDate, setOccurrenceDate] = useState('');
    const [interceptor, setInterceptor] = useState(NO_INTERCEPTOR);

    // What the event will do to the system's links (0019): the server works
    // it out again when it records the event.
    const links = aiSystemId ? (linksBySystem[aiSystemId] ?? []) : [];
    const nearMiss = nature === 'near_miss';
    const offered = eventReassessment(links, selected, null).interceptors;
    // An interceptor no longer among the eligible ones is dropped.
    const interceptorId =
        nearMiss && offered.some((link) => String(link.id) === interceptor)
            ? Number(interceptor)
            : null;
    const { reverted } = eventReassessment(links, selected, interceptorId);

    // Every subdomain is offered to every system, so a choice survives a
    // change of system; only the order follows the new risk profile.
    const expected = aiSystemId
        ? (expectedRiskSubdomainsBySystem[aiSystemId] ?? [])
        : [];

    function toggleSubdomain(code: string, checked: boolean) {
        setMissingSubdomain(false);
        setSelected((codes) =>
            checked
                ? [...codes, code]
                : codes.filter((selectedCode) => selectedCode !== code),
        );
    }

    // Check the required fields first, so the dialog only asks about a form
    // that can actually be sent. A checkbox group has no native "at least
    // one", so the subdomains are checked here.
    function askToConfirm() {
        const form = document.getElementById(FORM_ID);

        if (!(form instanceof HTMLFormElement) || !form.reportValidity()) {
            return;
        }

        if (selected.length === 0) {
            setMissingSubdomain(true);

            return;
        }

        setConfirming(true);
    }

    return (
        <>
            <Head title="Registrar evento adverso" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Registrar evento adverso"
                    description="Registre algo que deu errado em um sistema de IA em operação."
                />
                {aiSystems.length === 0 ? (
                    <p className="text-muted-foreground max-w-xl text-sm">
                        Nenhum sistema de IA foi cadastrado ainda. Todo evento
                        adverso acontece em um sistema.
                    </p>
                ) : (
                    <Form
                        {...AdverseEventController.store.form()}
                        id={FORM_ID}
                        onError={() => setConfirming(false)}
                        className="max-w-xl space-y-6"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="ai_system_id">
                                        Sistema afetado
                                    </Label>
                                    <Select
                                        name="ai_system_id"
                                        value={aiSystemId}
                                        onValueChange={setAiSystemId}
                                        required
                                    >
                                        <SelectTrigger
                                            id="ai_system_id"
                                            className="w-full"
                                            aria-invalid={
                                                errors.ai_system_id
                                                    ? true
                                                    : undefined
                                            }
                                        >
                                            <SelectValue placeholder="Selecione o sistema" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {aiSystems.map((system) => (
                                                <SelectItem
                                                    key={system.id}
                                                    value={String(system.id)}
                                                >
                                                    {system.name}
                                                    {system.application_domain && (
                                                        <span className="text-muted-foreground">
                                                            {' '}
                                                            ·{' '}
                                                            {
                                                                system.application_domain
                                                            }
                                                        </span>
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.ai_system_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="nature">Natureza</Label>
                                    <Select
                                        name="nature"
                                        value={nature}
                                        onValueChange={setNature}
                                        required
                                    >
                                        <SelectTrigger
                                            id="nature"
                                            className="w-full sm:w-64"
                                            aria-invalid={
                                                errors.nature ? true : undefined
                                            }
                                        >
                                            <SelectValue placeholder="Selecione a natureza" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {natures.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {labelFor(
                                                        adverseEventNatureLabels,
                                                        option.value,
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-muted-foreground text-sm">
                                        Um quase-incidente foi percebido antes
                                        de causar dano. Os dois disparam a
                                        reavaliação dos vínculos.
                                    </p>
                                    <InputError message={errors.nature} />
                                </div>

                                <RiskSubdomainPicker
                                    domains={riskDomains}
                                    expected={expected}
                                    selected={selected}
                                    onToggle={toggleSubdomain}
                                    disabled={!aiSystemId}
                                    error={
                                        missingSubdomain
                                            ? 'Selecione ao menos um subdomínio de risco que a ocorrência materializa.'
                                            : subdomainError(errors)
                                    }
                                />

                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        Descrição
                                    </Label>
                                    <Textarea
                                        id="description"
                                        name="description"
                                        required
                                        maxLength={2000}
                                        className="max-h-80 min-h-32"
                                        placeholder="Ex.: O modelo recusou todas as solicitações de clientes acima de 60 anos durante dois dias."
                                        aria-invalid={
                                            errors.description
                                                ? true
                                                : undefined
                                        }
                                    />
                                    <InputError message={errors.description} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="occurrence_date">
                                        Data de ocorrência
                                    </Label>
                                    <Input
                                        id="occurrence_date"
                                        name="occurrence_date"
                                        type="date"
                                        className="sm:w-48"
                                        max={dateInputValue()}
                                        required
                                        value={occurrenceDate}
                                        onChange={(event) =>
                                            setOccurrenceDate(
                                                event.target.value,
                                            )
                                        }
                                        aria-invalid={
                                            errors.occurrence_date
                                                ? true
                                                : undefined
                                        }
                                    />
                                    <p className="text-muted-foreground text-sm">
                                        Não pode ser uma data futura.
                                    </p>
                                    <InputError
                                        message={errors.occurrence_date}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="detected_at">
                                        Data de detecção{' '}
                                        <span className="text-muted-foreground font-normal">
                                            (opcional)
                                        </span>
                                    </Label>
                                    <Input
                                        id="detected_at"
                                        name="detected_at"
                                        type="date"
                                        className="sm:w-48"
                                        min={occurrenceDate || undefined}
                                        max={dateInputValue()}
                                        aria-describedby="detected_at-help"
                                        aria-invalid={
                                            errors.detected_at
                                                ? true
                                                : undefined
                                        }
                                    />
                                    <p
                                        id="detected_at-help"
                                        className="text-muted-foreground text-sm"
                                    >
                                        Quando o monitoramento percebeu a
                                        ocorrência, entre a data de ocorrência e
                                        hoje. O intervalo mede a demora em
                                        detectar.
                                    </p>
                                    <InputError message={errors.detected_at} />
                                </div>

                                {nearMiss && aiSystemId && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="intercepting_link_id">
                                            Vínculo que interceptou{' '}
                                            <span className="text-muted-foreground font-normal">
                                                (opcional)
                                            </span>
                                        </Label>
                                        <Select
                                            value={
                                                interceptorId === null
                                                    ? NO_INTERCEPTOR
                                                    : String(interceptorId)
                                            }
                                            onValueChange={setInterceptor}
                                            disabled={offered.length === 0}
                                        >
                                            <SelectTrigger
                                                id="intercepting_link_id"
                                                className="w-full"
                                                aria-describedby="intercepting_link_id-help"
                                                aria-invalid={
                                                    errors.intercepting_link_id
                                                        ? true
                                                        : undefined
                                                }
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem
                                                    value={NO_INTERCEPTOR}
                                                >
                                                    Nenhum
                                                </SelectItem>
                                                {offered.map((link) => (
                                                    <SelectItem
                                                        key={link.id}
                                                        value={String(link.id)}
                                                    >
                                                        {link.risk} →{' '}
                                                        {link.mitigation} (
                                                        {link.subdomain})
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {/* Sent empty when none, which
                                            Laravel turns into null. */}
                                        <input
                                            type="hidden"
                                            name="intercepting_link_id"
                                            value={interceptorId ?? ''}
                                        />
                                        <p
                                            id="intercepting_link_id-help"
                                            className="text-muted-foreground text-sm"
                                        >
                                            {offered.length === 0
                                                ? 'Nenhum vínculo deste sistema trata os subdomínios selecionados.'
                                                : 'O vínculo cuja mitigação barrou a ocorrência. Ele não é revertido, porque o evento mostra que funcionou.'}
                                        </p>
                                        <InputError
                                            message={
                                                errors.intercepting_link_id
                                            }
                                        />
                                    </div>
                                )}

                                <Alert>
                                    <TriangleAlertIcon />
                                    <AlertTitle>
                                        O registro é permanente
                                    </AlertTitle>
                                    <AlertDescription>
                                        Eventos adversos não podem ser editados
                                        nem excluídos. O registro devolve a
                                        declarados os vínculos verificados do
                                        sistema cujo risco está em algum dos
                                        subdomínios do evento.
                                    </AlertDescription>
                                </Alert>

                                <div className="flex items-center gap-4">
                                    <Button
                                        type="button"
                                        disabled={processing}
                                        onClick={askToConfirm}
                                    >
                                        Registrar evento
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href={index()}>Cancelar</Link>
                                    </Button>
                                </div>

                                <Dialog
                                    open={confirming}
                                    onOpenChange={setConfirming}
                                >
                                    <DialogContent>
                                        <DialogTitle>
                                            Registrar este evento adverso?
                                        </DialogTitle>
                                        <DialogDescription>
                                            Confira o sistema, os subdomínios de
                                            risco ({selected.join(', ')}) e a
                                            data: depois de registrado, o evento
                                            não pode ser editado nem excluído.
                                        </DialogDescription>
                                        <ReversalPreview reverted={reverted} />
                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">
                                                    Voltar
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                form={FORM_ID}
                                                disabled={processing}
                                            >
                                                Registrar
                                            </Button>
                                        </DialogFooter>
                                    </DialogContent>
                                </Dialog>
                            </>
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

/**
 * How many verified links the event will revert, and which, so the
 * confirmation says it before the event is recorded (0019, item 3).
 */
function ReversalPreview({ reverted }: { reverted: SystemLink[] }) {
    if (reverted.length === 0) {
        return (
            <p className="text-sm">
                Nenhum vínculo verificado deste sistema será revertido.
            </p>
        );
    }

    return (
        <div className="grid gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
            <p className="font-semibold">
                {reverted.length === 1
                    ? '1 vínculo verificado voltará a declarado'
                    : `${reverted.length} vínculos verificados voltarão a declarados`}
                , aguardando reavaliação:
            </p>
            <ul className="list-disc pl-5">
                {reverted.map((link) => (
                    <li key={link.id}>
                        {link.risk} → {link.mitigation} ({link.subdomain})
                    </li>
                ))}
            </ul>
        </div>
    );
}

/** The first error on the subdomains, on the list or on one of its items. */
function subdomainError(
    errors: Partial<Record<string, string>>,
): string | undefined {
    return (
        errors.risk_subdomains ??
        Object.entries(errors).find(([key]) =>
            key.startsWith('risk_subdomains.'),
        )?.[1]
    );
}

/**
 * Checkboxes for the MIT risk subdomains, each with the definition from the
 * source to help the classification. The system's risk profile comes first;
 * the rest follows grouped by domain. Checked ones are sent as
 * `risk_subdomains[]`.
 */
function RiskSubdomainPicker({
    domains,
    expected,
    selected,
    onToggle,
    disabled,
    error,
}: {
    domains: RiskDomainOption[];
    expected: ExpectedSubdomain[];
    selected: string[];
    onToggle: (code: string, checked: boolean) => void;
    disabled: boolean;
    error?: string;
}) {
    const profile = splitByRiskProfile(domains, expected);
    const checkbox = (
        subdomain: RiskDomainOption['children'][number],
        extra?: ReactNode,
    ) => (
        <SubdomainCheckbox
            key={subdomain.code}
            subdomain={subdomain}
            checked={selected.includes(subdomain.code)}
            onToggle={onToggle}
            extra={extra}
        />
    );

    return (
        <fieldset
            className="grid gap-2"
            aria-invalid={error ? true : undefined}
            aria-describedby="risk_subdomains-help"
        >
            <legend className="mb-2 text-sm font-medium">
                Subdomínios de risco materializados
            </legend>
            <p
                id="risk_subdomains-help"
                className="text-muted-foreground text-sm"
            >
                Selecione os riscos que a ocorrência materializa; um evento pode
                tocar mais de um. Taxonomia de domínios do MIT AI Risk
                Repository.
            </p>

            {disabled ? (
                <p className="text-muted-foreground rounded-lg border border-dashed p-4 text-sm">
                    Escolha o sistema primeiro.
                </p>
            ) : (
                <>
                    {selected.length > 0 && (
                        <p className="text-sm">
                            <span className="text-muted-foreground">
                                Selecionados:{' '}
                            </span>
                            {selected.join(', ')}
                        </p>
                    )}
                    <div className="grid max-h-[32rem] gap-6 overflow-y-auto rounded-lg border p-4">
                        {profile.expected.length > 0 ? (
                            <section className="grid gap-3">
                                <h3 className="text-sm font-semibold">
                                    Esperados para este sistema
                                </h3>
                                {profile.expected.map((subdomain) =>
                                    checkbox(
                                        subdomain,
                                        <span className="text-muted-foreground text-xs">
                                            {termLabel(subdomain.domain)} ·{' '}
                                            {subdomain.risks_count}{' '}
                                            {subdomain.risks_count === 1
                                                ? 'risco cadastrado'
                                                : 'riscos cadastrados'}
                                        </span>,
                                    ),
                                )}
                            </section>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Este sistema ainda não tem riscos cadastrados:
                                todos os subdomínios aparecem abaixo, por
                                domínio.
                            </p>
                        )}

                        <section className="grid gap-4">
                            {profile.expected.length > 0 && (
                                <div className="grid gap-1">
                                    <h3 className="text-sm font-semibold">
                                        Outros subdomínios
                                    </h3>
                                    <p className="text-muted-foreground text-xs">
                                        Um evento fora dos riscos cadastrados
                                        pode indicar um risco ainda não
                                        identificado.
                                    </p>
                                </div>
                            )}
                            {profile.others.map((domain) => (
                                <div key={domain.code} className="grid gap-2">
                                    <p className="text-sm font-medium">
                                        {termLabel(domain)}
                                    </p>
                                    {domain.children.map((subdomain) =>
                                        checkbox(subdomain),
                                    )}
                                </div>
                            ))}
                        </section>
                    </div>
                </>
            )}
            <InputError message={error} />
        </fieldset>
    );
}

function SubdomainCheckbox({
    subdomain,
    checked,
    onToggle,
    extra,
}: {
    subdomain: RiskDomainOption['children'][number];
    checked: boolean;
    onToggle: (code: string, checked: boolean) => void;
    /** A line under the name, such as the domain and risk count. */
    extra?: ReactNode;
}) {
    const id = `risk_subdomain_${subdomain.code}`;

    return (
        <div className="flex items-start gap-3">
            <Checkbox
                id={id}
                name="risk_subdomains[]"
                value={subdomain.code}
                checked={checked}
                onCheckedChange={(state) =>
                    onToggle(subdomain.code, state === true)
                }
                className="mt-0.5"
            />
            <div className="grid gap-0.5">
                <Label htmlFor={id} className="leading-snug">
                    {termLabel(subdomain)}
                </Label>
                {extra}
                {subdomain.description && (
                    // The definition in the source, verbatim.
                    <p lang="en" className="text-muted-foreground text-xs">
                        {subdomain.description}
                    </p>
                )}
            </div>
        </div>
    );
}

AdverseEventsCreate.layout = {
    breadcrumbs: [
        { title: 'Eventos adversos', href: index() },
        { title: 'Registrar', href: create() },
    ],
};
