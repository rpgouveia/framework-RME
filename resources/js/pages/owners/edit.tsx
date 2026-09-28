import { Form, Head, Link } from '@inertiajs/react';
import OwnerController from '@/actions/App/Http/Controllers/OwnerController';
import { DetailItem } from '@/components/detail-item';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { edit, index, show } from '@/routes/owners';
import type { Owner } from '@/types/models';
import { OwnerForm } from './form';

type Props = {
    owner: Owner;
};

export default function OwnersEdit({ owner }: Props) {
    const links = owner.links_count ?? 0;
    const entries = owner.status_histories_count ?? 0;
    // The status trail keeps the owner's id, so a used owner's role and
    // area are locked: renaming it would rewrite who made each entry.
    const locked = links > 0 || entries > 0;

    return (
        <>
            <Head title="Editar responsável" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Editar responsável"
                    description={`${owner.organizational_role} · ${owner.area}`}
                />
                {locked ? (
                    <Card className="max-w-xl">
                        <CardHeader>
                            <CardTitle>Cargo e área travados</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-6">
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <DetailItem label="Cargo">
                                    {owner.organizational_role}
                                </DetailItem>
                                <DetailItem label="Área">
                                    {owner.area}
                                </DetailItem>
                                <DetailItem label="Vínculos">
                                    {links}
                                </DetailItem>
                                <DetailItem label="Registros no histórico">
                                    {entries}
                                </DetailItem>
                            </dl>
                            <p className="text-muted-foreground text-sm">
                                O cargo e a área não podem ser alterados depois
                                que o responsável foi usado na cadeia de
                                rastreabilidade. Cadastre um novo responsável e
                                reatribua os vínculos ativos.
                            </p>
                            <div>
                                <Button variant="outline" asChild>
                                    <Link href={show(owner.id)}>
                                        Voltar ao responsável
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
                                submitLabel="Salvar"
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

OwnersEdit.layout = ({ owner }: Props) => ({
    breadcrumbs: [
        { title: 'Responsáveis', href: index() },
        { title: owner.organizational_role, href: show(owner.id) },
        { title: 'Editar', href: edit(owner.id) },
    ],
});
