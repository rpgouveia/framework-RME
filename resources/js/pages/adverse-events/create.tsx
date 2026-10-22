import { Form, Head, Link, usePage } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useState } from 'react';
import AdverseEventController from '@/actions/App/Http/Controllers/AdverseEventController';
import {
    RiskSubdomainPicker,
    subdomainError,
} from '@/components/risk-subdomain-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { dateInputValue } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { eventReassessment } from '@/lib/event-reassessment';
import type { SystemLink } from '@/lib/event-reassessment';
import { labelFor } from '@/lib/enum-labels';
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
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
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
            <Head title={t('Register adverse event')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Register adverse event')}
                    description={t(
                        'Record something that went wrong in an AI system in operation.',
                    )}
                />
                {aiSystems.length === 0 ? (
                    <p className="text-muted-foreground max-w-xl text-sm">
                        {t(
                            'No AI system has been registered yet. Every adverse event happens in a system.',
                        )}
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
                                        {t('Affected system')}
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
                                            <SelectValue
                                                placeholder={t(
                                                    'Select the system',
                                                )}
                                            />
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
                                    <Label htmlFor="nature">
                                        {t('Nature')}
                                    </Label>
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
                                            <SelectValue
                                                placeholder={t(
                                                    'Select the nature',
                                                )}
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {natures.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {labelFor(
                                                        enumLabels.AdverseEventNature,
                                                        option.value,
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-muted-foreground text-sm">
                                        {t(
                                            'A near miss was noticed before it caused harm. Both trigger the reassessment of the links.',
                                        )}
                                    </p>
                                    <InputError message={errors.nature} />
                                </div>

                                <RiskSubdomainPicker
                                    legend={t('Risk subdomains materialized')}
                                    help={t(
                                        'Select the risks the occurrence materializes; an event may touch more than one.',
                                    )}
                                    domains={riskDomains}
                                    expected={expected}
                                    selected={selected}
                                    onToggle={toggleSubdomain}
                                    disabled={!aiSystemId}
                                    error={
                                        missingSubdomain
                                            ? t(
                                                  'Select at least one risk subdomain the occurrence materializes.',
                                              )
                                            : subdomainError(errors)
                                    }
                                />

                                <div className="grid gap-2">
                                    <Label htmlFor="description">
                                        {t('Description')}
                                    </Label>
                                    <Textarea
                                        id="description"
                                        name="description"
                                        required
                                        maxLength={2000}
                                        className="max-h-80 min-h-32"
                                        placeholder={t(
                                            'E.g.: The model refused every request from customers over 60 for two days.',
                                        )}
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
                                        {t('Occurrence date')}
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
                                        {t('It cannot be a future date.')}
                                    </p>
                                    <InputError
                                        message={errors.occurrence_date}
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="detected_at">
                                        {t('Detection date')}{' '}
                                        <span className="text-muted-foreground font-normal">
                                            {t('(optional)')}
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
                                        {t(
                                            'When the monitoring noticed the occurrence, between the occurrence date and today. The interval measures how long detecting took.',
                                        )}
                                    </p>
                                    <InputError message={errors.detected_at} />
                                </div>

                                {nearMiss && aiSystemId && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="intercepting_link_id">
                                            {t('Intercepting link')}{' '}
                                            <span className="text-muted-foreground font-normal">
                                                {t('(optional)')}
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
                                                    {t('None')}
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
                                                ? t(
                                                      'No link of this system treats the selected subdomains.',
                                                  )
                                                : t(
                                                      'The link whose mitigation stopped the occurrence. It is not reverted, because the event shows it worked.',
                                                  )}
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
                                        {t('The record is permanent')}
                                    </AlertTitle>
                                    <AlertDescription>
                                        {t(
                                            "Adverse events can be neither edited nor deleted. The record takes back to declared the system's verified links whose risk is in one of the event's subdomains.",
                                        )}
                                    </AlertDescription>
                                </Alert>

                                <div className="flex items-center gap-4">
                                    <Button
                                        type="button"
                                        disabled={processing}
                                        onClick={askToConfirm}
                                    >
                                        {t('Record event')}
                                    </Button>
                                    <Button variant="ghost" asChild>
                                        <Link href={index()}>
                                            {t('Cancel')}
                                        </Link>
                                    </Button>
                                </div>

                                <Dialog
                                    open={confirming}
                                    onOpenChange={setConfirming}
                                >
                                    <DialogContent>
                                        <DialogTitle>
                                            {t('Record this adverse event?')}
                                        </DialogTitle>
                                        <DialogDescription>
                                            {t(
                                                'Check the system, the risk subdomains (:subdomains) and the date: once recorded, the event can be neither edited nor deleted.',
                                                {
                                                    subdomains:
                                                        selected.join(', '),
                                                },
                                            )}
                                        </DialogDescription>
                                        <ReversalPreview reverted={reverted} />
                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">
                                                    {t('Back')}
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                form={FORM_ID}
                                                disabled={processing}
                                            >
                                                {t('Record')}
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
    const { t, tc } = useTranslations();

    if (reverted.length === 0) {
        return (
            <p className="text-sm">
                {t('No verified link of this system will be reverted.')}
            </p>
        );
    }

    return (
        <div className="grid gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
            <p className="font-semibold">
                {tc(
                    ':count verified link will go back to declared, awaiting reassessment:|:count verified links will go back to declared, awaiting reassessment:',
                    reverted.length,
                )}
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

AdverseEventsCreate.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Adverse events'), href: index() },
            { title: t('Record'), href: create() },
        ],
    };
};
