import { Form, Head, Link } from '@inertiajs/react';
import LinkController from '@/actions/App/Http/Controllers/LinkController';
import { ObservedCost } from '@/components/observed-cost';
import { LinkStatusBadges } from '@/components/link-status-badges';
import { ReviewDate } from '@/components/review-date';
import { DetailItem } from '@/components/detail-item';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { formatDate } from '@/lib/format';
import {
    costLevelLabels,
    labelFor,
    lifecyclePhaseLabels,
    linkLabel,
} from '@/lib/labels';
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
    return (
        <>
            <Head title="Editar vínculo" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <Heading title="Editar vínculo" description={linkLabel(link)} />

                {/* The pair is the link's identity, the status changes only
                    through the history (RF09), the verification and review
                    date through the verification (0018), and the observed
                    cost comes with the evidence: all read only. */}
                <Card className="max-w-xl">
                    <CardHeader>
                        <CardTitle>Identificação</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2">
                            <DetailItem label="Risco">
                                {link.risk?.name}
                            </DetailItem>
                            <DetailItem label="Mitigação">
                                {link.mitigation?.name}
                            </DetailItem>
                            <DetailItem label="Status">
                                <span className="flex flex-wrap items-center gap-2">
                                    <LinkStatusBadges link={link} />
                                    <Link
                                        href={statusHistoriesIndex(link.id)}
                                        className="text-muted-foreground text-sm font-normal hover:underline"
                                    >
                                        Alterar pelo histórico
                                    </Link>
                                </span>
                            </DetailItem>
                            <DetailItem label="Data de criação">
                                {formatDate(link.creation_date)}
                            </DetailItem>
                            <DetailItem label="Custo observado">
                                {/* Reported with the evidence (0018). */}
                                <ObservedCost
                                    evidence={link.observed_cost_evidence}
                                />
                            </DetailItem>
                            <DetailItem label="Próxima revisão">
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
                    </CardContent>
                </Card>

                <Form
                    {...LinkController.update.form(link.id)}
                    options={{ preserveScroll: true }}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="owner_id">Responsável</Label>
                                <Select
                                    name="owner_id"
                                    defaultValue={String(link.owner_id)}
                                    required
                                >
                                    <SelectTrigger
                                        id="owner_id"
                                        className="w-full"
                                        aria-invalid={
                                            errors.owner_id ? true : undefined
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
                                                {owner.organizational_role} (
                                                {owner.area})
                                                {/* Only the current owner can
                                                    be inactive here: kept, not
                                                    offered to other links. */}
                                                {owner.deactivated_at !==
                                                    null && ' (inativo)'}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.owner_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="lifecycle_phase">
                                    Fase do ciclo de vida
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
                                        <SelectValue placeholder="Selecione a fase" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {lifecyclePhases.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {labelFor(
                                                    lifecyclePhaseLabels,
                                                    option.value,
                                                )}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.lifecycle_phase} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="estimated_cost">
                                        Custo estimado
                                    </Label>
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
                                            <SelectValue placeholder="Selecione o custo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {costLevels.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {labelFor(
                                                        costLevelLabels,
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

                            <div className="flex items-center gap-4">
                                <Button disabled={processing}>Salvar</Button>
                                <Button variant="ghost" asChild>
                                    <Link href={show(link.id)}>Cancelar</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

LinksEdit.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: index() },
        { title: linkLabel(link), href: show(link.id) },
        { title: 'Editar', href: edit(link.id) },
    ],
});
