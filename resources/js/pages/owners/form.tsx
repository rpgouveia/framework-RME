import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import type { Owner } from '@/types/models';

type Props = {
    errors: Partial<Record<string, string>>;
    processing: boolean;
    submitLabel: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    owner?: Owner;
};

/**
 * Fields shared by the create and edit screens. An owner is a role in an
 * area, never a person's name (R-2).
 */
export function OwnerForm({
    errors,
    processing,
    submitLabel,
    cancelHref,
    owner,
}: Props) {
    const { t } = useTranslations();

    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="organizational_role">{t('Position')}</Label>
                <Input
                    id="organizational_role"
                    name="organizational_role"
                    defaultValue={owner?.organizational_role}
                    required
                    maxLength={255}
                    placeholder={t('E.g.: AI Risk Manager')}
                    aria-invalid={errors.organizational_role ? true : undefined}
                />
                <p className="text-muted-foreground text-sm">
                    {t("An organizational role, never a person's name.")}
                </p>
                <InputError message={errors.organizational_role} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="area">{t('Area')}</Label>
                <Input
                    id="area"
                    name="area"
                    defaultValue={owner?.area}
                    required
                    maxLength={255}
                    placeholder={t('E.g.: Information Security')}
                    aria-invalid={errors.area ? true : undefined}
                />
                <InputError message={errors.area} />
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
