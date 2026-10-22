import { Form, Head, Link } from '@inertiajs/react';
import { InfoIcon } from 'lucide-react';
import LinkController from '@/actions/App/Http/Controllers/LinkController';
import { ObservedCost } from '@/components/observed-cost';
import {
    ProgressBadge,
    VerificationBadge,
} from '@/components/link-status-badges';
import { ReviewDate } from '@/components/review-date';
import { CostHelp } from '@/components/cost-help';
import { DetailItem } from '@/components/detail-item';
import { FormSection } from '@/components/form-section';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { labelFor } from '@/lib/enum-labels';
import { linkLabel } from '@/lib/names';
import { edit, index, show } from '@/routes/links';
import { index as statusHistoriesIndex } from '@/routes/links/status-histories';
import type { EnumOption, Link as RiskLink, Owner } from '@/types/models';

type Props = {
    link: RiskLink;
    owners: Owner[];
    lifecyclePhases: EnumOption[];
    costLevels: EnumOption[];
};

export default function LinksEdit({
    link,
    owners,
    lifecyclePhases,
    costLevels,
}: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Edit link')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Edit link')}
                    description={linkLabel(link, t)}
                />

                <Alert className="max-w-3xl">
                    <InfoIcon aria-hidden />
                    <AlertTitle>{t('What you edit here')}</AlertTitle>
                    <AlertDescription>
                        {t(
                            'Owner, phase and estimated cost. Risk and mitigation are fixed once the link is created; progress and verification change through the status history, so they stay recorded.',
                        )}
                    </AlertDescription>
                </Alert>

                {/* The pair is the link's identity, the status changes only
                    through the history (RF09), the verification and review
                    date through the verification (0018), and the observed
                    cost comes with the evidence: all read only. */}
                <Card className="max-w-3xl">
                    <CardHeader>
                        <CardTitle>{t('Identification')}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-6 sm:grid-cols-3">
                            <DetailItem label={t('Risk')}>
                                {link.risk?.name}
                            </DetailItem>
                            <DetailItem label={t('Mitigation')}>
                                {link.mitigation?.name}
                            </DetailItem>
                            <DetailItem label={t('Progress')}>
                                <ProgressBadge status={link.status} />
                            </DetailItem>
                            <DetailItem label={t('Verification')}>
                                <VerificationBadge
                                    verification={link.verification_status}
                                />
                            </DetailItem>
                            <DetailItem label={t('Creation date')}>
                                {formatDate(link.creation_date)}
                            </DetailItem>
                            <DetailItem label={t('Observed cost')}>
                                {/* Reported with the evidence (0018). */}
                                <ObservedCost
                                    evidence={link.observed_cost_evidence}
                                />
                            </DetailItem>
                            <DetailItem label={t('Next review')}>
                                <ReviewDate
                                    date={link.next_review_date}
                                    verification={link.verification_status}
                                    unacceptable={
                                        link.risk?.ai_system?.category ===
                                        'unacceptable'
                                    }
                                />
                            </DetailItem>
                        </dl>
                        <Link
                            href={statusHistoriesIndex(link.id)}
                            className="text-link hover:text-link-hover mt-6 inline-block text-sm hover:underline"
                        >
                            {t('Change through the history')}
                        </Link>
                    </CardContent>
                </Card>

                <Form
                    {...LinkController.update.form(link.id)}
                    options={{ preserveScroll: true }}
                    className="grid max-w-3xl gap-8"
                >
                    {({ processing, errors }) => (
                        <>
                            <FormSection title={t('Responsibility')}>
                                <div className="grid gap-6 md:grid-cols-3">
                                    <div className="grid min-w-0 content-start gap-2">
                                        <Label htmlFor="owner_id">
                                            {t('Owner')}
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
                                                        {
                                                            owner.organizational_role
                                                        }{' '}
                                                        ({owner.area})
                                                        {/* Only the current owner can
                                                    be inactive here: kept, not
                                                    offered to other links. */}
                                                        {owner.deactivated_at !==
                                                            null &&
                                                            ` (${t('inactive')})`}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <InputError message={errors.owner_id} />
                                    </div>

                                    <div className="grid min-w-0 content-start gap-2">
                                        <Label htmlFor="lifecycle_phase">
                                            {t('Lifecycle phase')}
                                        </Label>
                                        <Select
                                            name="lifecycle_phase"
                                            defaultValue={link.lifecycle_phase}
                                            required
                                        >
                                            <SelectTrigger
                                                id="lifecycle_phase"
                                                className="w-full"
                                                aria-invalid={
                                                    errors.lifecycle_phase
                                                        ? true
                                                        : undefined
                                                }
                                            >
                                                <SelectValue
                                                    placeholder={t(
                                                        'Select the phase',
                                                    )}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {lifecyclePhases.map(
                                                    (option) => (
                                                        <SelectItem
                                                            key={option.value}
                                                            value={option.value}
                                                        >
                                                            {labelFor(
                                                                enumLabels.LifecyclePhase,
                                                                option.value,
                                                            )}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <InputError
                                            message={errors.lifecycle_phase}
                                        />
                                    </div>

                                    <div className="grid min-w-0 content-start gap-2">
                                        <span className="flex h-4 items-center gap-1">
                                            <Label htmlFor="estimated_cost">
                                                {t('Estimated cost')}
                                            </Label>
                                            <CostHelp />
                                        </span>
                                        <Select
                                            name="estimated_cost"
                                            defaultValue={link.estimated_cost}
                                            required
                                        >
                                            <SelectTrigger
                                                id="estimated_cost"
                                                className="w-full"
                                                aria-invalid={
                                                    errors.estimated_cost
                                                        ? true
                                                        : undefined
                                                }
                                            >
                                                <SelectValue
                                                    placeholder={t(
                                                        'Select the cost',
                                                    )}
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
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
                                        <InputError
                                            message={errors.estimated_cost}
                                        />
                                    </div>
                                </div>
                            </FormSection>

                            <div className="flex items-center gap-4">
                                <Button size="lg" disabled={processing}>
                                    {t('Save changes')}
                                </Button>
                                <Button variant="ghost" size="lg" asChild>
                                    <Link href={show(link.id)}>
                                        {t('Cancel')}
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

LinksEdit.layout = ({ link, translations }: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: index() },
            { title: linkLabel(link, t), href: show(link.id) },
            { title: t('Edit'), href: edit(link.id) },
        ],
    };
};
