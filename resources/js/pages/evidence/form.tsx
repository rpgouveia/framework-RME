import { Link } from '@inertiajs/react';
import type { InertiaLinkProps } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { evidenceTypeLabels, labelFor } from '@/lib/labels';
import type { EnumOption, Evidence } from '@/types/models';

type Props = {
    types: EnumOption[];
    errors: Partial<Record<string, string>>;
    processing: boolean;
    submitLabel: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    evidence?: Evidence;
};

/**
 * Fields shared by the create and edit screens. Render it inside an Inertia
 * `<Form>`. There is no date field: the system stamps the registration date
 * when the evidence is created (Tela 3).
 */
export function EvidenceForm({
    types,
    errors,
    processing,
    submitLabel,
    cancelHref,
    evidence,
}: Props) {
    return (
        <>
            <div className="grid gap-2">
                <Label htmlFor="type">Tipo</Label>
                <Select name="type" defaultValue={evidence?.type} required>
                    <SelectTrigger
                        id="type"
                        className="w-full"
                        aria-invalid={errors.type ? true : undefined}
                    >
                        <SelectValue placeholder="Selecione o tipo" />
                    </SelectTrigger>
                    <SelectContent>
                        {types.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(evidenceTypeLabels, option.value)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.type} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">Descrição</Label>
                <Textarea
                    id="description"
                    name="description"
                    defaultValue={evidence?.description}
                    required
                    maxLength={255}
                    placeholder="Ex.: Relatório de auditoria de equidade do 1º trimestre, assinado pelo comitê."
                    aria-invalid={errors.description ? true : undefined}
                />
                <InputError message={errors.description} />
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
