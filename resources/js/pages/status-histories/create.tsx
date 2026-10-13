import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StatusHistoryController from '@/actions/App/Http/Controllers/StatusHistoryController';
import { LinkStatusBadges } from '@/components/link-status-badges';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { dateInputValue, formatDate } from '@/lib/format';
import { labelFor } from '@/lib/enum-labels';
import { linkLabel, subdomainCodes } from '@/lib/names';
import { index, show } from '@/routes/links';
import { create, index as historyIndex } from '@/routes/links/status-histories';
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
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const { url } = usePage();

    // The server sends only the moves the link's status allows, so the link
    // page shortcuts (`?new_status=cancelled`) are ignored when not among them.
    const requested = new URLSearchParams(url.split('?')[1]).get('new_status');
    const [newStatus, setNewStatus] = useState(
        statuses.some((option) => option.value === requested)
            ? (requested ?? '')
            : '',
    );
    const [adverseEventId, setAdverseEventId] = useState(NO_EVENT);

    const cancelling = newStatus === 'cancelled';

    return (
        <>
            <Head title={t('Record status change')} />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title={t('Record status change')}
                    description={linkLabel(link, t)}
                />
                <Form
                    {...StatusHistoryController.store.form(link.id)}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <span className="text-sm font-medium">
                                    {t('Current status')}
                                </span>
                                <LinkStatusBadges link={link} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="new_status">
                                    {t('New status')}
                                </Label>
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
                                        <SelectValue
                                            placeholder={t(
                                                'Select the new status',
                                            )}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {labelFor(
                                                    enumLabels.LinkStatus,
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
                                    {t('Reason')}
                                    {!cancelling && (
                                        <span className="text-muted-foreground font-normal">
                                            {' '}
                                            {t('(optional)')}
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
                                            ? t(
                                                  'Explain why the link is being cancelled.',
                                              )
                                            : t('What prompted the change.')
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
                                        {t('Recorded by')}
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
                                            <SelectValue
                                                placeholder={t(
                                                    'Select the owner',
                                                )}
                                            />
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
                                        {t('Change date')}
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
                                    {t('Related adverse event')}
                                    <span className="text-muted-foreground font-normal">
                                        {' '}
                                        {t('(optional)')}
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
                                            {t('None')}
                                        </SelectItem>
                                        {adverseEvents.map((event) => (
                                            <SelectItem
                                                key={event.id}
                                                value={String(event.id)}
                                            >
                                                {subdomainCodes(event)} ·{' '}
                                                {event.ai_system?.name} ·{' '}
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
                                        ? t('Cancel link')
                                        : t('Record change')}
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={show(link.id)}>
                                        {t('Back')}
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

StatusHistoriesCreate.layout = ({
    link,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: index() },
            { title: linkLabel(link, t), href: show(link.id) },
            { title: t('History'), href: historyIndex(link.id) },
            { title: t('Record change'), href: create(link.id) },
        ],
    };
};
