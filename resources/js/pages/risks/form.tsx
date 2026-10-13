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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { labelFor } from '@/lib/enum-labels';
import { termLabel } from '@/lib/names';
import type { AiSystem, EnumOption, Risk, RiskDomain } from '@/types/models';

/** Mirrors the `max:2000` rule in RiskValidationRules. */
const DESCRIPTION_MAX = 2000;

type Props = {
    aiSystems: Pick<AiSystem, 'id' | 'name'>[];
    /** The MIT AI risk domains, each with its subdomains. */
    riskDomains: RiskDomain[];
    lifecyclePhases: EnumOption[];
    uncertaintyLevels: EnumOption[];
    errors: Partial<Record<string, string>>;
    processing: boolean;
    submitLabel: string;
    cancelHref: NonNullable<InertiaLinkProps['href']>;
    risk?: Risk;
    /** Preselects the system on create, e.g. from `?ai_system=<id>`. */
    defaultAiSystemId?: number;
    /**
     * Preselects the subdomain (and its domain) on create, e.g. from
     * `?subdomain=2.1`, the shortcut of a risk not yet mapped (0019).
     */
    defaultSubdomainCode?: string;
};

/**
 * Fields shared by the create and edit screens. Render it inside an Inertia
 * `<Form>`: the selects get a `name`, so Radix submits them through a hidden
 * native `<select>`.
 */
export function RiskForm({
    aiSystems,
    riskDomains,
    lifecyclePhases,
    uncertaintyLevels,
    errors,
    processing,
    submitLabel,
    cancelHref,
    risk,
    defaultAiSystemId,
    defaultSubdomainCode,
}: Props) {
    const enumLabels = useEnumLabels();
    const { t, tc } = useTranslations();
    const aiSystemId = risk?.ai_system_id ?? defaultAiSystemId;
    const linkCount = risk?.links_count ?? 0;
    const [descriptionLength, setDescriptionLength] = useState(
        risk?.description.length ?? 0,
    );
    // Only the subdomain is sent; the domain narrows the list and is derived
    // from it on the server.
    const initial = riskDomains
        .flatMap((domain) =>
            domain.children.map((subdomain) => ({ domain, subdomain })),
        )
        .find(({ subdomain }) =>
            risk === undefined
                ? subdomain.code === defaultSubdomainCode
                : subdomain.id === risk.risk_subdomain_id,
        );
    const [domainCode, setDomainCode] = useState(initial?.domain.code ?? '');
    const [subdomainId, setSubdomainId] = useState(
        initial ? String(initial.subdomain.id) : '',
    );
    const subdomains =
        riskDomains.find((domain) => domain.code === domainCode)?.children ??
        [];
    const subdomain = subdomains.find(
        (option) => String(option.id) === subdomainId,
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
                            {t('AI system')}
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
                            {tc(
                                'It cannot be changed: the risk has :count link in the traceability chain of this system.|It cannot be changed: the risk has :count links in the traceability chain of this system.',
                                linkCount,
                            )}
                        </p>
                    </>
                ) : (
                    <>
                        <Label htmlFor="ai_system_id">{t('AI system')}</Label>
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
                                <SelectValue
                                    placeholder={t('Select the system')}
                                />
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
                <Label htmlFor="name">{t('Name')}</Label>
                <Input
                    id="name"
                    name="name"
                    defaultValue={risk?.name}
                    required
                    maxLength={255}
                    placeholder={t('E.g.: Selection bias')}
                    aria-describedby="name-help"
                    aria-invalid={errors.name ? true : undefined}
                />
                {/* 0001 and 0002: the name identifies the kind of risk and
                    may repeat across systems; the description is this
                    system's own. */}
                <p id="name-help" className="text-muted-foreground text-sm">
                    {t(
                        'The kind of risk, in a few words. It is what the lists show, and it may repeat in other systems.',
                    )}
                </p>
                <InputError message={errors.name} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="description">{t('Description')}</Label>
                <Textarea
                    id="description"
                    name="description"
                    defaultValue={risk?.description}
                    required
                    maxLength={DESCRIPTION_MAX}
                    // Grows with the text, then scrolls.
                    className="max-h-80 min-h-32"
                    placeholder={t(
                        'E.g.: The model performs worse for groups underrepresented in the training data, which may lead to discriminatory decisions.',
                    )}
                    aria-invalid={errors.description ? true : undefined}
                    aria-describedby="description-help description-count"
                    onChange={(event) =>
                        setDescriptionLength(event.target.value.length)
                    }
                />
                <p
                    id="description-help"
                    className="text-muted-foreground text-sm"
                >
                    {t(
                        'How this risk shows in this system: the context, the mechanism and who may be affected.',
                    )}
                </p>
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
                <Label htmlFor="risk_domain">{t('Risk domain')}</Label>
                <Select
                    value={domainCode}
                    onValueChange={(code) => {
                        setDomainCode(code);
                        setSubdomainId('');
                    }}
                >
                    <SelectTrigger id="risk_domain" className="w-full">
                        <SelectValue placeholder={t('Select the domain')} />
                    </SelectTrigger>
                    <SelectContent>
                        {riskDomains.map((domain) => (
                            <SelectItem key={domain.code} value={domain.code}>
                                {termLabel(domain)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <p className="text-muted-foreground text-sm">
                    {t(
                        'The standard classification of the risk, by the taxonomy of the MIT AI Risk Repository (Slattery et al.). It ties the risk to the recommended mitigations and to the adverse events.',
                    )}
                </p>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="risk_subdomain_id">{t('Risk subdomain')}</Label>
                <Select
                    name="risk_subdomain_id"
                    value={subdomainId}
                    onValueChange={setSubdomainId}
                    disabled={domainCode === ''}
                    required
                >
                    <SelectTrigger
                        id="risk_subdomain_id"
                        className="w-full"
                        aria-invalid={
                            errors.risk_subdomain_id ? true : undefined
                        }
                    >
                        <SelectValue
                            placeholder={
                                domainCode === ''
                                    ? t('Select the domain first')
                                    : t('Select the subdomain')
                            }
                        />
                    </SelectTrigger>
                    <SelectContent>
                        {subdomains.map((option) => (
                            <SelectItem
                                key={option.id}
                                value={String(option.id)}
                            >
                                {termLabel(option)}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {subdomain?.description && (
                    // The definition in the source, verbatim.
                    <p
                        lang="en"
                        className="text-muted-foreground border-l-2 pl-3 text-sm"
                    >
                        {subdomain.description}
                    </p>
                )}
                <InputError message={errors.risk_subdomain_id} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="lifecycle_phase">{t('Lifecycle phase')}</Label>
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
                        <SelectValue placeholder={t('Select the phase')} />
                    </SelectTrigger>
                    <SelectContent>
                        {lifecyclePhases.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(
                                    enumLabels.LifecyclePhase,
                                    option.value,
                                )}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.lifecycle_phase} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="uncertainty_level">
                    {t('Uncertainty level')}
                </Label>
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
                        <SelectValue placeholder={t('Select the level')} />
                    </SelectTrigger>
                    <SelectContent>
                        {uncertaintyLevels.map((option) => (
                            <SelectItem key={option.value} value={option.value}>
                                {labelFor(
                                    enumLabels.UncertaintyLevel,
                                    option.value,
                                )}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <InputError message={errors.uncertainty_level} />
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
