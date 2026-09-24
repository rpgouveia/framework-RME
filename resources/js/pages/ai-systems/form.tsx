import { Link } from '@inertiajs/react';
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
import { dateInputValue } from '@/lib/format';
import { categoryLabels, labelFor, sourceTypeLabels } from '@/lib/labels';
import type { AiSystem, EnumOption } from '@/types/models';

type Props = {
    sourceTypes: EnumOption[];
    categories: EnumOption[];
    errors: Partial<Record<string, string>>;
    processing: boolean;
    submitLabel: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    aiSystem?: AiSystem;
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
}: Props) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="name">Nome</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={aiSystem?.name}
                    required
                    maxLength={255}
                    placeholder="Ex.: Modelo de score de crédito"
                    aria-invalid={errors.name ? true : undefined}
                />
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="source_type">Origem</Label>
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
                        <SelectValue placeholder="Selecione a origem" />
                    </SelectTrigger>
                    <SelectContent>
                        {sourceTypes.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(sourceTypeLabels, option.value)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.source_type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="category">Categoria</Label>
                <Select
                    name="category"
                    defaultValue={aiSystem?.category}
                    required
                >
                    <SelectTrigger
                        id="category"
                        className="w-full"
                        aria-invalid={errors.category ? true : undefined}
                    >
                        <SelectValue placeholder="Selecione a categoria de risco (EU AI Act)" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(categoryLabels, option.value)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.category} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="registration_date">Data de cadastro</Label>
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
                    <Link href={cancelHref}>Cancelar</Link>
                </Button>
            </div>
        </>
    );
}
