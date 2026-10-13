import { Head, Link } from '@inertiajs/react';
import RiskController from '@/actions/App/Http/Controllers/RiskController';
import { LinkStatusBadges } from '@/components/link-status-badges';
import { ReviewDate } from '@/components/review-date';
import { DeleteDialog } from '@/components/delete-dialog';
import { DetailItem } from '@/components/detail-item';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useEnumLabels } from '@/hooks/use-enum-labels';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { uncertaintyBadgeClasses } from '@/lib/labels';
import { termLabel } from '@/lib/names';
import { rowLink } from '@/lib/row-link';
import { show as showAiSystem } from '@/routes/ai-systems';
import { create as createLink, show as showLink } from '@/routes/links';
import { edit, index, show } from '@/routes/risks';
import type { Link as RiskLink, Risk } from '@/types/models';

type Props = {
    risk: Risk;
};

export default function RisksShow({ risk }: Props) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();
    const links = risk.links ?? [];

    return (
        <>
            <Head title={risk.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <h1 className="max-w-3xl text-xl font-semibold tracking-tight">
                        {risk.name}
                    </h1>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(risk.id)}>{t('Edit')}</Link>
                        </Button>
                        {links.length === 0 && (
                            <DeleteDialog
                                form={RiskController.destroy.form(risk.id)}
                                title={t('Delete this risk?')}
                                description={t(
                                    'The risk ":name" will be removed. This action cannot be undone.',
                                    { name: risk.name },
                                )}
                            />
                        )}
                    </div>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>{t('Details')}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-6">
                        <dl>
                            <DetailItem label={t('Description')}>
                                <p className="font-normal whitespace-pre-line">
                                    {risk.description}
                                </p>
                            </DetailItem>
                        </dl>
                        {risk.risk_subdomain && (
                            <dl className="grid gap-4 sm:grid-cols-2">
                                <DetailItem label={t('Risk domain')}>
                                    {risk.risk_subdomain.parent &&
                                        termLabel(risk.risk_subdomain.parent)}
                                </DetailItem>
                                <DetailItem label={t('Risk subdomain')}>
                                    {termLabel(risk.risk_subdomain)}
                                    {risk.risk_subdomain.description && (
                                        // The definition in the source, verbatim.
                                        <p
                                            lang="en"
                                            className="text-muted-foreground mt-1 text-sm font-normal"
                                        >
                                            {risk.risk_subdomain.description}
                                        </p>
                                    )}
                                </DetailItem>
                            </dl>
                        )}
                        <dl className="grid gap-4 sm:grid-cols-3">
                            <DetailItem label={t('AI system')}>
                                {risk.ai_system && (
                                    <Link
                                        href={showAiSystem(risk.ai_system.id)}
                                        className="hover:underline"
                                    >
                                        {risk.ai_system.name}
                                    </Link>
                                )}
                            </DetailItem>
                            <DetailItem label={t('Lifecycle phase')}>
                                {
                                    enumLabels.LifecyclePhase[
                                        risk.lifecycle_phase
                                    ]
                                }
                            </DetailItem>
                            <DetailItem label={t('Uncertainty')}>
                                <Badge
                                    className={
                                        uncertaintyBadgeClasses[
                                            risk.uncertainty_level
                                        ]
                                    }
                                >
                                    {
                                        enumLabels.UncertaintyLevel[
                                            risk.uncertainty_level
                                        ]
                                    }
                                </Badge>
                            </DetailItem>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <CardTitle>{t('Links')}</CardTitle>
                        <Button size="sm" asChild>
                            <Link
                                href={createLink({ query: { risk: risk.id } })}
                            >
                                {t('Create link')}
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {links.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                {t(
                                    'This risk was identified but has no mitigation yet. Create a link to set how it will be treated and who answers for it.',
                                )}
                            </p>
                        ) : (
                            <LinksTable
                                links={links}
                                unacceptable={
                                    risk.ai_system?.category === 'unacceptable'
                                }
                            />
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function LinksTable({
    links,
    unacceptable,
}: {
    links: RiskLink[];
    /** Whether the risk's system is in the unacceptable tier. */
    unacceptable: boolean;
}) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>{t('Mitigation')}</TableHead>
                    <TableHead>{t('Owner')}</TableHead>
                    <TableHead>{t('Status')}</TableHead>
                    <TableHead>{t('Estimated cost')}</TableHead>
                    <TableHead>{t('Next review')}</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {links.map((link) => (
                    <TableRow
                        key={link.id}
                        className="cursor-pointer"
                        onClick={rowLink(showLink(link.id))}
                    >
                        <TableCell className="max-w-md whitespace-normal">
                            <Link
                                href={showLink(link.id)}
                                className="font-medium hover:underline"
                            >
                                {link.mitigation?.name}
                            </Link>
                        </TableCell>
                        <TableCell>
                            {link.owner && (
                                <div className="grid">
                                    <span>
                                        {link.owner.organizational_role}
                                    </span>
                                    <span className="text-muted-foreground text-xs">
                                        {link.owner.area}
                                    </span>
                                </div>
                            )}
                        </TableCell>
                        <TableCell>
                            <LinkStatusBadges link={link} />
                        </TableCell>
                        <TableCell>
                            {enumLabels.CostLevel[link.estimated_cost]}
                        </TableCell>
                        <TableCell>
                            <ReviewDate
                                date={link.next_review_date}
                                verification={link.verification_status}
                                unacceptable={unacceptable}
                            />
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

RisksShow.layout = ({ risk, translations }: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('Risks'), href: index() },
            { title: risk.name, href: show(risk.id) },
        ],
    };
};
