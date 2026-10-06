import { Form, Head, Link, usePage } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useState } from 'react';
import AdverseEventController from '@/actions/App/Http/Controllers/AdverseEventController';
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
import { dateInputValue } from '@/lib/format';
import { adverseEventTypeLabels, labelFor } from '@/lib/labels';
import { create, index } from '@/routes/adverse-events';
import type { AiSystem, EnumOption } from '@/types/models';

type Props = {
    aiSystems: Pick<AiSystem, 'id' | 'name'>[];
    /** RF04: the types each system accepts, keyed by system id. */
    eventTypesBySystem: Record<string, EnumOption[]>;
};

/** Lets the confirm button, rendered in a dialog outside the form, submit it. */
const FORM_ID = 'adverse-event-form';

export default function AdverseEventsCreate({
    aiSystems,
    eventTypesBySystem,
}: Props) {
    const { url } = usePage();

    // The AI system page links here with `?ai_system=<id>`.
    const requested = new URLSearchParams(url.split('?')[1]).get('ai_system');
    const [aiSystemId, setAiSystemId] = useState(
        aiSystems.some((system) => String(system.id) === requested)
            ? (requested ?? '')
            : '',
    );
    const [eventType, setEventType] = useState('');
    const [confirming, setConfirming] = useState(false);

    const eventTypes = aiSystemId ? (eventTypesBySystem[aiSystemId] ?? []) : [];

    function chooseSystem(value: string) {
        setAiSystemId(value);

        // Another system may not accept the type already chosen.
        if (
            !(eventTypesBySystem[value] ?? []).some(
                (t) => t.value === eventType,
            )
        ) {
            setEventType('');
        }
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
                                        onValueChange={chooseSystem}
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
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.ai_system_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="event_type">
                                        Tipo de evento
                                    </Label>
                                    <Select
                                        name="event_type"
                                        value={eventType}
                                        onValueChange={setEventType}
                                        disabled={!aiSystemId}
                                        required
                                    >
                                        <SelectTrigger
                                            id="event_type"
                                            className="w-full"
                                            aria-invalid={
                                                errors.event_type
                                                    ? true
                                                    : undefined
                                            }
                                        >
                                            <SelectValue
                                                placeholder={
                                                    aiSystemId
                                                        ? 'Selecione o tipo'
                                                        : 'Escolha o sistema primeiro'
                                                }
                                            />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {eventTypes.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {labelFor(
                                                        adverseEventTypeLabels,
                                                        option.value,
                                                    )}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <p className="text-muted-foreground text-sm">
                                        Os tipos disponíveis dependem do sistema
                                        escolhido, conforme o protocolo de
                                        monitoramento.
                                    </p>
                                    <InputError message={errors.event_type} />
                                </div>

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

                                <Alert>
                                    <TriangleAlertIcon />
                                    <AlertTitle>
                                        O registro é permanente
                                    </AlertTitle>
                                    <AlertDescription>
                                        Eventos adversos não podem ser editados
                                        nem excluídos. Quando a reavaliação por
                                        gatilho estiver ativa, o registro levará
                                        os vínculos verificados deste sistema a
                                        revisão.
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
                                            Confira o sistema, o tipo e a data:
                                            depois de registrado, o evento não
                                            pode ser editado nem excluído.
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
                )}
            </div>
        </>
    );
}

AdverseEventsCreate.layout = {
    breadcrumbs: [
        { title: 'Eventos adversos', href: index() },
        { title: 'Registrar', href: create() },
    ],
};
