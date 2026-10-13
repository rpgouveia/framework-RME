import { Form, Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import LinkController from '@/actions/App/Http/Controllers/LinkController';
import { DetailItem } from '@/components/detail-item';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { UnacceptableTierAlert } from '@/components/unacceptable-tier-alert';
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
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { availableMitigations, recommendMitigations } from '@/lib/link-options';
import { labelFor } from '@/lib/enum-labels';
import { uncertaintyBadgeClasses } from '@/lib/labels';
import { termLabel } from '@/lib/names';
import { index as mitigationsIndex } from '@/routes/mitigations';
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
            <div className="flex h-full flex-1 flex-col p-4">
                <Heading
                    title={t('Create link')}
                    description={t(
                        'Associate a mitigation from the catalogue with a risk already registered.',
                    )}
                />
                {missing.length > 0 ? (
                    // A form that cannot be completed helps no one: point to
                    // what has to exist first.
                    <div className="flex max-w-xl flex-col items-start gap-4 rounded-xl border border-dashed p-6">
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

    const risk = risks.find((option) => String(option.id) === riskId);
    const linked = risk?.linked_mitigation_ids ?? [];
    // R-6: a pair already linked is not offered again. What treats the
    // risk's subdomain comes first; the rest stays on offer (R-8).
    const { recommended, others } = recommendMitigations(risk, mitigations, {
        category,
        subcategory,
    });
    const available = [...recommended, ...others];
    const mitigationGroups = [
        { label: t('Recommended for this risk'), options: recommended },
        {
            label: risk ? t('Others from the catalogue') : t('Catalogue'),
            options: others,
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
        <Form {...LinkController.store.form()} className="max-w-xl space-y-6">
            {({ processing, errors }) => (
                <>
                    {replacing &&
                        String(replacing.risk_id) === riskId && (
                            // The new link records which one it replaces.
                            <div className="grid gap-1 rounded-lg border border-sky-300 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950/30 dark:text-sky-200">
                                <p className="font-semibold">
                                    {t(
                                        'Replacement of the link :risk → :mitigation',
                                        {
                                            risk: replacing.risk,
                                            mitigation: replacing.mitigation,
                                        },
                                    )}
                                </p>
                                <p>
                                    {t(
                                        'It was cancelled in the reassessment. Choose another mitigation for the same risk.',
                                    )}
                                </p>
                                <input
                                    type="hidden"
                                    name="replaces_link_id"
                                    value={replacing.id}
                                />
                                <InputError message={errors.replaces_link_id} />
                            </div>
                        )}
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
                                aria-invalid={errors.risk_id ? true : undefined}
                            >
                                <SelectValue
                                    placeholder={t('Select the risk')}
                                />
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
                        {risk && (
                            <p className="text-muted-foreground text-sm">
                                {risk.domain
                                    ? t(
                                          'Risk subdomain (MIT): :subdomain, in :domain',
                                          {
                                              subdomain: termLabel(
                                                  risk.subdomain,
                                              ),
                                              domain: termLabel(risk.domain),
                                          },
                                      )
                                    : t('Risk subdomain (MIT): :subdomain', {
                                          subdomain: termLabel(risk.subdomain),
                                      })}
                            </p>
                        )}
                        <InputError message={errors.risk_id} />
                        {risk?.ai_system.category === 'unacceptable' && (
                            <UnacceptableTierAlert short />
                        )}
                    </div>

                    <div className="grid gap-3">
                        <Label htmlFor="mitigation_id">{t('Mitigation')}</Label>
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
                                    aria-label={t('Filter by subcategory')}
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">
                                        {t('All subcategories of :category', {
                                            category: categoryOption.name,
                                        })}
                                    </SelectItem>
                                    {categoryOption.children.map((option) => (
                                        <SelectItem
                                            key={option.code}
                                            value={option.code}
                                        >
                                            {termLabel(option)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        )}
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
                                <SelectValue
                                    placeholder={t(
                                        'Select a mitigation from the catalogue',
                                    )}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                {mitigationGroups.map((group) => (
                                    <SelectGroup key={group.label}>
                                        <SelectLabel>{group.label}</SelectLabel>
                                        {group.options.map((option) => (
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

                        {mitigation && (
                            // RNF03: a qualitative estimate comes with its
                            // source and its uncertainty.
                            <dl className="bg-muted/40 grid gap-4 rounded-lg border p-4 sm:grid-cols-2">
                                <DetailItem
                                    label={t('Subcategory (Saeri et al.)')}
                                >
                                    {termLabel(mitigation.subcategory)}
                                </DetailItem>
                                <DetailItem
                                    label={t('Risk subdomains treated (MIT)')}
                                >
                                    <ul className="grid gap-1">
                                        {mitigation.target_risk_subdomains.map(
                                            (term) => (
                                                <li
                                                    key={term.code}
                                                    className={
                                                        term.code ===
                                                        risk?.subdomain.code
                                                            ? undefined
                                                            : 'font-normal'
                                                    }
                                                >
                                                    {termLabel(term)}
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                </DetailItem>
                                <DetailItem label={t('Suggested cost')}>
                                    {
                                        enumLabels.CostLevel[
                                            mitigation.suggested_cost
                                        ]
                                    }
                                </DetailItem>
                                <DetailItem label={t('Uncertainty')}>
                                    <Badge
                                        className={
                                            uncertaintyBadgeClasses[
                                                mitigation.uncertainty_level
                                            ]
                                        }
                                    >
                                        {
                                            enumLabels.UncertaintyLevel[
                                                mitigation.uncertainty_level
                                            ]
                                        }
                                    </Badge>
                                </DetailItem>
                                <DetailItem label={t('Source of the estimate')}>
                                    <span className="font-normal">
                                        {mitigation.estimate_source}
                                    </span>
                                </DetailItem>
                            </dl>
                        )}
                    </div>

                    <div className="grid gap-2">
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

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
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

                        <div className="grid gap-2">
                            <Label htmlFor="estimated_cost">
                                {t('Estimated cost')}
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
                            <InputError message={errors.estimated_cost} />
                        </div>
                    </div>

                    <ReviewNotice
                        tier={risk?.ai_system.category}
                        reviewIntervals={reviewIntervals}
                    />

                    <div className="flex items-center gap-4">
                        {/* RF01: no link without a target risk. */}
                        <Button disabled={!riskId || processing}>
                            {t('Save')}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={index()}>{t('Cancel')}</Link>
                        </Button>
                    </div>
                </>
            )}
        </Form>
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
            <p className="text-muted-foreground text-sm">
                {t(
                    "The link starts Planned and Declared. The periodic review starts at the first verification, with the interval of the tier of the chosen risk's system.",
                )}
            </p>
        );
    }

    const days = reviewIntervals[tier] ?? null;

    if (days === null) {
        return (
            <p className="text-muted-foreground text-sm">
                {t(
                    'The link starts Planned and Declared and will have no periodic review: the system is in the unacceptable tier and cannot operate, so the link cannot be verified. It serves to plan the discontinuation.',
                )}
            </p>
        );
    }

    return (
        <p className="text-muted-foreground text-sm">
            {t(
                'The link starts Planned and Declared. The periodic review starts at the first verification: the next review will be :days days after it, because the system is of :tier risk.',
                { days, tier: enumLabels.AiSystemCategory[tier].toLowerCase() },
            )}
        </p>
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
