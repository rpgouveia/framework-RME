import { Form, Head, Link } from '@inertiajs/react';
import { LockIcon } from 'lucide-react';
import { useState } from 'react';
import EvidenceController from '@/actions/App/Http/Controllers/EvidenceController';
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
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    costLevelLabels,
    evidenceTypeLabels,
    labelFor,
    linkLabel,
} from '@/lib/labels';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { create, index } from '@/routes/links/evidence';
import type { EnumOption, Link as RiskLink } from '@/types/models';

type Props = {
    link: RiskLink;
    types: EnumOption[];
    costLevels: EnumOption[];
};

/** Radix Select items cannot be empty, so "not informed" needs a stand-in. */
const NOT_INFORMED = 'none';

/** Lets the confirm button, rendered in a dialog outside the form, submit it. */
const FORM_ID = 'evidence-form';

export default function EvidenceCreate({ link, types, costLevels }: Props) {
    const [confirming, setConfirming] = useState(false);
    const [observedCost, setObservedCost] = useState(NOT_INFORMED);

    // Check the required fields first, so the dialog only asks about a form
    // that can actually be sent.
    function askToConfirm() {
        const form = document.getElementById(FORM_ID);

        if (form instanceof HTMLFormElement && form.reportValidity()) {
            setConfirming(true);
        }
    }

    return (
        <>
            <Head title="Registrar evidência" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Registrar evidência"
                    description={`${linkLabel(link)}. A data de registro é definida automaticamente.`}
                />
                <Form
                    {...EvidenceController.store.form(link.id)}
                    id={FORM_ID}
                    onError={() => setConfirming(false)}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Tipo</Label>
                                <Select name="type" required>
                                    <SelectTrigger
                                        id="type"
                                        className="w-full"
                                        aria-invalid={
                                            errors.type ? true : undefined
                                        }
                                    >
                                        <SelectValue placeholder="Selecione o tipo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {labelFor(
                                                    evidenceTypeLabels,
                                                    option.value,
                                                )}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Descrição</Label>
                                <Textarea
                                    id="description"
                                    name="description"
                                    required
                                    maxLength={255}
                                    placeholder="Ex.: Relatório de auditoria de equidade do 1º trimestre, assinado pelo comitê."
                                    aria-invalid={
                                        errors.description ? true : undefined
                                    }
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="observed_cost">
                                    Custo observado{' '}
                                    <span className="text-muted-foreground font-normal">
                                        (opcional)
                                    </span>
                                </Label>
                                <Select
                                    value={observedCost}
                                    onValueChange={setObservedCost}
                                >
                                    <SelectTrigger
                                        id="observed_cost"
                                        className="w-full sm:w-64"
                                        aria-describedby="observed_cost-help"
                                        aria-invalid={
                                            errors.observed_cost
                                                ? true
                                                : undefined
                                        }
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NOT_INFORMED}>
                                            Não informado
                                        </SelectItem>
                                        {costLevels.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {labelFor(
                                                    costLevelLabels,
                                                    option.value,
                                                )}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {/* Sent as an empty string, which Laravel
                                    turns into null. */}
                                <input
                                    type="hidden"
                                    name="observed_cost"
                                    value={
                                        observedCost === NOT_INFORMED
                                            ? ''
                                            : observedCost
                                    }
                                />
                                <p
                                    id="observed_cost-help"
                                    className="text-muted-foreground text-sm"
                                >
                                    O custo real da aplicação até agora (RF07).
                                    O informado na evidência mais recente passa
                                    a ser o custo observado do vínculo.
                                </p>
                                <InputError message={errors.observed_cost} />
                            </div>

                            <Alert>
                                <LockIcon />
                                <AlertTitle>
                                    Evidências são permanentes
                                </AlertTitle>
                                <AlertDescription>
                                    Depois de registrada, a evidência não pode
                                    ser editada nem excluída, porque sustenta a
                                    verificação do vínculo. Um engano se corrige
                                    registrando outra evidência.
                                </AlertDescription>
                            </Alert>

                            <div className="flex items-center gap-4">
                                <Button
                                    type="button"
                                    disabled={processing}
                                    onClick={askToConfirm}
                                >
                                    Registrar
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={index(link.id)}>Cancelar</Link>
                                </Button>
                            </div>

                            <Dialog
                                open={confirming}
                                onOpenChange={setConfirming}
                            >
                                <DialogContent>
                                    <DialogTitle>
                                        Registrar esta evidência?
                                    </DialogTitle>
                                    <DialogDescription>
                                        Confira o tipo e a descrição: depois de
                                        registrada, a evidência não pode ser
                                        editada nem excluída.
                                    </DialogDescription>
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
            </div>
        </>
    );
}

EvidenceCreate.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(link), href: showLink(link.id) },
        { title: 'Evidências', href: index(link.id) },
        { title: 'Registrar', href: create(link.id) },
    ],
});
