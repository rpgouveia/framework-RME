import { Form, Head, Link } from '@inertiajs/react';
import { LockIcon } from 'lucide-react';
import { useState } from 'react';
import EvidenceController from '@/actions/App/Http/Controllers/EvidenceController';
import { CostHelp } from '@/components/cost-help';
import { FormSection } from '@/components/form-section';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { labelFor } from '@/lib/enum-labels';
import { linkLabel } from '@/lib/names';
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
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
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
            <Head title={t('Register evidence')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Register evidence')}
                    description={t(
                        ':link. The registration date is set automatically.',
                        { link: linkLabel(link, t) },
                    )}
                />
                <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,1fr)_340px]">
                    <Form
                        {...EvidenceController.store.form(link.id)}
                        id={FORM_ID}
                        onError={() => setConfirming(false)}
                        className="grid min-w-0 gap-10"
                    >
                        {({ processing, errors }) => (
                            <>
                                <FormSection title={t('The evidence')}>
                                    <div className="grid gap-2">
                                        <Label htmlFor="type">
                                            {t('Type')}
                                        </Label>
                                        <Select name="type" required>
                                            <SelectTrigger
                                                id="type"
                                                className="w-full"
                                                aria-invalid={
                                                    errors.type
                                                        ? true
                                                        : undefined
                                                }
                                            >
                                                <SelectValue
                                                    placeholder={t(
                                                        'Select the type',
                                                    )}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {types.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {labelFor(
                                                            enumLabels.EvidenceType,
                                                            option.value,
                                                        )}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.type} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="description">
                                            {t('Description')}
                                        </Label>
                                        <Textarea
                                            id="description"
                                            name="description"
                                            required
                                            maxLength={255}
                                            placeholder={t(
                                                'E.g.: Fairness audit report of the 1st quarter, signed by the committee.',
                                            )}
                                            aria-invalid={
                                                errors.description
                                                    ? true
                                                    : undefined
                                            }
                                        />
                                        <InputError
                                            message={errors.description}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <span className="flex h-4 items-center gap-1">
                                            <Label htmlFor="observed_cost">
                                                {t('Observed cost')}{' '}
                                                <span className="text-muted-foreground">
                                                    {t('(optional)')}
                                                </span>
                                            </Label>
                                            <CostHelp />
                                        </span>
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
                                                <SelectItem
                                                    value={NOT_INFORMED}
                                                >
                                                    {t('Not reported')}
                                                </SelectItem>
                                                {costLevels.map((option) => (
                                                    <SelectItem
                                                        key={option.value}
                                                        value={option.value}
                                                    >
                                                        {labelFor(
                                                            enumLabels.CostLevel,
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
                                            className="text-muted-foreground text-xs tracking-[0.32px]"
                                        >
                                            {t(
                                                "The effort actually spent so far, on the same scale as the estimated cost (:cost). The one reported in the latest evidence becomes the link's observed cost.",
                                                {
                                                    cost: enumLabels.CostLevel[
                                                        link.estimated_cost
                                                    ],
                                                },
                                            )}
                                        </p>
                                        <InputError
                                            message={errors.observed_cost}
                                        />
                                    </div>
                                </FormSection>

                                <Alert>
                                    <LockIcon />
                                    <AlertTitle>
                                        {t('Evidence is permanent')}
                                    </AlertTitle>
                                    <AlertDescription>
                                        {t(
                                            "Once registered, the evidence can be neither edited nor deleted, because it supports the link's verification. A mistake is corrected by registering other evidence.",
                                        )}
                                    </AlertDescription>
                                </Alert>

                                <div className="flex items-center gap-4">
                                    <Button
                                        type="button"
                                        size="lg"
                                        disabled={processing}
                                        onClick={askToConfirm}
                                    >
                                        {t('Register evidence')}
                                    </Button>
                                    <Button variant="ghost" size="lg" asChild>
                                        <Link href={index(link.id)}>
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
                                            {t('Register this evidence?')}
                                        </DialogTitle>
                                        <DialogDescription>
                                            {t(
                                                'Check the type and the description: once registered, the evidence can be neither edited nor deleted.',
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

                    {link.mitigation && (
                        <Card>
                            <CardHeader>
                                <CardTitle>{t('Expected evidence')}</CardTitle>
                                <CardDescription>
                                    {t(
                                        'From the catalogue, for the mitigation :mitigation.',
                                        { mitigation: link.mitigation.name },
                                    )}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <p>{link.mitigation.expected_evidence}</p>
                            </CardContent>
                        </Card>
                    )}
                </div>
            </div>
        </>
    );
}

EvidenceCreate.layout = ({ link, translations }: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: linksIndex() },
            { title: linkLabel(link, t), href: showLink(link.id) },
            { title: t('Evidence'), href: index(link.id) },
            { title: t('Record'), href: create(link.id) },
        ],
    };
};
