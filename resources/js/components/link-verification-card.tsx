import { Form, Link as InertiaLink, usePage } from '@inertiajs/react';
import { useState } from 'react';
import LinkVerificationController from '@/actions/App/Http/Controllers/LinkVerificationController';
import { DetailItem } from '@/components/detail-item';
import { EntryAuthor } from '@/components/entry-author';
import InputError from '@/components/input-error';
import { VerificationBadge } from '@/components/link-status-badges';
import { UNACCEPTABLE_ALERT_ID } from '@/components/unacceptable-tier-alert';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
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
 * The verification dimension of a link (0013, 0018): its state, the last
 * verification, and the two moves, verify on evidence or revert by hand.
 * A declared link that was verified before is awaiting reassessment.
 */
export function LinkVerificationCard({
    link,
    verification,
}: {
    link: Link;
    verification: VerificationProps;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const { url } = usePage();
    const declared = link.verification_status === 'declared';
    const reversal = declared ? link.last_reversal : null;
    const aiSystem = link.risk?.ai_system;
    const unacceptable = aiSystem?.category === 'unacceptable';
    // The evidence page links here with `?verify=1` once the link is ready.
    const [verifying, setVerifying] = useState(
        verification.problem === null &&
            new URLSearchParams(url.split('?')[1]).get('verify') === '1',
    );
    const [reverting, setReverting] = useState(false);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex flex-wrap items-center gap-2">
                    {t('Verification')}
                    <VerificationBadge
                        verification={link.verification_status}
                    />
                </CardTitle>
                <CardDescription>
                    {t(
                        'The verification proves, with evidence, that the mitigation is applied. The periodic review starts at the verification.',
                    )}
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-6">
                {reversal && (
                    // Flagged for review: a verified link went back to
                    // declared (0018, item 1). A link of a system in the
                    // unacceptable tier still counts as awaiting reassessment,
                    // but cannot be verified again: what it calls for is to
                    // plan the discontinuation (0019, addendum).
                    <div className="grid gap-1 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
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
                                                className="underline underline-offset-4"
                                            >
                                                {t(':outcome on :date', {
                                                    outcome:
                                                        enumLabels.ReassessmentOutcome[
                                                            reversal
                                                                .reassessment
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
                        ) : unacceptable && aiSystem ? (
                            <p className="font-semibold">
                                {interpolateNodes(
                                    t(':system: plan the discontinuation'),
                                    {
                                        system: (
                                            <InertiaLink
                                                href={`${showAiSystem.url(aiSystem.id)}#${UNACCEPTABLE_ALERT_ID}`}
                                                className="underline underline-offset-4"
                                            >
                                                {t(
                                                    'System in the unacceptable tier',
                                                )}
                                            </InertiaLink>
                                        ),
                                    },
                                )}
                            </p>
                        ) : (
                            <p className="font-semibold">
                                {t('Awaiting reassessment')}
                            </p>
                        )}
                        <p>
                            {t(
                                'Verification reverted on :date (origin: :origin).',
                                {
                                    date: formatDate(reversal.change_date),
                                    origin: enumLabels.ChangeOrigin[
                                        reversal.origin
                                    ].toLowerCase(),
                                },
                            )}
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
                                            className="font-medium underline underline-offset-4"
                                        >
                                            {t(':type of :date', {
                                                type: enumLabels
                                                    .SystemChangeType[
                                                    reversal.system_change.type
                                                ],
                                                date: formatDate(
                                                    reversal.system_change
                                                        .change_date,
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
                                            className="font-medium underline underline-offset-4"
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
                )}

                <dl className="grid gap-4 sm:grid-cols-2">
                    <DetailItem label={t('Last verification')}>
                        {link.last_verification ? (
                            formatDate(link.last_verification.change_date)
                        ) : (
                            <span className="text-muted-foreground font-normal">
                                {t('Never verified')}
                            </span>
                        )}
                    </DetailItem>
                    <DetailItem label={t('Verified by')}>
                        {link.last_verification ? (
                            <EntryAuthor entry={link.last_verification} />
                        ) : (
                            <span className="text-muted-foreground font-normal">
                                —
                            </span>
                        )}
                    </DetailItem>
                </dl>

                {declared ? (
                    <div className="grid justify-items-start gap-2">
                        <Button
                            disabled={verification.problem !== null}
                            onClick={() => setVerifying(true)}
                            aria-describedby={
                                verification.problem
                                    ? 'verification-problem'
                                    : undefined
                            }
                        >
                            {t('Verify')}
                        </Button>
                        {verification.problem && (
                            <p
                                id="verification-problem"
                                className="text-muted-foreground text-sm"
                            >
                                {verification.problem}
                            </p>
                        )}
                    </div>
                ) : (
                    <div className="grid gap-2">
                        <div className="flex flex-wrap gap-2">
                            {/* Renewed before it falls due, on evidence
                                stored since the last verification (0019). */}
                            <Button
                                disabled={verification.problem !== null}
                                onClick={() => setVerifying(true)}
                                aria-describedby={
                                    verification.problem
                                        ? 'verification-problem'
                                        : undefined
                                }
                            >
                                {t('Renew verification')}
                            </Button>
                            <Button
                                variant="outline"
                                onClick={() => setReverting(true)}
                            >
                                {t('Revert verification')}
                            </Button>
                        </div>
                        {verification.problem && (
                            <p
                                id="verification-problem"
                                className="text-muted-foreground text-sm"
                            >
                                {verification.problem}
                            </p>
                        )}
                    </div>
                )}
            </CardContent>

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
        </Card>
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
