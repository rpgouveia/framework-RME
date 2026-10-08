import { Form, usePage } from '@inertiajs/react';
import { useState } from 'react';
import LinkVerificationController from '@/actions/App/Http/Controllers/LinkVerificationController';
import { DetailItem } from '@/components/detail-item';
import { EntryAuthor } from '@/components/entry-author';
import InputError from '@/components/input-error';
import { VerificationBadge } from '@/components/link-status-badges';
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
import { formatDate } from '@/lib/format';
import { changeOriginLabels } from '@/lib/labels';
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
    const { url } = usePage();
    const declared = link.verification_status === 'declared';
    const reversal = declared ? link.last_reversal : null;
    // The evidence page links here with `?verify=1` once the link is ready.
    const [verifying, setVerifying] = useState(
        declared &&
            verification.problem === null &&
            new URLSearchParams(url.split('?')[1]).get('verify') === '1',
    );
    const [reverting, setReverting] = useState(false);

    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex flex-wrap items-center gap-2">
                    Verificação
                    <VerificationBadge
                        verification={link.verification_status}
                    />
                </CardTitle>
                <CardDescription>
                    A verificação comprova, com evidência, que a mitigação está
                    aplicada. A revisão periódica começa na verificação.
                </CardDescription>
            </CardHeader>
            <CardContent className="grid gap-6">
                {reversal && (
                    // Flagged for review: a verified link went back to
                    // declared (0018, item 1).
                    <div className="grid gap-1 rounded-lg border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        <p className="font-semibold">Aguardando reavaliação</p>
                        <p>
                            Verificação revertida em{' '}
                            {formatDate(reversal.change_date)} (origem:{' '}
                            {changeOriginLabels[reversal.origin].toLowerCase()}
                            ).
                        </p>
                        {reversal.trigger_reason && (
                            <p>Motivo: {reversal.trigger_reason}</p>
                        )}
                    </div>
                )}

                <dl className="grid gap-4 sm:grid-cols-2">
                    <DetailItem label="Última verificação">
                        {link.last_verification ? (
                            formatDate(link.last_verification.change_date)
                        ) : (
                            <span className="text-muted-foreground font-normal">
                                Nunca verificado
                            </span>
                        )}
                    </DetailItem>
                    <DetailItem label="Verificado por">
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
                            Verificar
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
                    <div>
                        <Button
                            variant="outline"
                            onClick={() => setReverting(true)}
                        >
                            Reverter verificação
                        </Button>
                    </div>
                )}
            </CardContent>

            <VerifyDialog
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
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>Verificar este vínculo?</DialogTitle>
                <DialogDescription>
                    A revisão periódica passa a contar a partir de hoje, com o
                    intervalo da faixa do sistema. O registro fica no histórico.
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
                                label="Quem verifica"
                                error={errors.owner_id}
                            />
                            <InputError message={errors.verification} />
                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button type="button" variant="secondary">
                                        Voltar
                                    </Button>
                                </DialogClose>
                                <Button type="submit" disabled={processing}>
                                    Verificar
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
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogTitle>Reverter a verificação?</DialogTitle>
                <DialogDescription>
                    O vínculo volta a declarado e sai da revisão periódica. Para
                    verificá-lo de novo, será preciso registrar uma evidência
                    nova.
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
                                label="Quem reverte"
                                error={errors.owner_id}
                            />
                            <div className="grid gap-2">
                                <Label htmlFor="trigger_reason">Motivo</Label>
                                <Textarea
                                    id="trigger_reason"
                                    name="trigger_reason"
                                    required
                                    maxLength={255}
                                    placeholder="Ex.: A auditoria encontrou o controle desativado em produção."
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
                                        Voltar
                                    </Button>
                                </DialogClose>
                                <Button
                                    type="submit"
                                    variant="destructive"
                                    disabled={processing}
                                >
                                    Reverter
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
                    <SelectValue placeholder="Selecione o papel responsável" />
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
