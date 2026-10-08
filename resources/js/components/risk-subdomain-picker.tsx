import type { ReactNode } from 'react';
import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { termLabel } from '@/lib/labels';
import { splitByRiskProfile } from '@/lib/risk-profile';
import type { ExpectedSubdomain } from '@/lib/risk-profile';
import type { RiskDomainOption } from '@/types/models';

/** The first error on the subdomains, on the list or on one of its items. */
export function subdomainError(
    errors: Partial<Record<string, string>>,
): string | undefined {
    return (
        errors.risk_subdomains ??
        Object.entries(errors).find(([key]) =>
            key.startsWith('risk_subdomains.'),
        )?.[1]
    );
}

/**
 * Checkboxes for the MIT risk subdomains, each with the definition from the
 * source to help the classification. The system's risk profile comes first;
 * the rest follows grouped by domain. Checked ones are sent as
 * `risk_subdomains[]`.
 */
export function RiskSubdomainPicker({
    legend,
    help,
    domains,
    expected,
    selected,
    onToggle,
    disabled,
    error,
}: {
    legend: string;
    /** A short help line, before the taxonomy's name. */
    help: string;
    domains: RiskDomainOption[];
    expected: ExpectedSubdomain[];
    selected: string[];
    onToggle: (code: string, checked: boolean) => void;
    disabled: boolean;
    error?: string;
}) {
    const profile = splitByRiskProfile(domains, expected);
    const checkbox = (
        subdomain: RiskDomainOption['children'][number],
        extra?: ReactNode,
    ) => (
        <SubdomainCheckbox
            key={subdomain.code}
            subdomain={subdomain}
            checked={selected.includes(subdomain.code)}
            onToggle={onToggle}
            extra={extra}
        />
    );

    return (
        <fieldset
            className="grid gap-2"
            aria-invalid={error ? true : undefined}
            aria-describedby="risk_subdomains-help"
        >
            <legend className="mb-2 text-sm font-medium">{legend}</legend>
            <p
                id="risk_subdomains-help"
                className="text-muted-foreground text-sm"
            >
                {help} Taxonomia de domínios do MIT AI Risk Repository.
            </p>

            {disabled ? (
                <p className="text-muted-foreground rounded-lg border border-dashed p-4 text-sm">
                    Escolha o sistema primeiro.
                </p>
            ) : (
                <>
                    {selected.length > 0 && (
                        <p className="text-sm">
                            <span className="text-muted-foreground">
                                Selecionados:{' '}
                            </span>
                            {selected.join(', ')}
                        </p>
                    )}
                    <div className="grid max-h-[32rem] gap-6 overflow-y-auto rounded-lg border p-4">
                        {profile.expected.length > 0 ? (
                            <section className="grid gap-3">
                                <h3 className="text-sm font-semibold">
                                    Esperados para este sistema
                                </h3>
                                {profile.expected.map((subdomain) =>
                                    checkbox(
                                        subdomain,
                                        <span className="text-muted-foreground text-xs">
                                            {termLabel(subdomain.domain)} ·{' '}
                                            {subdomain.risks_count}{' '}
                                            {subdomain.risks_count === 1
                                                ? 'risco cadastrado'
                                                : 'riscos cadastrados'}
                                        </span>,
                                    ),
                                )}
                            </section>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Este sistema ainda não tem riscos cadastrados:
                                todos os subdomínios aparecem abaixo, por
                                domínio.
                            </p>
                        )}

                        <section className="grid gap-4">
                            {profile.expected.length > 0 && (
                                <div className="grid gap-1">
                                    <h3 className="text-sm font-semibold">
                                        Outros subdomínios
                                    </h3>
                                    <p className="text-muted-foreground text-xs">
                                        Um evento fora dos riscos cadastrados
                                        pode indicar um risco ainda não
                                        identificado.
                                    </p>
                                </div>
                            )}
                            {profile.others.map((domain) => (
                                <div key={domain.code} className="grid gap-2">
                                    <p className="text-sm font-medium">
                                        {termLabel(domain)}
                                    </p>
                                    {domain.children.map((subdomain) =>
                                        checkbox(subdomain),
                                    )}
                                </div>
                            ))}
                        </section>
                    </div>
                </>
            )}
            <InputError message={error} />
        </fieldset>
    );
}

function SubdomainCheckbox({
    subdomain,
    checked,
    onToggle,
    extra,
}: {
    subdomain: RiskDomainOption['children'][number];
    checked: boolean;
    onToggle: (code: string, checked: boolean) => void;
    /** A line under the name, such as the domain and risk count. */
    extra?: ReactNode;
}) {
    const id = `risk_subdomain_${subdomain.code}`;

    return (
        <div className="flex items-start gap-3">
            <Checkbox
                id={id}
                name="risk_subdomains[]"
                value={subdomain.code}
                checked={checked}
                onCheckedChange={(state) =>
                    onToggle(subdomain.code, state === true)
                }
                className="mt-0.5"
            />
            <div className="grid gap-0.5">
                <Label htmlFor={id} className="leading-snug">
                    {termLabel(subdomain)}
                </Label>
                {extra}
                {subdomain.description && (
                    // The definition in the source, verbatim.
                    <p lang="en" className="text-muted-foreground text-xs">
                        {subdomain.description}
                    </p>
                )}
            </div>
        </div>
    );
}
