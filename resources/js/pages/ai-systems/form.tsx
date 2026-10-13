import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import { useState } from 'react';
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
 * `<Form>`: the selects get a `name`, so Radix submits them through a hidden
 * native `<select>`.
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

    return (
        <>
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
                    <span className="text-muted-foreground font-normal">
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
                    aria-invalid={errors.application_domain ? true : undefined}
                />
                <p
                    id="application_domain-help"
                    className="text-muted-foreground text-sm"
                >
                    {t(
                        'Where the system is used, to set it in context. It changes no classification.',
                    )}
                </p>
                <InputError message={errors.application_domain} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="source_type">{t('Origin')}</Label>
                <Select
                    name="source_type"
                    defaultValue={aiSystem?.source_type}
                    required
                >
                    <SelectTrigger
                        id="source_type"
                        className="w-full"
                        aria-invalid={errors.source_type ? true : undefined}
                    >
                        <SelectValue placeholder={t('Select the origin')} />
                    </SelectTrigger>
                    <SelectContent>
                        {sourceTypes.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(
                                    enumLabels.SystemSourceType,
                                    option.value,
                                )}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.source_type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="category">{t('Category')}</Label>
                <Select
                    name="category"
                    value={category}
                    onValueChange={setCategory}
                    required
                >
                    <SelectTrigger
                        id="category"
                        className="w-full"
                        aria-invalid={errors.category ? true : undefined}
                    >
                        <SelectValue
                            placeholder={t(
                                'Select the risk category (EU AI Act)',
                            )}
                        />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(
                                    enumLabels.AiSystemCategory,
                                    option.value,
                                )}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {reverting && (
                    // Warned before saving: the move reverts in the same
                    // transaction (0019, item 9).
                    <p
                        role="alert"
                        className="rounded-lg border border-red-300 bg-red-50 p-3 text-sm text-red-900 dark:border-red-900 dark:bg-red-950/40 dark:text-red-200"
                    >
                        {tc(
                            'The unacceptable tier stands for prohibited practices: on saving, :count verified link of this system will go back to declared, and the system is no longer considered in operation.|The unacceptable tier stands for prohibited practices: on saving, :count verified links of this system will go back to declared, and the system is no longer considered in operation.',
                            verifiedLinksCount,
                        )}
                    </p>
                )}
                <InputError message={errors.category} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="registration_date">
                    {t('Registration date')}
                </Label>
                <Input
                    id="registration_date"
                    name="registration_date"
                    type="date"
                    className="sm:w-38"
                    defaultValue={dateInputValue(aiSystem?.registration_date)}
                    required
                    aria-invalid={errors.registration_date ? true : undefined}
                />
                <InputError message={errors.registration_date} />
            </div>

            <div className="flex items-center gap-4">
                <Button disabled={processing}>{submitLabel}</Button>
                <Button variant="ghost" asChild>
                    <Link href={cancelHref}>{t('Cancel')}</Link>
                </Button>
            </div>
        </>
    );
}
