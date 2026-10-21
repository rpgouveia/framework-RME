import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { AiSystemName } from '@/components/ai-system-name';
import { PaginationLinks } from '@/components/pagination-links';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import { formatDate } from '@/lib/format';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import { categoryBadgeClasses } from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { create, edit, index, show } from '@/routes/ai-systems';
import type { AiSystem, Paginated } from '@/types/models';

type Props = {
    aiSystems: Paginated<AiSystem>;
};

export default function AiSystemsIndex({ aiSystems }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('AI systems')} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title={t('AI systems')} />
                    <Button asChild>
                        <Link href={create()}>{t('Register AI system')}</Link>
                    </Button>
                </div>

                {aiSystems.data.length === 0 ? (
                    <EmptyState />
                ) : (
                    <>
                        <SystemsTable systems={aiSystems.data} />
                        {aiSystems.last_page > 1 && (
                            <PaginationLinks links={aiSystems.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EmptyState() {
    const { t } = useTranslations();

    return (
        <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
            <p className="text-muted-foreground text-sm">
                {t('No AI system has been registered yet.')}
            </p>
            <Button asChild>
                <Link href={create()}>{t('Register AI system')}</Link>
            </Button>
        </div>
    );
}

function SystemsTable({ systems }: { systems: AiSystem[] }) {
    const enumLabels = useEnumLabels();
    const { t } = useTranslations();

    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>{t('Name')}</TableHead>
                        <TableHead>{t('Origin')}</TableHead>
                        <TableHead>{t('Risk tier')}</TableHead>
                        <TableHead>{t('Registration')}</TableHead>
                        <TableHead className="text-right">
                            {t('Risks')}
                        </TableHead>
                        <TableHead>
                            <span className="sr-only">{t('Actions')}</span>
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {systems.map((system) => (
                        <TableRow
                            key={system.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(system.id))}
                        >
                            <TableCell className="font-medium">
                                <AiSystemName
                                    domain={system.application_domain}
                                >
                                    <Link
                                        href={show(system.id)}
                                        className="hover:underline"
                                    >
                                        {system.name}
                                    </Link>
                                </AiSystemName>
                            </TableCell>
                            <TableCell>
                                <Badge variant="outline">
                                    {
                                        enumLabels.SystemSourceType[
                                            system.source_type
                                        ]
                                    }
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <Badge
                                    className={
                                        categoryBadgeClasses[system.category]
                                    }
                                >
                                    {
                                        enumLabels.AiSystemCategory[
                                            system.category
                                        ]
                                    }
                                </Badge>
                            </TableCell>
                            <TableCell>
                                {formatDate(system.registration_date)}
                            </TableCell>
                            <TableCell className="text-right tabular-nums">
                                {system.risks_count ?? 0}
                            </TableCell>
                            <TableCell className="text-right">
                                <Link
                                    href={edit(system.id)}
                                    className="text-muted-foreground hover:text-foreground text-sm hover:underline"
                                >
                                    {t('Edit')}
                                </Link>
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

AiSystemsIndex.layout = ({ translations }: WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [{ title: t('AI systems'), href: index() }],
    };
};
