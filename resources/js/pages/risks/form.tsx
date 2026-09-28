import { Link } from '@inertiajs/react';
import { useState } from 'react';
import type { InertiaLinkProps } from '@inertiajs/react';
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
import { Textarea } from '@/components/ui/textarea';
import {
    labelFor,
    lifecyclePhaseLabels,
    riskCategoryLabels,
    uncertaintyLevelLabels,
} from '@/lib/labels';
import type { AiSystem, EnumOption, Risk } from '@/types/models';

/** Mirrors the `max:2000` rule in RiskValidationRules. */
const DESCRIPTION_MAX = 2000;

type Props = {
    aiSystems: Pick<AiSystem, 'id' | 'name'>[];
    categories: EnumOption[];
    lifecyclePhases: EnumOption[];
    uncertaintyLevels: EnumOption[];
    errors: Partial<Record<string, string>>;
    processing: boolean;
    submitLabel: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    risk?: Risk;
    /** Preselects the system on create, e.g. from `?ai_system=<id>`. */
    defaultAiSystemId?: number;
};

/**
 * Fields shared by the create and edit screens. Render it inside an Inertia
 * `<Form>`: the selects get a `name`, so Radix submits them through a hidden
 * native `<select>`.
 */
export function RiskForm({
    aiSystems,
    categories,
    lifecyclePhases,
    uncertaintyLevels,
    errors,
    processing,
    submitLabel,
    cancelHref,
    risk,
    defaultAiSystemId,
}: Props) {
    const aiSystemId = risk?.ai_system_id ?? defaultAiSystemId;
    const linkCount = risk?.links_count ?? 0;
    const [descriptionLength, setDescriptionLength] = useState(
        risk?.description.length ?? 0,
    );

    return (
        <>
            <div className="grid gap-2">
                {linkCount > 0 ? (
                    // Once linked, the risk belongs to its system's
                    // traceability chain; the server keeps the system when
                    // the field is not sent.
                    <>
                        <span className="text-sm font-medium">
                            Sistema de IA
                        </span>
                        <p className="font-medium">
                            {
                                aiSystems.find(
                                    (system) =>
                                        system.id === risk?.ai_system_id,
                                )?.name
                            }
                        </p>
                        <p className="text-muted-foreground text-sm">
                            Não pode ser alterado: o risco tem {linkCount}{' '}
                            {linkCount === 1 ? 'vínculo' : 'vínculos'} na cadeia
                            de rastreabilidade deste sistema.
                        </p>
                    </>
                ) : (
                    <>
                        <Label htmlFor="ai_system_id">Sistema de IA</Label>
                        <Select
                            name="ai_system_id"
                            defaultValue={
                                aiSystemId === undefined
                                    ? undefined
                                    : String(aiSystemId)
                            }
                            required
                        >
                            <SelectTrigger
                                id="ai_system_id"
                                className="w-full"
                                aria-invalid={
                                    errors.ai_system_id ? true : undefined
                                }
                            >
                                <SelectValue placeholder="Selecione o sistema" />
                            </SelectTrigger>
                            <SelectContent>
                                {aiSystems.map((system) => (
                                    <SelectItem
                                        key={system.id}
                                        value={String(system.id)}
                                    >
                                        {system.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </>
                )}
                <InputError message={errors.ai_system_id} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="name">Nome</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={risk?.name}
                    required
                    maxLength={255}
                    placeholder="Ex.: Viés de seleção"
                    aria-invalid={errors.name ? true : undefined}
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Descrição</Label>
                <Textarea
                    id="description"
                    name="description"
                    defaultValue={risk?.description}
                    required
                    maxLength={DESCRIPTION_MAX}
                    // Grows with the text, then scrolls.
                    className="max-h-80 min-h-32"
                    placeholder="Ex.: O modelo apresenta desempenho inferior para grupos sub-representados nos dados de treino, o que pode levar a decisões discriminatórias."
                    aria-invalid={errors.description ? true : undefined}
                    aria-describedby="description-count"
                    onChange={(event) =>
                        setDescriptionLength(event.target.value.length)
                    }
                />
                <div className="flex justify-between gap-4">
                    <InputError message={errors.description} />
                    <p
                        id="description-count"
                        className="text-muted-foreground ml-auto text-xs tabular-nums"
                    >
                        {descriptionLength}/{DESCRIPTION_MAX}
                    </p>
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="category">Categoria</Label>
                <Select name="category" defaultValue={risk?.category} required>
                    <SelectTrigger
                        id="category"
                        className="w-full"
                        aria-invalid={errors.category ? true : undefined}
                    >
                        <SelectValue placeholder="Selecione a categoria" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(riskCategoryLabels, option.value)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.category} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="lifecycle_phase">Fase do ciclo de vida</Label>
                <Select
                    name="lifecycle_phase"
                    defaultValue={risk?.lifecycle_phase}
                    required
                >
                    <SelectTrigger
                        id="lifecycle_phase"
                        className="w-full"
                        aria-invalid={errors.lifecycle_phase ? true : undefined}
                    >
                        <SelectValue placeholder="Selecione a fase" />
                    </SelectTrigger>
                    <SelectContent>
                        {lifecyclePhases.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(lifecyclePhaseLabels, option.value)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.lifecycle_phase} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="uncertainty_level">Nível de incerteza</Label>
                <Select
                    name="uncertainty_level"
                    defaultValue={risk?.uncertainty_level}
                    required
                >
                    <SelectTrigger
                        id="uncertainty_level"
                        className="w-full"
                        aria-invalid={
                            errors.uncertainty_level ? true : undefined
                        }
                    >
                        <SelectValue placeholder="Selecione o nível" />
                    </SelectTrigger>
                    <SelectContent>
                        {uncertaintyLevels.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(uncertaintyLevelLabels, option.value)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.uncertainty_level} />
            </div>

            <div className="flex items-center gap-4">
                <Button disabled={processing}>{submitLabel}</Button>
                <Button variant="ghost" asChild>
                    <Link href={cancelHref}>Cancelar</Link>
                </Button>
            </div>
        </>
    );
}
