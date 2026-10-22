import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { SystemChangesTable } from '@/components/system-changes-table';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { translator } from '@/lib/i18n';
import type { WithTranslations } from '@/lib/i18n';
import {
    index as aiSystemsIndex,
    show as showAiSystem,
} from '@/routes/ai-systems';
import { create, index } from '@/routes/ai-systems/system-changes';
import type { AiSystem, Paginated, SystemChange } from '@/types/models';

type Props = {
    aiSystem: AiSystem;
    systemChanges: Paginated<SystemChange>;
};

export default function SystemChangesIndex({ aiSystem, systemChanges }: Props) {
    const { t } = useTranslations();

    return (
        <>
            <Head title={t('System changes')} />
            <div className="mx-auto flex w-full max-w-[1184px] flex-1 flex-col gap-8 px-4 py-6 md:px-8 md:py-8">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title={t('System changes')}
                        description={t(
                            ':system. Each change is recorded and can be neither edited nor deleted.',
                            { system: aiSystem.name },
                        )}
                    />
                    <Button asChild>
                        <Link href={create(aiSystem.id)}>
                            {t('Record change')}
                        </Link>
                    </Button>
                </div>

                {systemChanges.data.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        {t('No change has been recorded for this system.')}
                    </p>
                ) : (
                    <>
                        <SystemChangesTable changes={systemChanges.data} />
                        {systemChanges.last_page > 1 && (
                            <PaginationLinks links={systemChanges.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

SystemChangesIndex.layout = ({
    aiSystem,
    translations,
}: Props & WithTranslations) => {
    const t = translator(translations);

    return {
        breadcrumbs: [
            { title: t('AI systems'), href: aiSystemsIndex() },
            { title: aiSystem.name, href: showAiSystem(aiSystem.id) },
            { title: t('Changes'), href: index(aiSystem.id) },
        ],
    };
};
