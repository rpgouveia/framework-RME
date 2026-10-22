import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { TriangleAlertIcon } from 'lucide-react';
import { useState } from 'react';
import { FormSection } from '@/components/form-section';
import InputError from '@/components/input-error';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioTile } from '@/components/ui/radio-group';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { dateInputValue } from '@/lib/format';
import { labelFor } from '@/lib/enum-labels';
import type { AiSystem, EnumOption } from '@/types/models';

type Props = {
    sourceTypes: EnumOption[];
    categories: EnumOption[];
    errors: Partial<Record<string, string>>;
    processing: boolean;
    submitLabel: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    aiSystem?: AiSystem;
    /**
     * On edit, how many verified links a move into the unacceptable tier
     * would revert (0019, item 9).
     */
    verifiedLinksCount?: number;
};

/**
 * Fields shared by the create and edit screens. Render it inside an Inertia
 * `<Form>`: the radio groups get a `name`, so Radix submits them through
 * hidden native inputs.
 */
export function AiSystemForm({
    sourceTypes,
    categories,
    errors,
    processing,
    submitLabel,
    cancelHref,
    aiSystem,
    verifiedLinksCount = 0,
}: Props) {
    const enumLabels = useEnumLabels();
    const { t, tc } = useTranslations();
    const [category, setCategory] = useState(aiSystem?.category ?? '');
    const reverting =
        category === 'unacceptable' &&
        aiSystem?.category !== 'unacceptable' &&
        verifiedLinksCount > 0;

    const sourceDescriptions: Record<string, string> = {
        internal: t(
            'The organization is the producer: it designs, develops, tests and deploys the system.',
        ),
        third_party: t(
            'The organization is the client of a provider, who supplies the system as a product or service.',
        ),
        open_source: t(
            'The organization incorporates or adapts an open source model or software.',
        ),
        hybrid: t(
            'The organization combines its own component with a third party or open source one.',
        ),
    };
    const tierDescriptions: Record<string, string> = {
        unacceptable: t(
            'Prohibited practice (art. 5). The system is never considered in operation: its links are not verified and serve to plan the discontinuation.',
        ),
        high: t('High-risk system (art. 6 and Annex III).'),
        limited: t('Subject to transparency obligations (art. 50).'),
        minimal: t('Other systems.'),
    };

    return (
        <>
            <FormSection title={t('Identification')}>
                <div className="grid gap-2">
                    <Label htmlFor="name">{t('Name')}</Label>
                    <Input
                        id="name"
                        name="name"
                        defaultValue={aiSystem?.name}
                        required
                        maxLength={255}
                        placeholder={t('E.g.: Credit scoring model')}
                        aria-invalid={errors.name ? true : undefined}
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="application_domain">
                        {t('Application domain')}{' '}
                        <span className="text-muted-foreground">
                            {t('(optional)')}
                        </span>
                    </Label>
                    <Input
                        id="application_domain"
                        name="application_domain"
                        defaultValue={aiSystem?.application_domain ?? ''}
                        maxLength={255}
                        placeholder={t('E.g.: Customer service')}
                        aria-describedby="application_domain-help"
                        aria-invalid={
                            errors.application_domain ? true : undefined
                        }
                    />
                    <p
                        id="application_domain-help"
                        className="text-muted-foreground text-xs tracking-[0.32px]"
                    >
                        {t(
                            'Where the system is used, to set it in context. It changes no classification.',
                        )}
                    </p>
                    <InputError message={errors.application_domain} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="registration_date">
                        {t('Registration date')}
                    </Label>
                    <Input
                        id="registration_date"
                        name="registration_date"
                        type="date"
                        className="sm:w-48"
                        defaultValue={dateInputValue(
                            aiSystem?.registration_date,
                        )}
                        required
                        aria-invalid={
                            errors.registration_date ? true : undefined
                        }
                    />
                    <InputError message={errors.registration_date} />
                </div>
            </FormSection>

            <FormSection
                title={t('Origin of the system')}
                description={t(
                    "The organization's relation to the system, which shows how much control it has over it.",
                )}
            >
                <RadioGroup
                    name="source_type"
                    defaultValue={aiSystem?.source_type}
                    required
                    aria-label={t('Origin')}
                    className="gap-2 sm:grid-cols-2"
                >
                    {sourceTypes.map((option) => (
                        <RadioTile
                            key={option.value}
                            value={option.value}
                            title={labelFor(
                                enumLabels.SystemSourceType,
                                option.value,
                            )}
                            description={sourceDescriptions[option.value]}
                        />
                    ))}
                </RadioGroup>
                <InputError message={errors.source_type} />
            </FormSection>

            <FormSection
                title={t('Risk tier (EU AI Act)')}
                description={t(
                    'The tier sets how often the verified links are reviewed.',
                )}
            >
                <RadioGroup
                    name="category"
                    value={category}
                    onValueChange={setCategory}
                    required
                    aria-label={t('Risk tier')}
                    className="gap-2"
                >
                    {categories.map((option) => (
                        <RadioTile
                            key={option.value}
                            value={option.value}
                            title={labelFor(
                                enumLabels.AiSystemCategory,
                                option.value,
                            )}
                            description={tierDescriptions[option.value]}
                        />
                    ))}
                </RadioGroup>
                {reverting && (
                    // Warned before saving: the move reverts in the same
                    // transaction (0019, item 9).
                    <Alert variant="destructive" role="alert">
                        <TriangleAlertIcon aria-hidden />
                        <AlertDescription>
                            {tc(
                                'The unacceptable tier stands for prohibited practices: on saving, :count verified link of this system will go back to declared, and the system is no longer considered in operation.|The unacceptable tier stands for prohibited practices: on saving, :count verified links of this system will go back to declared, and the system is no longer considered in operation.',
                                verifiedLinksCount,
                            )}
                        </AlertDescription>
                    </Alert>
                )}
                <InputError message={errors.category} />
            </FormSection>

            <div className="flex items-center gap-4">
                <Button size="lg" disabled={processing}>
                    {submitLabel}
                </Button>
                <Button variant="ghost" size="lg" asChild>
                    <Link href={cancelHref}>{t('Cancel')}</Link>
                </Button>
            </div>
        </>
    );
}
