import { Form, Head, Link, usePage } from '@inertiajs/react';
import { InfoIcon, SearchIcon } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import LinkController from '@/actions/App/Http/Controllers/LinkController';
import { CostHelp } from '@/components/cost-help';
import { DetailItem } from '@/components/detail-item';
import { FormSection } from '@/components/form-section';
import { Term as Definition } from '@/components/help';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { UncertaintyBadge } from '@/components/link-status-badges';
import { UnacceptableTierAlert } from '@/components/unacceptable-tier-alert';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { availableMitigations, recommendMitigations } from '@/lib/link-options';
import { labelFor } from '@/lib/enum-labels';
import { categoryBadgeClasses } from '@/lib/labels';
import { termLabel } from '@/lib/names';
import {
    index as mitigationsIndex,
    show as showMitigation,
} from '@/routes/mitigations';
import { create as createOwner } from '@/routes/owners';
import { create as createRisk } from '@/routes/risks';
import { create, index } from '@/routes/links';
import type {
    AiSystem,
    AiSystemCategory,
    EnumOption,
    Mitigation,
    Owner,
    Risk,
    TaxonomyCategory,
} from '@/types/models';

/** A term of a taxonomy, as the form shows it. */
type Term = { code: string; name: string };

type RiskOption = Pick<Risk, 'id' | 'name'> & {
    /** The tier sets the review interval and the unacceptable alert. */
    ai_system: Pick<AiSystem, 'id' | 'name' | 'category'>;
    /** The MIT domain and subdomain of the risk. */
    domain: Term | null;
    subdomain: Term;
    /** Mitigations this risk is already linked to (R-6). */
    linked_mitigation_ids: number[];
};

/** A catalogue entry with its Saeri category derived from the subcategory. */
type MitigationOption = Pick<
    Mitigation,
    'id' | 'name' | 'suggested_cost' | 'uncertainty_level' | 'estimate_source'
> & {
    category: string | null;
    subcategory: Term;
    /** The MIT risk subdomains the entry treats. */
    target_risk_subdomains: Term[];
};

type Props = {
    risks: RiskOption[];
    mitigations: MitigationOption[];
    owners: Pick<Owner, 'id' | 'organizational_role' | 'area'>[];
    saeriCategories: TaxonomyCategory[];
    lifecyclePhases: EnumOption[];
    costLevels: EnumOption[];
    /** R-7: days to the first review for each tier; null means none. */
    reviewIntervals: Partial<Record<AiSystemCategory, number | null>>;
    /** The link this one replaces, after a reassessment chose to (0020). */
    replacing: {
        id: number;
        risk_id: number;
        risk: string;
        mitigation: string;
    } | null;
};

export default function LinksCreate(props: Props) {
    const { t } = useTranslations();
    const { risks, mitigations, owners } = props;

    const missing = [
        risks.length === 0 && {
            label: t('a risk'),
            href: createRisk(),
            action: t('Register risk'),
        },
        // The catalogue is loaded from its data file (R-8), not registered
        // here, so the way out is to see it.
        mitigations.length === 0 && {
            label: t('a mitigation in the catalogue'),
            href: mitigationsIndex(),
            action: t('See the mitigation catalogue'),
        },
        owners.length === 0 && {
            label: t('an owner'),
            href: createOwner(),
            action: t('Register owner'),
        },
    ].filter((item) => item !== false);

    return (
        <>
            <Head title={t('Create link')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <Heading
                    title={t('Create link')}
                    description={t(
                        'Associate a mitigation from the catalogue with a risk already registered.',
                    )}
                />
                {missing.length > 0 ? (
                    // A form that cannot be completed helps no one: point to
                    // what has to exist first.
                    <div className="flex max-w-xl flex-col items-start gap-4 border border-dashed p-6">
                        <p className="text-muted-foreground text-sm">
                            {t('To create a link you need :items.', {
                                items: missing
                                    .map((item) => item.label)
                                    .join(', '),
                            })}
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
    reviewIntervals,
    replacing,
}: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const { url } = usePage();

    // The risk page links here with `?risk=<id>`; ignore an unknown id.
    const requested = new URLSearchParams(url.split('?')[1]).get('risk');
    const [riskId, setRiskId] = useState(
        risks.some((risk) => String(risk.id) === requested)
            ? (requested ?? '')
            : '',
    );
    const [category, setCategory] = useState<string | null>(null);
    const [subcategory, setSubcategory] = useState<string | null>(null);
    const [mitigationId, setMitigationId] = useState('');
    const [estimatedCost, setEstimatedCost] = useState('');
    const [query, setQuery] = useState('');

    const risk = risks.find((option) => String(option.id) === riskId);
    const linked = risk?.linked_mitigation_ids ?? [];
    // R-6: a pair already linked is not offered again. What treats the
    // risk's subdomain comes first; the rest stays on offer (R-8).
    const { recommended, others } = recommendMitigations(risk, mitigations, {
        category,
        subcategory,
    });
    const available = [...recommended, ...others];
    const search = query.trim().toLocaleLowerCase();
    // The search only narrows what is listed; the chosen one stays chosen.
    const matches = (option: MitigationOption) =>
        search === '' || option.name.toLocaleLowerCase().includes(search);
    const mitigationGroups = [
        {
            label: t('Recommended for this risk'),
            options: recommended.filter(matches),
        },
        {
            label: risk ? t('Others from the catalogue') : t('Catalogue'),
            options: others.filter(matches),
        },
    ].filter((group) => group.options.length > 0);
    const categoryOption = saeriCategories.find(
        (option) => option.code === category,
    );
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
            !availableMitigations(chosen, mitigations, {
                category: null,
                subcategory: null,
            }).includes(mitigation)
        ) {
            setMitigationId('');
        }
    }

    function chooseCategory(value: string) {
        const next = value === '' || value === 'all' ? null : value;
        setCategory(next);
        setSubcategory(null);

        if (mitigation && next !== null && mitigation.category !== next) {
            setMitigationId('');
        }
    }

    function chooseSubcategory(value: string) {
        const next = value === 'all' ? null : value;
        setSubcategory(next);

        if (
            mitigation &&
            next !== null &&
            mitigation.subcategory.code !== next
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
        <Form
            {...LinkController.store.form()}
            className="grid max-w-[1040px] gap-10"
        >
            {({ processing, errors }) => (
                <>
                    {replacing &&
                        String(replacing.risk_id) === riskId && (
                            // The new link records which one it replaces.
                            <Alert>
                                <AlertTitle>
                                    {t(
                                        'Replacement of the link :risk → :mitigation',
                                        {
                                            risk: replacing.risk,
                                            mitigation: replacing.mitigation,
                                        },
                                    )}
                                </AlertTitle>
                                <AlertDescription>
                                    {t(
                                        'It was cancelled in the reassessment. Choose another mitigation for the same risk.',
                                    )}
                                    <input
                                        type="hidden"
                                        name="replaces_link_id"
                                        value={replacing.id}
                                    />
                                    <InputError
                                        message={errors.replaces_link_id}
                                    />
                                </AlertDescription>
                            </Alert>
                        )}

                    <FormSection step={1} title={t('Risk')}>
                        <div className="grid gap-2">
                            <Label htmlFor="risk_id">{t('Risk')}</Label>
                            <Select
                                name="risk_id"
                                value={riskId}
                                onValueChange={chooseRisk}
                                required
                            >
                                <SelectTrigger
                                    id="risk_id"
                                    className="w-full"
                                    aria-invalid={
                                        errors.risk_id ? true : undefined
                                    }
                                >
                                    <SelectValue
                                        placeholder={t('Select the risk')}
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {groups.map((group) => (
                                        <SelectGroup
                                            key={group[0].ai_system.id}
                                        >
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
                            <p className="text-muted-foreground text-xs tracking-[0.32px]">
                                {t(
                                    'Grouped by AI system. Risk and mitigation cannot be changed once the link is created.',
                                )}
                            </p>
                            <InputError message={errors.risk_id} />
                        </div>
                        {risk && (
                            <dl className="bg-muted grid gap-4 p-4 sm:grid-cols-3">
                                <DetailItem label={t('Risk subdomain (MIT)')}>
                                    {termLabel(risk.subdomain)}
                                    {risk.domain && (
                                        <span className="text-muted-foreground block text-xs tracking-[0.32px]">
                                            {termLabel(risk.domain)}
                                        </span>
                                    )}
                                </DetailItem>
                                <DetailItem label={t('AI system')}>
                                    {risk.ai_system.name}
                                </DetailItem>
                                <DetailItem label={t('Risk tier')}>
                                    <Badge
                                        className={
                                            categoryBadgeClasses[
                                                risk.ai_system.category
                                            ]
                                        }
                                    >
                                        {
                                            enumLabels.AiSystemCategory[
                                                risk.ai_system.category
                                            ]
                                        }
                                    </Badge>
                                </DetailItem>
                            </dl>
                        )}
                        {risk?.ai_system.category === 'unacceptable' && (
                            <UnacceptableTierAlert short />
                        )}
                    </FormSection>

                    <FormSection step={2} title={t('Mitigation')}>
                        <div className="grid gap-6 lg:grid-cols-[minmax(0,3fr)_minmax(280px,2fr)]">
                            <div className="grid content-start gap-3">
                                <ToggleGroup
                                    type="single"
                                    variant="outline"
                                    size="sm"
                                    value={category ?? 'all'}
                                    onValueChange={chooseCategory}
                                    aria-label={t(
                                        'Filter by category of the taxonomy of Saeri et al.',
                                    )}
                                    className="flex-wrap"
                                >
                                    <ToggleGroupItem value="all">
                                        {t('All')}
                                    </ToggleGroupItem>
                                    {saeriCategories.map((option) => (
                                        <ToggleGroupItem
                                            key={option.code}
                                            value={option.code}
                                        >
                                            {termLabel(option)}
                                        </ToggleGroupItem>
                                    ))}
                                </ToggleGroup>
                                {/* The subcategory narrows further, but is optional. */}
                                {categoryOption && (
                                    <Select
                                        value={subcategory ?? 'all'}
                                        onValueChange={chooseSubcategory}
                                    >
                                        <SelectTrigger
                                            className="w-full"
                                            aria-label={t(
                                                'Filter by subcategory',
                                            )}
                                        >
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                {t(
                                                    'All subcategories of :category',
                                                    {
                                                        category:
                                                            categoryOption.name,
                                                    },
                                                )}
                                            </SelectItem>
                                            {categoryOption.children.map(
                                                (option) => (
                                                    <SelectItem
                                                        key={option.code}
                                                        value={option.code}
                                                    >
                                                        {termLabel(option)}
                                                    </SelectItem>
                                                ),
                                            )}
                                        </SelectContent>
                                    </Select>
                                )}
                                <div className="relative">
                                    <SearchIcon
                                        className="text-muted-foreground pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2"
                                        aria-hidden
                                    />
                                    <Input
                                        type="search"
                                        value={query}
                                        onChange={(event) =>
                                            setQuery(event.target.value)
                                        }
                                        placeholder={t(
                                            'Search mitigation by name',
                                        )}
                                        aria-label={t(
                                            'Search mitigation by name',
                                        )}
                                        className="pl-9"
                                    />
                                </div>
                                <RadioGroup
                                    name="mitigation_id"
                                    value={mitigationId}
                                    onValueChange={chooseMitigation}
                                    required
                                    aria-label={t('Mitigation')}
                                    aria-invalid={
                                        errors.mitigation_id ? true : undefined
                                    }
                                    className="max-h-[28rem] gap-0 overflow-y-auto border"
                                >
                                    {mitigationGroups.map((group) => (
                                        <div key={group.label}>
                                            <p className="bg-secondary text-muted-foreground sticky top-0 z-10 px-4 py-2 text-xs font-semibold tracking-[0.32px]">
                                                {group.label}
                                            </p>
                                            {group.options.map((option) => (
                                                <label
                                                    key={option.id}
                                                    className="hover:bg-accent has-[[data-state=checked]]:bg-accent relative grid cursor-pointer grid-cols-[16px_1fr_auto] items-start gap-3 border-b px-4 py-3 last:border-b-0"
                                                >
                                                    <RadioGroupItem
                                                        value={String(
                                                            option.id,
                                                        )}
                                                        className="mt-0.5"
                                                    />
                                                    <span className="grid gap-0.5">
                                                        <span>
                                                            {option.name}
                                                        </span>
                                                        <span className="text-muted-foreground text-xs tracking-[0.32px]">
                                                            {termLabel(
                                                                option.subcategory,
                                                            )}
                                                        </span>
                                                    </span>
                                                    <Badge className="bg-tag-gray text-tag-gray-foreground">
                                                        {
                                                            enumLabels
                                                                .CostLevel[
                                                                option
                                                                    .suggested_cost
                                                            ]
                                                        }
                                                    </Badge>
                                                </label>
                                            ))}
                                        </div>
                                    ))}
                                </RadioGroup>
                                {risk &&
                                    available.length > 0 &&
                                    recommended.length === 0 && (
                                        <p className="text-muted-foreground text-sm">
                                            {t(
                                                'No mitigation of this filter treats the subdomain :code; all remain available.',
                                                { code: risk.subdomain.code },
                                            )}
                                        </p>
                                    )}
                                {available.length === 0 && (
                                    <p className="text-muted-foreground text-sm">
                                        {linked.length > 0 && category === null
                                            ? t(
                                                  'This risk is already linked to every mitigation of the catalogue.',
                                              )
                                            : t(
                                                  'No mitigation available in this filter.',
                                              )}
                                    </p>
                                )}
                                <InputError message={errors.mitigation_id} />
                            </div>

                            <ChosenMitigation
                                mitigation={mitigation}
                                riskSubdomain={risk?.subdomain.code}
                            />
                        </div>
                    </FormSection>

                    <FormSection step={3} title={t('Responsibility')}>
                        <div className="grid gap-6 md:grid-cols-3">
                            <div className="grid min-w-0 content-start gap-2">
                                <Label htmlFor="owner_id">{t('Owner')}</Label>
                                <Select name="owner_id" required>
                                    <SelectTrigger
                                        id="owner_id"
                                        className="w-full"
                                        aria-invalid={
                                            errors.owner_id ? true : undefined
                                        }
                                    >
                                        <SelectValue
                                            placeholder={t(
                                                'Select the accountable role',
                                            )}
                                        />
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

                            <div className="grid min-w-0 content-start gap-2">
                                <Label htmlFor="lifecycle_phase">
                                    {t('Lifecycle phase')}
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
                                        <SelectValue
                                            placeholder={t('Select the phase')}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {lifecyclePhases.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
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

                            <div className="grid min-w-0 content-start gap-2">
                                <span className="flex h-4 items-center gap-1">
                                    <Label htmlFor="estimated_cost">
                                        {t('Estimated cost')}
                                    </Label>
                                    <CostHelp />
                                </span>
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
                                            errors.estimated_cost
                                                ? true
                                                : undefined
                                        }
                                    >
                                        <SelectValue
                                            placeholder={t('Select the cost')}
                                        />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {costLevels.map((option) => (
                                            <SelectItem
                                                key={option.value}
                                                value={option.value}
                                            >
                                                {labelFor(
                                                    enumLabels.CostLevel,
                                                    option.value,
                                                )}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <p className="text-muted-foreground text-xs tracking-[0.32px]">
                                    {t(
                                        'Filled with the suggested cost. It is the effort planned for this organization, not an amount of money.',
                                    )}
                                </p>
                                <InputError message={errors.estimated_cost} />
                            </div>
                        </div>
                    </FormSection>

                    <ReviewNotice
                        tier={risk?.ai_system.category}
                        reviewIntervals={reviewIntervals}
                    />

                    <div className="flex items-center gap-4">
                        {/* No link without a target risk. */}
                        <Button size="lg" disabled={!riskId || processing}>
                            {t('Create link')}
                        </Button>
                        <Button variant="ghost" size="lg" asChild>
                            <Link href={index()}>{t('Cancel')}</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
    );
}

/**
 * The catalogue entry the user picked, beside the list: what it treats and
 * how sure its cost estimate is, with the source of the estimate.
 */
function ChosenMitigation({
    mitigation,
    riskSubdomain,
}: {
    mitigation: MitigationOption | undefined;
    riskSubdomain: string | undefined;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <aside
            aria-label={t('Chosen mitigation')}
            className="bg-muted grid content-start gap-5 p-4"
        >
            {mitigation === undefined ? (
                <p className="text-muted-foreground text-sm">
                    {t(
                        'Choose a mitigation to see what it treats and its cost.',
                    )}
                </p>
            ) : (
                <>
                    <div className="grid gap-1">
                        <span className="text-muted-foreground text-xs tracking-[0.32px]">
                            {t('Chosen mitigation')}
                        </span>
                        <h3 className="text-base font-semibold">
                            {mitigation.name}
                        </h3>
                        <span className="text-muted-foreground text-xs tracking-[0.32px]">
                            {termLabel(mitigation.subcategory)} · Saeri et al.
                        </span>
                    </div>
                    <div className="grid gap-2">
                        <span className="text-muted-foreground text-xs tracking-[0.32px]">
                            {t('Risk subdomains treated (MIT)')}
                        </span>
                        <span className="flex flex-wrap gap-1">
                            {mitigation.target_risk_subdomains.map((term) => (
                                <Definition
                                    key={term.code}
                                    definition={term.name}
                                    className="no-underline"
                                >
                                    <Badge
                                        className={
                                            term.code === riskSubdomain
                                                ? 'border-foreground bg-transparent font-semibold'
                                                : 'bg-tag-gray text-tag-gray-foreground'
                                        }
                                    >
                                        {term.code === riskSubdomain
                                            ? t(':code · this risk', {
                                                  code: term.code,
                                              })
                                            : term.code}
                                    </Badge>
                                </Definition>
                            ))}
                        </span>
                    </div>
                    <dl className="grid grid-cols-2 gap-4">
                        <DetailItem label={t('Suggested cost')}>
                            {enumLabels.CostLevel[mitigation.suggested_cost]}
                        </DetailItem>
                        <DetailItem label={t('Uncertainty')}>
                            <UncertaintyBadge
                                level={mitigation.uncertainty_level}
                            />
                        </DetailItem>
                        <div className="col-span-2">
                            <DetailItem label={t('Source of the estimate')}>
                                {mitigation.estimate_source}
                            </DetailItem>
                        </div>
                    </dl>
                    <Link
                        href={showMitigation(mitigation.id)}
                        className="text-link hover:text-link-hover text-sm hover:underline"
                    >
                        {t('See in the catalogue')}
                    </Link>
                </>
            )}
        </aside>
    );
}

/**
 * When the periodic review starts (R-7 as revised by 0018): at the link's
 * first verification, one interval of the tier of the risk's system later;
 * never, for a system that cannot operate.
 */
function ReviewNotice({
    tier,
    reviewIntervals,
}: {
    tier: AiSystemCategory | undefined;
    reviewIntervals: Props['reviewIntervals'];
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    if (tier === undefined) {
        return (
            <Notice>
                {t(
                    "The link starts Planned and Declared. The periodic review starts at the first verification, with the interval of the tier of the chosen risk's system.",
                )}
            </Notice>
        );
    }

    const days = reviewIntervals[tier] ?? null;

    if (days === null) {
        return (
            <Notice>
                {t(
                    'The link starts Planned and Declared and will have no periodic review: the system is in the unacceptable tier and cannot operate, so the link cannot be verified. It serves to plan the discontinuation.',
                )}
            </Notice>
        );
    }

    return (
        <Notice>
            {t(
                'The link starts Planned and Declared. The periodic review starts at the first verification: the next review will be :days days after it, because the system is of :tier risk.',
                { days, tier: enumLabels.AiSystemCategory[tier].toLowerCase() },
            )}
        </Notice>
    );
}

function Notice({ children }: { children: ReactNode }) {
    return (
        <Alert>
            <InfoIcon aria-hidden />
            <AlertDescription>{children}</AlertDescription>
        </Alert>
    );
}

LinksCreate.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Links'), href: index() },
            { title: t('Create link'), href: create() },
        ],
    };
};
