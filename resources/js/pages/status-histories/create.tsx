import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StatusHistoryController from '@/actions/App/Http/Controllers/StatusHistoryController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { dateInputValue, formatDate } from '@/lib/format';
import {
    adverseEventTypeLabels,
    labelFor,
    linkLabel,
    linkStatusBadgeClasses,
    linkStatusLabels,
} from '@/lib/labels';
import { index, show } from '@/routes/links';
import { create } from '@/routes/links/status-histories';
import type {
    AdverseEvent,
    EnumOption,
    Link as RiskLink,
    Owner,
} from '@/types/models';

type Props = {
    link: RiskLink;
    owners: Owner[];
    statuses: EnumOption[];
    adverseEvents: AdverseEvent[];
};

/** Radix Select items cannot be empty, so "no event" needs a stand-in. */
const NO_EVENT = 'none';

export default function StatusHistoriesCreate({
    link,
    owners,
    statuses,
    adverseEvents,
}: Props) {
    const { url } = usePage();

    // The link page offers shortcuts such as `?new_status=cancelled`; a value
    // that is not a status, or that is the current one, is ignored.
    const choices = statuses.filter((option) => option.value !== link.status);
    const requested = new URLSearchParams(url.split('?')[1]).get('new_status');
    const [newStatus, setNewStatus] = useState(
        choices.some((option) => option.value === requested)
            ? (requested ?? '')
            : '',
    );
    const [adverseEventId, setAdverseEventId] = useState(NO_EVENT);

    const cancelling = newStatus === 'cancelled';

    return (
        <>
            <Head title="Registrar mudança de status" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Registrar mudança de status"
                    description={linkLabel(link)}
                />
                <Form
                    {...StatusHistoryController.store.form(link.id)}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            {/* The change starts from wherever the link is now. */}
                            <input
                                type="hidden"
                                name="previous_status"
                                value={link.status}
                            />
                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    Status atual
                                </span>
                                <Badge
                                    className={
                                        linkStatusBadgeClasses[link.status]
                                    }
                                >
                                    {linkStatusLabels[link.status]}
                                </Badge>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="new_status">Novo status</Label>
                                <Select
                                    name="new_status"
                                    defaultValue={newStatus || undefined}
                                    onValueChange={setNewStatus}
                                    required
                                >
                                    <SelectTrigger
                                        id="new_status"
                                        className="w-full"
                                        aria-invalid={
                                            errors.new_status ? true : undefined
                                        }
                                    >
                                        <SelectValue placeholder="Selecione o novo status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {choices.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {labelFor(
                                                    linkStatusLabels,
                                                    option.value,
                                                )}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.new_status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="trigger_reason">
                                    Motivo
                                    {!cancelling && (
                                        <span className="text-muted-foreground font-normal">
                                            {' '}
                                            (opcional)
                                        </span>
                                    )}
                                </Label>
                                <Textarea
                                    id="trigger_reason"
                                    name="trigger_reason"
                                    required={cancelling}
                                    maxLength={255}
                                    placeholder={
                                        cancelling
                                            ? 'Explique por que o vínculo está sendo cancelado.'
                                            : 'O que motivou a mudança.'
                                    }
                                    aria-invalid={
                                        errors.trigger_reason ? true : undefined
                                    }
                                />
                                <InputError message={errors.trigger_reason} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="owner_id">
                                        Registrado por
                                    </Label>
                                    <Select
                                        name="owner_id"
                                        defaultValue={String(link.owner_id)}
                                        required
                                    >
                                        <SelectTrigger
                                            id="owner_id"
                                            className="w-full"
                                            aria-invalid={
                                                errors.owner_id
                                                    ? true
                                                    : undefined
                                            }
                                        >
                                            <SelectValue placeholder="Selecione o responsável" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {owners.map((owner) => (
                                                <SelectItem
                                                    key={owner.id}
                                                    value={String(owner.id)}
                                                >
                                                    {owner.organizational_role}{' '}
                                                    ({owner.area})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.owner_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="change_date">
                                        Data da mudança
                                    </Label>
                                    <Input
                                        id="change_date"
                                        name="change_date"
                                        type="date"
                                        defaultValue={dateInputValue()}
                                        required
                                        aria-invalid={
                                            errors.change_date
                                                ? true
                                                : undefined
                                        }
                                    />
                                    <InputError message={errors.change_date} />
                                </div>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="adverse_event_id">
                                    Evento adverso relacionado
                                    <span className="text-muted-foreground font-normal">
                                        {' '}
                                        (opcional)
                                    </span>
                                </Label>
                                <Select
                                    value={adverseEventId}
                                    onValueChange={setAdverseEventId}
                                >
                                    <SelectTrigger
                                        id="adverse_event_id"
                                        className="w-full"
                                        aria-invalid={
                                            errors.adverse_event_id
                                                ? true
                                                : undefined
                                        }
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NO_EVENT}>
                                            Nenhum
                                        </SelectItem>
                                        {adverseEvents.map((event) => (
                                            <SelectItem
                                                key={event.id}
                                                value={String(event.id)}
                                            >
                                                {
                                                    adverseEventTypeLabels[
                                                        event.event_type
                                                    ]
                                                }{' '}
                                                · {event.ai_system?.name} ·{' '}
                                                {formatDate(
                                                    event.occurrence_date,
                                                )}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {/* Sent as an empty string, which Laravel
                                    turns into null. */}
                                <input
                                    type="hidden"
                                    name="adverse_event_id"
                                    value={
                                        adverseEventId === NO_EVENT
                                            ? ''
                                            : adverseEventId
                                    }
                                />
                                <InputError message={errors.adverse_event_id} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    variant={
                                        cancelling ? 'destructive' : 'default'
                                    }
                                >
                                    {cancelling
                                        ? 'Cancelar vínculo'
                                        : 'Registrar mudança'}
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={show(link.id)}>Voltar</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

StatusHistoriesCreate.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: index() },
        { title: linkLabel(link), href: show(link.id) },
        { title: 'Registrar mudança de status', href: create(link.id) },
    ],
});
