import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import LinkController from '@/actions/App/Http/Controllers/LinkController';
import { DetailItem } from '@/components/detail-item';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { dateFromToday } from '@/lib/format';
import { availableMitigations } from '@/lib/link-options';
import {
    costLevelLabels,
    labelFor,
    lifecyclePhaseLabels,
    saeriCategoryLabels,
    uncertaintyBadgeClasses,
    uncertaintyLevelLabels,
} from '@/lib/labels';
import { index as mitigationsIndex } from '@/routes/mitigations';
import { create as createOwner } from '@/routes/owners';
import { create as createRisk } from '@/routes/risks';
import { create, index } from '@/routes/links';
import type {
    AiSystem,
    EnumOption,
    Mitigation,
    Owner,
    Risk,
    SaeriCategory,
} from '@/types/models';

type RiskOption = Pick<Risk, 'id' | 'name'> & {
    ai_system: Pick<AiSystem, 'id' | 'name'>;
    /** Mitigations this risk is already linked to (R-6). */
    linked_mitigation_ids: number[];
};

type MitigationOption = Pick<
    Mitigation,
    | 'id'
    | 'name'
    | 'saeri_category'
    | 'suggested_cost'
    | 'uncertainty_level'
    | 'bibliography_source'
>;

type Props = {
    risks: RiskOption[];
    mitigations: MitigationOption[];
    owners: Pick<Owner, 'id' | 'organizational_role' | 'area'>[];
    saeriCategories: EnumOption[];
    lifecyclePhases: EnumOption[];
    costLevels: EnumOption[];
    reviewIntervalDays: number;
};

type CategoryFilter = SaeriCategory | 'all';

export default function LinksCreate(props: Props) {
    const { risks, mitigations, owners } = props;

    const missing = [
        risks.length === 0 && {
            label: 'um risco',
            href: createRisk(),
            action: 'Cadastrar risco',
        },
        // The catalogue is loaded from its data file (R-8), not registered
        // here, so the way out is to see it.
        mitigations.length === 0 && {
            label: 'uma mitigação no catálogo',
            href: mitigationsIndex(),
            action: 'Ver o catálogo de mitigações',
        },
        owners.length === 0 && {
            label: 'um responsável',
            href: createOwner(),
            action: 'Cadastrar responsável',
        },
    ].filter((item) => item !== false);

    return (
        <>
            <Head title="Criar vínculo" />
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title="Criar vínculo"
                    description="Associe uma mitigação do catálogo a um risco já cadastrado."
                />
                {missing.length > 0 ? (
                    // A form that cannot be completed helps no one: point to
                    // what has to exist first.
                    <div className="flex max-w-xl flex-col items-start gap-4 rounded-xl border border-dashed p-6">
                        <p className="text-muted-foreground text-sm">
                            Para criar um vínculo é preciso ter{' '}
                            {missing.map((item) => item.label).join(', ')}.
                        </p>
                        <div className="flex flex-wrap gap-2">
                            {missing.map((item) => (
                                <Button key={item.action} asChild>
                                    <Link href={item.href}>{item.action}</Link>
                                </Button>
                            ))}
                        </div>
                    </div>
                ) : (
                    <LinkForm {...props} />
                )}
            </div>
        </>
    );
}

function LinkForm({
    risks,
    mitigations,
    owners,
    saeriCategories,
    lifecyclePhases,
    costLevels,
    reviewIntervalDays,
}: Props) {
    const { url } = usePage();

    // The risk page links here with `?risk=<id>`; ignore an unknown id.
    const requested = new URLSearchParams(url.split('?')[1]).get('risk');
    const [riskId, setRiskId] = useState(
        risks.some((risk) => String(risk.id) === requested)
            ? (requested ?? '')
            : '',
    );
    const [category, setCategory] = useState<CategoryFilter>('all');
    const [mitigationId, setMitigationId] = useState('');
    const [estimatedCost, setEstimatedCost] = useState('');

    const risk = risks.find((option) => String(option.id) === riskId);
    const linked = risk?.linked_mitigation_ids ?? [];
    // R-6: a pair already linked is not offered again.
    const available = availableMitigations(risk, mitigations, category);
    const mitigation = mitigations.find(
        (option) => String(option.id) === mitigationId,
    );

    // Risks grouped by the AI system they belong to.
    const groups = Object.values(
        Object.groupBy(risks, (option) => option.ai_system.id),
    )
        .filter((group) => group !== undefined)
        .sort((a, b) => a[0].ai_system.name.localeCompare(b[0].ai_system.name));

    function chooseRisk(value: string) {
        setRiskId(value);
        const chosen = risks.find((option) => String(option.id) === value);

        if (
            mitigation &&
            !availableMitigations(chosen, mitigations, 'all').includes(
                mitigation,
            )
        ) {
            setMitigationId('');
        }
    }

    function chooseCategory(value: string) {
        const next = (value || 'all') as CategoryFilter;
        setCategory(next);

        if (
            mitigation &&
            next !== 'all' &&
            mitigation.saeri_category !== next
        ) {
            setMitigationId('');
        }
    }

    function chooseMitigation(value: string) {
        setMitigationId(value);
        const chosen = mitigations.find(
            (option) => String(option.id) === value,
        );

        // The catalogue's suggestion is a starting point the user can adjust.
        if (chosen) {
            setEstimatedCost(chosen.suggested_cost);
        }
    }

    return (
        <Form {...LinkController.store.form()} className="max-w-xl space-y-6">
            {({ processing, errors }) => (
                <>
                    <div className="grid gap-2">
                        <Label htmlFor="risk_id">Risco</Label>
                        <Select
                            name="risk_id"
                            value={riskId}
                            onValueChange={chooseRisk}
                            required
                        >
                            <SelectTrigger
                                id="risk_id"
                                className="w-full"
                                aria-invalid={errors.risk_id ? true : undefined}
                            >
                                <SelectValue placeholder="Selecione o risco" />
                            </SelectTrigger>
                            <SelectContent>
                                {groups.map((group) => (
                                    <SelectGroup key={group[0].ai_system.id}>
                                        <SelectLabel>
                                            {group[0].ai_system.name}
                                        </SelectLabel>
                                        {group.map((option) => (
                                            <SelectItem
                                                key={option.id}
                                                value={String(option.id)}
                                            >
                                                {option.name}
                                            </SelectItem>
                                        ))}
                                    </SelectGroup>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.risk_id} />
                    </div>

                    <div className="grid gap-3">
                        <Label htmlFor="mitigation_id">Mitigação</Label>
                        <ToggleGroup
                            type="single"
                            variant="outline"
                            size="sm"
                            value={category}
                            onValueChange={chooseCategory}
                            aria-label="Filtrar por categoria SAERI"
                            className="flex-wrap"
                        >
                            <ToggleGroupItem value="all">Todas</ToggleGroupItem>
                            {saeriCategories.map((option) => (
                                <ToggleGroupItem
                                    key={option.value}
                                    value={option.value}
                                >
                                    {labelFor(
                                        saeriCategoryLabels,
                                        option.value,
                                    )}
                                </ToggleGroupItem>
                            ))}
                        </ToggleGroup>
                        <Select
                            name="mitigation_id"
                            value={mitigationId}
                            onValueChange={chooseMitigation}
                            required
                        >
                            <SelectTrigger
                                id="mitigation_id"
                                className="w-full"
                                aria-invalid={
                                    errors.mitigation_id ? true : undefined
                                }
                            >
                                <SelectValue placeholder="Selecione uma mitigação do catálogo" />
                            </SelectTrigger>
                            <SelectContent>
                                {available.map((option) => (
                                    <SelectItem
                                        key={option.id}
                                        value={String(option.id)}
                                    >
                                        {option.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        {available.length === 0 && (
                            <p className="text-muted-foreground text-sm">
                                {linked.length > 0 && category === 'all'
                                    ? 'Este risco já está vinculado a todas as mitigações do catálogo.'
                                    : 'Nenhuma mitigação disponível nesta categoria.'}
                            </p>
                        )}
                        <InputError message={errors.mitigation_id} />

                        {mitigation && (
                            // RNF03: a qualitative estimate comes with its
                            // source and its uncertainty.
                            <dl className="bg-muted/40 grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                                <DetailItem label="Categoria SAERI">
                                    {
                                        saeriCategoryLabels[
                                            mitigation.saeri_category
                                        ]
                                    }
                                </DetailItem>
                                <DetailItem label="Custo sugerido">
                                    {costLevelLabels[mitigation.suggested_cost]}
                                </DetailItem>
                                <DetailItem label="Incerteza">
                                    <Badge
                                        className={
                                            uncertaintyBadgeClasses[
                                                mitigation.uncertainty_level
                                            ]
                                        }
                                    >
                                        {
                                            uncertaintyLevelLabels[
                                                mitigation.uncertainty_level
                                            ]
                                        }
                                    </Badge>
                                </DetailItem>
                                <DetailItem label="Fonte">
                                    <span className="font-normal">
                                        {mitigation.bibliography_source}
                                    </span>
                                </DetailItem>
                            </dl>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="owner_id">Responsável</Label>
                        <Select name="owner_id" required>
                            <SelectTrigger
                                id="owner_id"
                                className="w-full"
                                aria-invalid={
                                    errors.owner_id ? true : undefined
                                }
                            >
                                <SelectValue placeholder="Selecione o papel responsável" />
                            </SelectTrigger>
                            <SelectContent>
                                {owners.map((owner) => (
                                    <SelectItem
                                        key={owner.id}
                                        value={String(owner.id)}
                                    >
                                        {owner.organizational_role} (
                                        {owner.area})
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.owner_id} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="lifecycle_phase">
                                Fase do ciclo de vida
                            </Label>
                            <Select name="lifecycle_phase" required>
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

                        <div className="grid gap-2">
                            <Label htmlFor="estimated_cost">
                                Custo estimado
                            </Label>
                            <Select
                                name="estimated_cost"
                                value={estimatedCost}
                                onValueChange={setEstimatedCost}
                                required
                            >
                                <SelectTrigger
                                    id="estimated_cost"
                                    className="w-full"
                                    aria-invalid={
                                        errors.estimated_cost ? true : undefined
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
                            <InputError message={errors.estimated_cost} />
                        </div>
                    </div>

                    <p className="text-muted-foreground text-sm">
                        O vínculo nasce como Planejado. A próxima revisão será
                        em {dateFromToday(reviewIntervalDays)}.
                    </p>

                    <div className="flex items-center gap-4">
                        {/* RF01: no link without a target risk. */}
                        <Button disabled={!riskId || processing}>Salvar</Button>
                        <Button variant="ghost" asChild>
                            <Link href={index()}>Cancelar</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

LinksCreate.layout = {
    breadcrumbs: [
        { title: 'Vínculos', href: index() },
        { title: 'Criar vínculo', href: create() },
    ],
};
