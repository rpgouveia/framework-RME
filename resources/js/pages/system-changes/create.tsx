import { Form, Head, Link } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useState } from 'react';
import SystemChangeController from '@/actions/App/Http/Controllers/SystemChangeController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import {
    RiskSubdomainPicker,
    subdomainError,
} from '@/components/risk-subdomain-picker';
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
import { dateInputValue } from '@/lib/format';
import { labelFor, systemChangeTypeLabels } from '@/lib/labels';
import type { ExpectedSubdomain } from '@/lib/risk-profile';
import { systemChangeReassessment } from '@/lib/system-change-reassessment';
import type { VerifiedLink } from '@/lib/system-change-reassessment';
import {
    index as aiSystemsIndex,
    show as showAiSystem,
} from '@/routes/ai-systems';
import { create } from '@/routes/ai-systems/system-changes';
import type { AiSystem, EnumOption, RiskDomainOption } from '@/types/models';

type Props = {
    aiSystem: AiSystem;
    types: EnumOption[];
    /** Every MIT subdomain, grouped by domain. */
    riskDomains: RiskDomainOption[];
    /** The system's risk profile, shown first. */
    expected: ExpectedSubdomain[];
    /** The verified links the change may revert. */
    verifiedLinks: VerifiedLink[];
};

/** Lets the confirm button, rendered in a dialog outside the form, submit it. */
const FORM_ID = 'system-change-form';

export default function SystemChangesCreate({
    aiSystem,
    types,
    riskDomains,
    expected,
    verifiedLinks,
}: Props) {
    const [type, setType] = useState('');
    const [selected, setSelected] = useState<string[]>([]);
    const [confirming, setConfirming] = useState(false);
    const unacceptable = aiSystem.category === 'unacceptable';
    // What the change will revert (0021); the server works it out again.
    const reverted = unacceptable
        ? []
        : systemChangeReassessment(verifiedLinks, selected);

    function toggleSubdomain(code: string, checked: boolean) {
        setSelected((codes) =>
            checked
                ? [...codes, code]
                : codes.filter((selectedCode) => selectedCode !== code),
        );
    }

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
            <Head title="Registrar mudança do sistema" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Registrar mudança do sistema"
                    description={`${aiSystem.name}. Uma nova versão do modelo ou uma alteração na base de dados leva os vínculos verificados à reavaliação.`}
                />
                <Form
                    {...SystemChangeController.store.form(aiSystem.id)}
                    id={FORM_ID}
                    onError={() => setConfirming(false)}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="type">Tipo de mudança</Label>
                                <Select
                                    name="type"
                                    value={type}
                                    onValueChange={setType}
                                    required
                                >
                                    <SelectTrigger
                                        id="type"
                                        className="w-full sm:w-72"
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
                                                    systemChangeTypeLabels,
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
                                    maxLength={2000}
                                    className="min-h-28"
                                    placeholder="Ex.: Troca do modelo de classificação pela versão 2.0 do fornecedor."
                                    aria-invalid={
                                        errors.description ? true : undefined
                                    }
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="change_date">
                                    Data da mudança
                                </Label>
                                <Input
                                    id="change_date"
                                    name="change_date"
                                    type="date"
                                    className="sm:w-48"
                                    max={dateInputValue()}
                                    required
                                    aria-invalid={
                                        errors.change_date ? true : undefined
                                    }
                                />
                                <p className="text-muted-foreground text-sm">
                                    Não pode ser uma data futura.
                                </p>
                                <InputError message={errors.change_date} />
                            </div>

                            <RiskSubdomainPicker
                                legend="Subdomínios de risco afetados (opcional)"
                                help="Informe os riscos que a mudança pode alcançar: só os vínculos deles serão revertidos. Sem nenhum, todos os vínculos verificados do sistema são revertidos."
                                domains={riskDomains}
                                expected={expected}
                                selected={selected}
                                onToggle={toggleSubdomain}
                                disabled={false}
                                error={subdomainError(errors)}
                            />

                            <Alert>
                                <TriangleAlertIcon aria-hidden />
                                <AlertTitle>O registro é permanente</AlertTitle>
                                <AlertDescription>
                                    Mudanças do sistema não podem ser editadas
                                    nem excluídas. O registro devolve a
                                    declarados os vínculos verificados que a
                                    mudança alcança, aguardando reavaliação.
                                </AlertDescription>
                            </Alert>

                            <div className="flex items-center gap-4">
                                <Button
                                    type="button"
                                    disabled={processing}
                                    onClick={askToConfirm}
                                >
                                    Registrar mudança
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={showAiSystem(aiSystem.id)}>
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>

                            <Dialog
                                open={confirming}
                                onOpenChange={setConfirming}
                            >
                                <DialogContent>
                                    <DialogTitle>
                                        Registrar esta mudança do sistema?
                                    </DialogTitle>
                                    <DialogDescription>
                                        Depois de registrada, a mudança não pode
                                        ser editada nem excluída.
                                    </DialogDescription>
                                    <ReversalPreview
                                        reverted={reverted}
                                        unacceptable={unacceptable}
                                        withSubdomains={selected.length > 0}
                                    />
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

/**
 * How many verified links the change will revert (0021, item 3), and, with
 * no subdomain given, that informing them would limit the reversal.
 */
function ReversalPreview({
    reverted,
    unacceptable,
    withSubdomains,
}: {
    reverted: VerifiedLink[];
    unacceptable: boolean;
    withSubdomains: boolean;
}) {
    if (unacceptable) {
        return (
            <p className="text-sm">
                O sistema está na faixa inaceitável: a mudança fica registrada,
                sem reverter vínculos.
            </p>
        );
    }

    return (
        <div className="grid gap-2">
            {!withSubdomains && (
                <p className="text-sm">
                    Nenhum subdomínio foi informado: todos os vínculos
                    verificados do sistema serão revertidos. Informar os
                    subdomínios afetados limita a reversão aos riscos realmente
                    envolvidos.
                </p>
            )}
            {reverted.length === 0 ? (
                <p className="text-sm">
                    Nenhum vínculo verificado será revertido.
                </p>
            ) : (
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
                                {link.risk} → {link.mitigation} (
                                {link.subdomain})
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}

SystemChangesCreate.layout = ({ aiSystem }: Props) => ({
    breadcrumbs: [
        { title: 'Sistemas de IA', href: aiSystemsIndex() },
        { title: aiSystem.name, href: showAiSystem(aiSystem.id) },
        { title: 'Registrar mudança', href: create(aiSystem.id) },
    ],
});
