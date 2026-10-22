import { Form, Link as InertiaLink, usePage } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useState } from 'react';
import LinkVerificationController from '@/actions/App/Http/Controllers/LinkVerificationController';
import { DetailItem } from '@/components/detail-item';
import { EntryAuthor } from '@/components/entry-author';
import { Term } from '@/components/help';
import InputError from '@/components/input-error';
import { LinkStatusBadges } from '@/components/link-status-badges';
import { ReviewDate } from '@/components/review-date';
import { UNACCEPTABLE_ALERT_ID } from '@/components/unacceptable-tier-alert';
import { Button } from '@/components/ui/button';
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
import { interpolateNodes } from '@/lib/i18n';
import { subdomainCodes } from '@/lib/names';
import { show as showAdverseEvent } from '@/routes/adverse-events';
import { show as showAiSystem } from '@/routes/ai-systems';
import { create as createEvidence } from '@/routes/links/evidence';
import { create as createReassessment } from '@/routes/links/reassessments';
import { create as createStatusChange } from '@/routes/links/status-histories';
import { show as showReassessment } from '@/routes/reassessments';
import { show as showSystemChange } from '@/routes/system-changes';
import type { Link, Owner } from '@/types/models';

type VerifierOption = Pick<Owner, 'id' | 'organizational_role' | 'area'>;

export type VerificationProps = {
    /** Why the link cannot be verified now; null when it can. */
    problem: string | null;
    /** The active owners who may verify or revert. */
    owners: VerifierOption[];
};

/**
 * The state of a link and its next action. The state shows both dimensions
 * (0013), the review date and, for a declared link that was verified before,
 * the reversal awaiting reassessment (0018, item 1). The next action is the
 * one move the link calls for now, with the reason when it is blocked:
 * reassess, verify, renew on new evidence, or reactivate a cancelled link.
 */
export function LinkVerificationCard({
    link,
    verification,
    awaitingReassessment,
}: {
    link: Link;
    verification: VerificationProps;
    awaitingReassessment: boolean;
}) {
    const { t } = useTranslations();
    const { url } = usePage();
    const declared = link.verification_status === 'declared';
    const cancelled = link.status === 'cancelled';
    const reversal = declared ? link.last_reversal : null;
    const unacceptable = link.risk?.ai_system?.category === 'unacceptable';
    // The evidence page links here with `?verify=1` once the link is ready.
    const [verifying, setVerifying] = useState(
        verification.problem === null &&
            new URLSearchParams(url.split('?')[1]).get('verify') === '1',
    );
    const [reverting, setReverting] = useState(false);

    return (
        <section
            aria-labelledby="link-state"
            className="bg-card grid border md:grid-cols-2"
        >
            <div className="grid content-start gap-6 border-b p-6 md:border-r md:border-b-0">
                <h2 id="link-state" className="text-base font-semibold">
                    {t('Link state')}
                </h2>
                <div className="flex flex-wrap items-start gap-8">
                    <LinkStatusBadges link={link} labelled />
                    <span className="grid gap-1">
                        <span className="text-muted-foreground text-xs tracking-[0.32px]">
                            {t('Next review')}
                        </span>
                        <span>
                            <ReviewDate
                                date={link.next_review_date}
                                verification={link.verification_status}
                                unacceptable={unacceptable}
                            />
                        </span>
                    </span>
                </div>
                {reversal && <ReversalNotice link={link} />}
                <dl className="grid gap-4 sm:grid-cols-2">
                    <DetailItem label={t('Last verification')}>
                        {link.last_verification ? (
                            formatDate(link.last_verification.change_date)
                        ) : (
                            <span className="text-muted-foreground">
                                {t('Never verified')}
                            </span>
                        )}
                    </DetailItem>
                    <DetailItem label={t('Verified by')}>
                        {link.last_verification ? (
                            <EntryAuthor entry={link.last_verification} />
                        ) : (
                            <span className="text-muted-foreground">—</span>
                        )}
                    </DetailItem>
                </dl>
            </div>

            <div className="bg-muted grid content-start gap-4 p-6">
                <h2 className="text-base font-semibold">{t('Next action')}</h2>
                {cancelled ? (
                    <>
                        <p>
                            {t(
                                'None: the link is closed. A cancelled link cannot be verified nor reassessed.',
                            )}
                        </p>
                        <div>
                            <Button variant="outline" asChild>
                                <InertiaLink
                                    href={createStatusChange(link.id, {
                                        query: { new_status: 'planned' },
                                    })}
                                >
                                    {t('Reactivate link')}
                                </InertiaLink>
                            </Button>
                        </div>
                    </>
                ) : awaitingReassessment ? (
                    <>
                        <p>
                            {unacceptable
                                ? t(
                                      'Record how the link takes part in the discontinuation plan.',
                                  )
                                : t(
                                      'Decide whether the mitigation is still adequate: maintain, adjust, replace or close.',
                                  )}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            <Button asChild>
                                <InertiaLink href={createReassessment(link.id)}>
                                    {t('Reassess')}
                                </InertiaLink>
                            </Button>
                            <Button variant="outline" asChild>
                                <InertiaLink href={createEvidence(link.id)}>
                                    {t('Register evidence')}
                                </InertiaLink>
                            </Button>
                        </div>
                        <BlockedReason problem={verification.problem} />
                    </>
                ) : declared ? (
                    <>
                        <p>
                            {verification.problem === null
                                ? t(
                                      'The link has the evidence the verification needs. Verify it to start the periodic review.',
                                  )
                                : t(
                                      'Register the evidence that proves the mitigation, then verify the link.',
                                  )}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {verification.problem === null ? (
                                <Button onClick={() => setVerifying(true)}>
                                    {t('Verify')}
                                </Button>
                            ) : (
                                <Button asChild>
                                    <InertiaLink href={createEvidence(link.id)}>
                                        {t('Register evidence')}
                                    </InertiaLink>
                                </Button>
                            )}
                        </div>
                        <BlockedReason problem={verification.problem} />
                    </>
                ) : (
                    <>
                        <p>
                            {link.next_review_date
                                ? t(
                                      'Nothing pending until :date. To renew sooner, register new evidence.',
                                      {
                                          date: formatDate(
                                              link.next_review_date,
                                          ),
                                      },
                                  )
                                : t('Nothing pending.')}
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {/* Renewed before it falls due, on evidence
                                stored since the last verification (0019). */}
                            {verification.problem === null ? (
                                <Button onClick={() => setVerifying(true)}>
                                    {t('Renew verification')}
                                </Button>
                            ) : (
                                <Button asChild>
                                    <InertiaLink href={createEvidence(link.id)}>
                                        {t('Register evidence')}
                                    </InertiaLink>
                                </Button>
                            )}
                        </div>
                        <BlockedReason problem={verification.problem} />
                        <div>
                            <Button
                                variant="link"
                                className="h-auto px-0"
                                onClick={() => setReverting(true)}
                            >
                                {t('Revert verification')}
                            </Button>
                        </div>
                    </>
                )}
            </div>

            <VerifyDialog
                renewal={!declared}
                link={link}
                owners={verification.owners}
                open={verifying}
                onOpenChange={setVerifying}
            />
            <RevertDialog
                link={link}
                owners={verification.owners}
                open={reverting}
                onOpenChange={setReverting}
            />
        </section>
    );
}

/** Why the verification is blocked, written out next to the action. */
function BlockedReason({ problem }: { problem: string | null }) {
    if (problem === null) {
        return null;
    }

    return (
        <p className="text-muted-foreground text-xs tracking-[0.32px]">
            {problem}
        </p>
    );
}

/**
 * A verified link that went back to declared (0018, item 1). A link of a
 * system in the unacceptable tier still counts as awaiting reassessment, but
 * cannot be verified again: what it calls for is to plan the discontinuation
 * (0019, addendum).
 */
function ReversalNotice({ link }: { link: Link }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const reversal = link.last_reversal;
    const aiSystem = link.risk?.ai_system;

    if (!reversal) {
        return null;
    }

    return (
        <div className="border-warning bg-muted grid grid-cols-[20px_1fr] gap-3 border-l-[3px] p-4 text-sm">
            <TriangleAlertIcon
                className="fill-warning text-foreground size-5"
                aria-hidden
            />
            <div className="grid gap-1">
                {reversal.reassessment ? (
                    // Decided, still to be proven (0020, item 4).
                    <p className="font-semibold">
                        {interpolateNodes(
                            t(
                                'Reassessed (:reassessment): awaiting verification',
                            ),
                            {
                                reassessment: (
                                    <InertiaLink
                                        href={showReassessment(
                                            reversal.reassessment.id,
                                        )}
                                        className="text-link underline-offset-4 hover:underline"
                                    >
                                        {t(':outcome on :date', {
                                            outcome:
                                                enumLabels.ReassessmentOutcome[
                                                    reversal.reassessment
                                                        .outcome
                                                ].toLowerCase(),
                                            date: formatDate(
                                                reversal.reassessment
                                                    .reassessment_date,
                                            ),
                                        })}
                                    </InertiaLink>
                                ),
                            },
                        )}
                    </p>
                ) : aiSystem?.category === 'unacceptable' ? (
                    <p className="font-semibold">
                        {interpolateNodes(
                            t(':system: plan the discontinuation'),
                            {
                                system: (
                                    <InertiaLink
                                        href={`${showAiSystem.url(aiSystem.id)}#${UNACCEPTABLE_ALERT_ID}`}
                                        className="text-link underline-offset-4 hover:underline"
                                    >
                                        {t('System in the unacceptable tier')}
                                    </InertiaLink>
                                ),
                            },
                        )}
                    </p>
                ) : (
                    <p className="font-semibold">
                        <Term
                            definition={t(
                                'A declared link whose last reversal has no concluded reassessment yet.',
                            )}
                        >
                            {t('Awaiting reassessment')}
                        </Term>
                    </p>
                )}
                <p>
                    {t('Verification reverted on :date (origin: :origin).', {
                        date: formatDate(reversal.change_date),
                        origin: enumLabels.ChangeOrigin[
                            reversal.origin
                        ].toLowerCase(),
                    })}
                </p>
                {reversal.trigger_reason && (
                    <p>
                        {t('Reason: :reason', {
                            reason: reversal.trigger_reason,
                        })}
                    </p>
                )}
                {reversal.system_change && (
                    <p>
                        {interpolateNodes(t('System change: :change'), {
                            change: (
                                <InertiaLink
                                    href={showSystemChange(
                                        reversal.system_change.id,
                                    )}
                                    className="text-link underline-offset-4 hover:underline"
                                >
                                    {t(':type of :date', {
                                        type: enumLabels.SystemChangeType[
                                            reversal.system_change.type
                                        ],
                                        date: formatDate(
                                            reversal.system_change.change_date,
                                        ),
                                    })}
                                </InertiaLink>
                            ),
                        })}
                    </p>
                )}
                {reversal.adverse_event && (
                    <p>
                        {interpolateNodes(t('Adverse event: :event'), {
                            event: (
                                <InertiaLink
                                    href={showAdverseEvent(
                                        reversal.adverse_event.id,
                                    )}
                                    className="text-link underline-offset-4 hover:underline"
                                >
                                    {t(':subdomains of :date', {
                                        subdomains: subdomainCodes(
                                            reversal.adverse_event,
                                        ),
                                        date: formatDate(
                                            reversal.adverse_event
                                                .occurrence_date,
                                        ),
                                    })}
                                </InertiaLink>
                            ),
                        })}
                    </p>
                )}
            </div>
        </div>
    );
}

function VerifyDialog({
    renewal,
    link,
    owners,
    open,
    onOpenChange,
}: {
    /** The link is verified: this renews the verification. */
    renewal: boolean;
    link: Link;
    owners: VerifierOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { t } = useTranslations();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>
                    {renewal
                        ? t('Renew the verification of this link?')
                        : t('Verify this link?')}
                </DialogTitle>
                <DialogDescription>
                    {renewal
                        ? t(
                              "The new evidence renews the verification before it falls due: the periodic review counts again from today, with the interval of the system's current tier.",
                          )
                        : t(
                              "The periodic review counts from today, with the interval of the system's tier.",
                          )}{' '}
                    {t('The record stays in the history.')}
                </DialogDescription>
                <Form
                    {...LinkVerificationController.store.form(link.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <OwnerSelect
                                owners={owners}
                                defaultOwnerId={link.owner_id}
                                label={t('Who verifies')}
                                error={errors.owner_id}
                            />
                            <InputError message={errors.verification} />
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        {t('Back')}
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    {renewal ? t('Renew') : t('Verify')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

function RevertDialog({
    link,
    owners,
    open,
    onOpenChange,
}: {
    link: Link;
    owners: VerifierOption[];
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { t } = useTranslations();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>{t('Revert the verification?')}</DialogTitle>
                <DialogDescription>
                    {t(
                        'The link goes back to declared and leaves the periodic review. To verify it again, new evidence must be registered.',
                    )}
                </DialogDescription>
                <Form
                    {...LinkVerificationController.destroy.form(link.id)}
                    options={{ preserveScroll: true }}
                    onSuccess={() => onOpenChange(false)}
                    className="grid gap-4"
                >
                    {({ processing, errors }) => (
                        <>
                            <OwnerSelect
                                owners={owners}
                                defaultOwnerId={link.owner_id}
                                label={t('Who reverts')}
                                error={errors.owner_id}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="trigger_reason">
                                    {t('Reason')}
                                </Label>
                                <Textarea
                                    id="trigger_reason"
                                    name="trigger_reason"
                                    required
                                    maxLength={255}
                                    placeholder={t(
                                        'E.g.: The audit found the control disabled in production.',
                                    )}
                                    aria-invalid={
                                        errors.trigger_reason ? true : undefined
                                    }
                                />
                                <InputError message={errors.trigger_reason} />
                            </div>
                            <InputError message={errors.verification} />
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        {t('Back')}
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    {t('Revert')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

/**
 * One active owner, a role and not a person (0018, item 6); the link's
 * owner comes first when active.
 */
function OwnerSelect({
    owners,
    defaultOwnerId,
    label,
    error,
}: {
    owners: VerifierOption[];
    defaultOwnerId: number;
    label: string;
    error?: string;
}) {
    const { t } = useTranslations();
    const preselected = owners.some((owner) => owner.id === defaultOwnerId)
        ? String(defaultOwnerId)
        : undefined;

    return (
        <div className="grid gap-2">
            <Label htmlFor="verification_owner_id">{label}</Label>
            <Select name="owner_id" defaultValue={preselected} required>
                <SelectTrigger
                    id="verification_owner_id"
                    className="w-full"
                    aria-invalid={error ? true : undefined}
                >
                    <SelectValue
                        placeholder={t('Select the accountable role')}
                    />
                </SelectTrigger>
                <SelectContent>
                    {owners.map((owner) => (
                        <SelectItem key={owner.id} value={String(owner.id)}>
                            {owner.organizational_role} ({owner.area})
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={error} />
        </div>
    );
}
