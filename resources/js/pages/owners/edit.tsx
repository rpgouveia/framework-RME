import { Form, Head, Link } from '@inertiajs/react';
import OwnerController from '@/actions/App/Http/Controllers/OwnerController';
import { DetailItem } from '@/components/detail-item';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { edit, index, show } from '@/routes/owners';
import type { Owner } from '@/types/models';
import { OwnerForm } from './form';

type Props = {
    owner: Owner;
};

export default function OwnersEdit({ owner }: Props) {
    const { t } = useTranslations();
    const links = owner.links_count ?? 0;
    const entries = owner.status_histories_count ?? 0;
    // The status trail keeps the owner's id, so a used owner's role and
    // area are locked: renaming it would rewrite who made each entry.
    const locked = links > 0 || entries > 0;

    return (
        <>
            <Head title={t('Edit owner')} />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title={t('Edit owner')}
                    description={`${owner.organizational_role} · ${owner.area}`}
                />
                {locked ? (
                    <Card className="max-w-xl">
                        <CardHeader>
                            <CardTitle>{t('Role and area locked')}</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-6">
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <DetailItem label={t('Position')}>
                                    {owner.organizational_role}
                                </DetailItem>
                                <DetailItem label={t('Area')}>
                                    {owner.area}
                                </DetailItem>
                                <DetailItem label={t('Links')}>
                                    {links}
                                </DetailItem>
                                <DetailItem label={t('History entries')}>
                                    {entries}
                                </DetailItem>
                            </dl>
                            <p className="text-muted-foreground text-sm">
                                {t(
                                    'The role and the area cannot be changed once the owner was used in the traceability chain. Register a new owner and reassign the active links.',
                                )}
                            </p>
                            <div>
                                <Button variant="outline" asChild>
                                    <Link href={show(owner.id)}>
                                        {t('Back to the owner')}
                                    </Link>
                                </Button>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <Form
                        {...OwnerController.update.form(owner.id)}
                        options={{ preserveScroll: true }}
                        className="max-w-xl space-y-6"
                    >
                        {({ processing, errors }) => (
                            <OwnerForm
                                errors={errors}
                                processing={processing}
                                submitLabel={t('Save')}
                                cancelHref={show(owner.id)}
                                owner={owner}
                            />
                        )}
                    </Form>
                )}
            </div>
        </>
    );
}

OwnersEdit.layout = ({ owner, translations }: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Owners'), href: index() },
            { title: owner.organizational_role, href: show(owner.id) },
            { title: t('Edit'), href: edit(owner.id) },
        ],
    };
};
