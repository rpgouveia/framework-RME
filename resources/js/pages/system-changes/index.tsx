import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { PaginationLinks } from '@/components/pagination-links';
import { SystemChangesTable } from '@/components/system-changes-table';
import { Button } from '@/components/ui/button';
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
    return (
        <>
            <Head title="Mudanças do sistema" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Mudanças do sistema"
                        description={`${aiSystem.name}. Cada mudança fica registrada e não pode ser editada nem excluída.`}
                    />
                    <Button asChild>
                        <Link href={create(aiSystem.id)}>
                            Registrar mudança
                        </Link>
                    </Button>
                </div>

                {systemChanges.data.length === 0 ? (
                    <p className="text-muted-foreground rounded-xl border border-dashed p-12 text-center text-sm">
                        Nenhuma mudança foi registrada para este sistema.
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

SystemChangesIndex.layout = ({ aiSystem }: Props) => ({
    breadcrumbs: [
        { title: 'Sistemas de IA', href: aiSystemsIndex() },
        { title: aiSystem.name, href: showAiSystem(aiSystem.id) },
        { title: 'Mudanças', href: index(aiSystem.id) },
    ],
});
