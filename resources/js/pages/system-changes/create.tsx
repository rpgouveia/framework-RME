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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { dateInputValue } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { labelFor } from '@/lib/enum-labels';
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
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
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
            <Head title={t('Record system change')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Record system change')}
                    description={t(
                        ':system. A new model version or a change to the database takes the verified links to reassessment.',
                        { system: aiSystem.name },
                    )}
                />
                <Form
                    {...SystemChangeController.store.form(aiSystem.id)}
                    id={FORM_ID}
                    onError={() => setConfirming(false)}
                    className="grid max-w-3xl gap-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="type">{t('Change type')}</Label>
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
                                        <SelectValue
                                            placeholder={t('Select the type')}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {labelFor(
                                                    enumLabels.SystemChangeType,
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
                                    maxLength={2000}
                                    className="min-h-28"
                                    placeholder={t(
                                        "E.g.: Replacement of the classification model by the supplier's version 2.0.",
                                    )}
                                    aria-invalid={
                                        errors.description ? true : undefined
                                    }
                                />
                                <InputError message={errors.description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="change_date">
                                    {t('Change date')}
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
                                    {t('It cannot be a future date.')}
                                </p>
                                <InputError message={errors.change_date} />
                            </div>

                            <RiskSubdomainPicker
                                legend={t(
                                    'Risk subdomains affected (optional)',
                                )}
                                help={t(
                                    'Give the risks the change may reach: only their links will be reverted. With none, every verified link of the system is reverted.',
                                )}
                                domains={riskDomains}
                                expected={expected}
                                selected={selected}
                                onToggle={toggleSubdomain}
                                disabled={false}
                                error={subdomainError(errors)}
                            />

                            <Alert>
                                <TriangleAlertIcon aria-hidden />
                                <AlertTitle>
                                    {t('The record is permanent')}
                                </AlertTitle>
                                <AlertDescription>
                                    {t(
                                        'System changes can be neither edited nor deleted. The record takes back to declared the verified links the change reaches, awaiting reassessment.',
                                    )}
                                </AlertDescription>
                            </Alert>

                            <div className="flex items-center gap-4">
                                <Button
                                    type="button"
                                    disabled={processing}
                                    onClick={askToConfirm}
                                >
                                    {t('Record change')}
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={showAiSystem(aiSystem.id)}>
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
                                        {t('Record this system change?')}
                                    </DialogTitle>
                                    <DialogDescription>
                                        {t(
                                            'Once recorded, the change can be neither edited nor deleted.',
                                        )}
                                    </DialogDescription>
                                    <ReversalPreview
                                        reverted={reverted}
                                        unacceptable={unacceptable}
                                        withSubdomains={selected.length > 0}
                                    />
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
    const { t, tc } = useTranslations();

    if (unacceptable) {
        return (
            <p className="text-sm">
                {t(
                    'The system is in the unacceptable tier: the change is recorded, without reverting links.',
                )}
            </p>
        );
    }

    return (
        <div className="grid gap-2">
            {!withSubdomains && (
                <p className="text-sm">
                    {t(
                        'No subdomain was given: every verified link of the system will be reverted. Giving the affected subdomains limits the reversal to the risks really involved.',
                    )}
                </p>
            )}
            {reverted.length === 0 ? (
                <p className="text-sm">
                    {t('No verified link will be reverted.')}
                </p>
            ) : (
                <div className="grid gap-2 rounded-lg border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                    <p className="font-semibold">
                        {tc(
                            ':count verified link will go back to declared, awaiting reassessment:|:count verified links will go back to declared, awaiting reassessment:',
                            reverted.length,
                        )}
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

SystemChangesCreate.layout = ({
    aiSystem,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('AI systems'), href: aiSystemsIndex() },
            { title: aiSystem.name, href: showAiSystem(aiSystem.id) },
            { title: t('Record change'), href: create(aiSystem.id) },
        ],
    };
};
