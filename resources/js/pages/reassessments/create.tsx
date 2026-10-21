import { Form, Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ReassessmentController from '@/actions/App/Http/Controllers/ReassessmentController';
import { DetailItem } from '@/components/detail-item';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { LinkStatusBadges } from '@/components/link-status-badges';
import { UnacceptableTierAlert } from '@/components/unacceptable-tier-alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
} from '@/components/ui/dialog';
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
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { TranslateFn, WithTranslations } from '@/lib/i18n';
import { labelFor } from '@/lib/enum-labels';
import { linkLabel, subdomainCodes } from '@/lib/names';
import { cn } from '@/lib/utils';
import { show as showAdverseEvent } from '@/routes/adverse-events';
import { show as showSystemChange } from '@/routes/system-changes';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { create } from '@/routes/links/reassessments';
import type {
    EnumOption,
    Link as RiskLink,
    Owner,
    ReassessmentOutcome,
    StatusHistory,
} from '@/types/models';

type OutcomeOption = {
    value: ReassessmentOutcome;
    label: string;
    /** Why the outcome is not available now; null when it is. */
    problem: string | null;
};

type Props = {
    link: RiskLink;
    /** The reversal this reassessment concludes. */
    reversal: StatusHistory;
    outcomes: OutcomeOption[];
    /** An adverse event or a manual reversal calls for a cause analysis. */
    causeApplies: boolean;
    /** Why the link cannot be verified in the same act; null when it can. */
    verificationProblem: string | null;
    owners: Pick<Owner, 'id' | 'organizational_role' | 'area'>[];
    costLevels: EnumOption[];
    lifecyclePhases: EnumOption[];
    statuses: EnumOption[];
    causeStatuses: EnumOption[];
};

/**
 * What an outcome does for this link (0020, item 3): for a system in the
 * unacceptable tier, adjusting is how the discontinuation is planned (0020,
 * item 8).
 */
function outcomeEffect(
    outcome: ReassessmentOutcome,
    unacceptable: boolean,
    t: TranslateFn,
): string {
    if (outcome === 'adjust' && unacceptable) {
        return t(
            'Record the discontinuation plan, taking the link to the Retirement phase.',
        );
    }

    const effects: Record<ReassessmentOutcome, string> = {
        maintain: t(
            'The mitigation is still adequate. The link is verified now, with the evidence registered after the reversal.',
        ),
        adjust: t(
            'Changes the owner, estimated cost, phase or progress status. Verifying now is optional; without verification, the link goes on to await verification.',
        ),
        replace: t(
            'Cancels this link and leads to the creation of another, with another mitigation for the same risk.',
        ),
        close: t('Cancels this link, with no replacement.'),
    };

    return effects[outcome];
}

/** Radix Select items cannot be empty, so "no change" needs a stand-in. */
const UNCHANGED = 'unchanged';

/** Lets the confirm button, rendered in a dialog outside the form, submit it. */
const FORM_ID = 'reassessment-form';

export default function ReassessmentsCreate(props: Props) {
    const enumLabels = useEnumLabels();
    const {
        link,
        reversal,
        outcomes,
        causeApplies,
        verificationProblem,
        owners,
        causeStatuses,
    } = props;
    const { t } = useTranslations();
    const [outcome, setOutcome] = useState<ReassessmentOutcome | null>(null);
    const [causeStatus, setCauseStatus] = useState('');
    const [verify, setVerify] = useState(false);
    const [confirming, setConfirming] = useState(false);
    const [missingOutcome, setMissingOutcome] = useState(false);
    const unacceptable = link.risk?.ai_system?.category === 'unacceptable';

    // Check the required fields first, so the dialog only asks about a form
    // that can actually be sent.
    function askToConfirm() {
        const form = document.getElementById(FORM_ID);

        if (!(form instanceof HTMLFormElement) || !form.reportValidity()) {
            return;
        }

        if (outcome === null) {
            setMissingOutcome(true);

            return;
        }

        setConfirming(true);
    }

    return (
        <>
            <Head title={t('Reassess link')} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading
                    title={t('Reassess link')}
                    description={linkLabel(link, t)}
                />

                {unacceptable && <UnacceptableTierAlert short />}

                <ReversalContext link={link} reversal={reversal} />

                <Form
                    {...ReassessmentController.store.form(link.id)}
                    id={FORM_ID}
                    onError={() => setConfirming(false)}
                    className="max-w-3xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <InputError message={errors.reassessment} />

                            <fieldset className="grid gap-3">
                                <legend className="mb-2 text-sm font-medium">
                                    {t('Outcome')}
                                </legend>
                                {outcomes.map((option) => (
                                    <label
                                        key={option.value}
                                        className={cn(
                                            'flex items-start gap-3 rounded-lg border p-3',
                                            option.problem
                                                ? 'cursor-not-allowed opacity-70'
                                                : 'hover:bg-muted/50 cursor-pointer',
                                            outcome === option.value &&
                                                'border-primary',
                                        )}
                                    >
                                        <input
                                            type="radio"
                                            name="outcome"
                                            value={option.value}
                                            disabled={option.problem !== null}
                                            checked={outcome === option.value}
                                            onChange={() => {
                                                setOutcome(option.value);
                                                setMissingOutcome(false);
                                            }}
                                            className="mt-1"
                                        />
                                        <span className="grid gap-0.5">
                                            <span className="font-medium">
                                                {
                                                    enumLabels
                                                        .ReassessmentOutcome[
                                                        option.value
                                                    ]
                                                }
                                            </span>
                                            <span className="text-muted-foreground text-sm">
                                                {outcomeEffect(
                                                    option.value,
                                                    unacceptable,
                                                    t,
                                                )}
                                            </span>
                                            {option.problem && (
                                                <span className="text-sm text-amber-700 dark:text-amber-300">
                                                    {t(
                                                        'Unavailable: :problem',
                                                        {
                                                            problem:
                                                                option.problem,
                                                        },
                                                    )}
                                                </span>
                                            )}
                                        </span>
                                    </label>
                                ))}
                                <InputError
                                    message={
                                        missingOutcome
                                            ? t(
                                                  'Choose the outcome of the reassessment.',
                                              )
                                            : errors.outcome
                                    }
                                />
                            </fieldset>

                            <div className="grid gap-2">
                                <Label htmlFor="owner_id">
                                    {t('Who reassesses')}
                                </Label>
                                <Select
                                    name="owner_id"
                                    defaultValue={
                                        owners.some(
                                            (owner) =>
                                                owner.id === link.owner_id,
                                        )
                                            ? String(link.owner_id)
                                            : undefined
                                    }
                                    required
                                >
                                    <SelectTrigger
                                        id="owner_id"
                                        className="w-full"
                                        aria-invalid={
                                            errors.owner_id ? true : undefined
                                        }
                                    >
                                        <SelectValue
                                            placeholder={t(
                                                'Select the accountable role',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {owners.map((owner) => (
                                            <SelectItem
                                                key={owner.id}
                                                value={String(owner.id)}
                                            >
                                                {owner.organizational_role} (
                                                {owner.area})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.owner_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="justification">
                                    {t('Justification')}
                                </Label>
                                <Textarea
                                    id="justification"
                                    name="justification"
                                    required
                                    maxLength={2000}
                                    className="min-h-28"
                                    placeholder={t(
                                        'E.g.: The mitigation was active; the event came from a case outside its scope.',
                                    )}
                                    aria-invalid={
                                        errors.justification ? true : undefined
                                    }
                                />
                                <InputError message={errors.justification} />
                            </div>

                            {causeApplies && (
                                <CauseFields
                                    causeStatus={causeStatus}
                                    onCauseStatus={setCauseStatus}
                                    causeStatuses={causeStatuses}
                                    lifecyclePhases={props.lifecyclePhases}
                                    errors={errors}
                                />
                            )}

                            {outcome === 'adjust' && (
                                <AdjustFields
                                    {...props}
                                    errors={errors}
                                    verify={verify}
                                    onVerify={setVerify}
                                    verificationProblem={verificationProblem}
                                />
                            )}

                            <InputError message={errors.verification} />

                            <div className="flex items-center gap-4">
                                <Button
                                    type="button"
                                    disabled={processing}
                                    onClick={askToConfirm}
                                >
                                    {t('Record reassessment')}
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={showLink(link.id)}>
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
                                        {t('Record this reassessment?')}
                                    </DialogTitle>
                                    <DialogDescription>
                                        {t(
                                            'Outcome: :outcome. :effect The reassessment is permanent: it can be neither edited nor deleted.',
                                            {
                                                outcome: outcome
                                                    ? enumLabels
                                                          .ReassessmentOutcome[
                                                          outcome
                                                      ]
                                                    : '',
                                                effect: outcome
                                                    ? outcomeEffect(
                                                          outcome,
                                                          unacceptable,
                                                          t,
                                                      )
                                                    : '',
                                            },
                                        )}
                                    </DialogDescription>
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
            </div>
        </>
    );
}

/** What the reassessment answers: the reversal and what triggered it. */
function ReversalContext({
    link,
    reversal,
}: {
    link: RiskLink;
    reversal: StatusHistory;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <Card className="max-w-3xl">
            <CardHeader>
                <CardTitle>{t('Reversal to reassess')}</CardTitle>
            </CardHeader>
            <CardContent className="grid gap-4">
                <dl className="grid gap-4 sm:grid-cols-3">
                    <DetailItem label={t('Origin')}>
                        {enumLabels.ChangeOrigin[reversal.origin]}
                    </DetailItem>
                    <DetailItem label={t('Date')}>
                        {formatDate(reversal.change_date)}
                    </DetailItem>
                    <DetailItem label={t('Link status')}>
                        <LinkStatusBadges link={link} />
                    </DetailItem>
                </dl>
                {reversal.trigger_reason && (
                    <dl>
                        <DetailItem label={t('Reason')}>
                            <p className="font-normal">
                                {reversal.trigger_reason}
                            </p>
                        </DetailItem>
                    </dl>
                )}
                {reversal.system_change && (
                    <dl>
                        <DetailItem label={t('System change')}>
                            <Link
                                href={showSystemChange(
                                    reversal.system_change.id,
                                )}
                                className="hover:underline"
                            >
                                {t(':type of :date', {
                                    type: enumLabels.SystemChangeType[
                                        reversal.system_change.type
                                    ],
                                    date: formatDate(
                                        reversal.system_change.change_date,
                                    ),
                                })}
                            </Link>
                        </DetailItem>
                    </dl>
                )}
                {reversal.adverse_event && (
                    <dl>
                        <DetailItem label={t('Adverse event')}>
                            <Link
                                href={showAdverseEvent(
                                    reversal.adverse_event.id,
                                )}
                                className="hover:underline"
                            >
                                {t(':subdomains of :date', {
                                    subdomains: subdomainCodes(
                                        reversal.adverse_event,
                                    ),
                                    date: formatDate(
                                        reversal.adverse_event.occurrence_date,
                                    ),
                                })}
                            </Link>
                        </DetailItem>
                    </dl>
                )}
            </CardContent>
        </Card>
    );
}

/**
 * The cause analysis (0020, item 6), for an adverse event or a manual
 * reversal: identified, with the cause and the phase it came from, or
 * explicitly not identified.
 */
function CauseFields({
    causeStatus,
    onCauseStatus,
    causeStatuses,
    lifecyclePhases,
    errors,
}: {
    causeStatus: string;
    onCauseStatus: (value: string) => void;
    causeStatuses: EnumOption[];
    lifecyclePhases: EnumOption[];
    errors: Partial<Record<string, string>>;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <fieldset className="grid gap-3 rounded-lg border p-4">
            <legend className="px-1 text-sm font-medium">
                {t('Cause analysis')}
            </legend>
            <div className="flex flex-wrap gap-4">
                {causeStatuses.map((option) => (
                    <label
                        key={option.value}
                        className="flex items-center gap-2 text-sm"
                    >
                        <input
                            type="radio"
                            name="cause_status"
                            value={option.value}
                            checked={causeStatus === option.value}
                            onChange={() => onCauseStatus(option.value)}
                            required
                        />
                        {option.value === 'identified'
                            ? t('Cause found')
                            : t('Cause not found')}
                    </label>
                ))}
            </div>
            <InputError message={errors.cause_status} />

            {causeStatus === 'identified' && (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="cause">{t('Cause found')}</Label>
                        <Textarea
                            id="cause"
                            name="cause"
                            required
                            maxLength={2000}
                            placeholder={t(
                                'E.g.: Input outside the expected domain, which the filter did not cover.',
                            )}
                            aria-invalid={errors.cause ? true : undefined}
                        />
                        <InputError message={errors.cause} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="cause_phase">
                            {t('Lifecycle phase in which the cause originated')}
                        </Label>
                        <Select name="cause_phase" required>
                            <SelectTrigger
                                id="cause_phase"
                                className="w-full sm:w-72"
                                aria-invalid={
                                    errors.cause_phase ? true : undefined
                                }
                            >
                                <SelectValue
                                    placeholder={t('Select the phase')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {lifecyclePhases.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={option.value}
                                    >
                                        {labelFor(
                                            enumLabels.LifecyclePhase,
                                            option.value,
                                        )}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.cause_phase} />
                    </div>
                </>
            )}
        </fieldset>
    );
}

/**
 * What an adjustment changes (0020): each field left unchanged stays as it
 * is. Verifying in the same act is optional, when the evidence allows it.
 */
function AdjustFields({
    link,
    owners,
    costLevels,
    lifecyclePhases,
    statuses,
    errors,
    verify,
    onVerify,
    verificationProblem,
}: Props & {
    errors: Partial<Record<string, string>>;
    verify: boolean;
    onVerify: (value: boolean) => void;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <fieldset className="grid gap-4 rounded-lg border p-4">
            <legend className="px-1 text-sm font-medium">
                {t('Adjustments to the link')}
            </legend>
            <p className="text-muted-foreground text-sm">
                {t(
                    'Leave "No change" on what does not change. The before and after of each change are recorded in the reassessment.',
                )}
            </p>
            <div className="grid gap-4 sm:grid-cols-2">
                <ChangeSelect
                    name="changes[owner_id]"
                    label={t('Owner')}
                    current={`${link.owner?.organizational_role ?? ''}`}
                    options={owners
                        .filter((owner) => owner.id !== link.owner_id)
                        .map((owner) => ({
                            value: String(owner.id),
                            label: `${owner.organizational_role} (${owner.area})`,
                        }))}
                    error={errors['changes.owner_id']}
                />
                <ChangeSelect
                    name="changes[estimated_cost]"
                    label={t('Estimated cost')}
                    current={enumLabels.CostLevel[link.estimated_cost]}
                    options={costLevels
                        .filter(
                            (option) => option.value !== link.estimated_cost,
                        )
                        .map((option) => ({
                            value: option.value,
                            label: labelFor(enumLabels.CostLevel, option.value),
                        }))}
                    error={errors['changes.estimated_cost']}
                />
                <ChangeSelect
                    name="changes[lifecycle_phase]"
                    label={t('Lifecycle phase')}
                    current={enumLabels.LifecyclePhase[link.lifecycle_phase]}
                    options={lifecyclePhases
                        .filter(
                            (option) => option.value !== link.lifecycle_phase,
                        )
                        .map((option) => ({
                            value: option.value,
                            label: labelFor(
                                enumLabels.LifecyclePhase,
                                option.value,
                            ),
                        }))}
                    error={errors['changes.lifecycle_phase']}
                />
                <ChangeSelect
                    name="changes[status]"
                    label={t('Progress status')}
                    current={enumLabels.LinkStatus[link.status]}
                    options={statuses.map((option) => ({
                        value: option.value,
                        label: labelFor(enumLabels.LinkStatus, option.value),
                    }))}
                    error={errors['changes.status']}
                />
            </div>

            <div className="flex items-start gap-3">
                <Checkbox
                    id="verify"
                    name="verify"
                    value="1"
                    checked={verify}
                    onCheckedChange={(state) => onVerify(state === true)}
                    disabled={verificationProblem !== null}
                    className="mt-0.5"
                />
                <div className="grid gap-0.5">
                    <Label htmlFor="verify">
                        {t('Verify in the same act')}
                    </Label>
                    <p className="text-muted-foreground text-sm">
                        {verificationProblem ??
                            t(
                                'Use it when the change is already applied and the evidence registered after the reversal proves it. Without verifying, the link goes on to await verification.',
                            )}
                    </p>
                </div>
            </div>
        </fieldset>
    );
}

function ChangeSelect({
    name,
    label,
    current,
    options,
    error,
}: {
    name: string;
    label: string;
    /** The value today, shown so the change reads as before and after. */
    current: string;
    options: { value: string; label: string }[];
    error?: string;
}) {
    const { t } = useTranslations();
    const [value, setValue] = useState(UNCHANGED);
    const id = name.replace(/\W+/g, '_');

    return (
        <div className="grid gap-2">
            <Label htmlFor={id}>{label}</Label>
            <Select value={value} onValueChange={setValue}>
                <SelectTrigger
                    id={id}
                    className="w-full"
                    aria-invalid={error ? true : undefined}
                >
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={UNCHANGED}>
                        {t('No change (:current)', { current })}
                    </SelectItem>
                    {options.map((option) => (
                        <SelectItem key={option.value} value={option.value}>
                            {option.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            {/* Sent empty when unchanged. */}
            <input
                type="hidden"
                name={name}
                value={value === UNCHANGED ? '' : value}
            />
            <InputError message={error} />
        </div>
    );
}

ReassessmentsCreate.layout = ({
    link,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: linksIndex() },
            { title: linkLabel(link, t), href: showLink(link.id) },
            { title: t('Reassess'), href: create(link.id) },
        ],
    };
};
