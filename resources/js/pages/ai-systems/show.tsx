import { Form, Head, Link } from '@inertiajs/react';
import AiSystemController from '@/actions/App/Http/Controllers/AiSystemController';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import {
    categoryBadgeClasses,
    categoryLabels,
    lifecyclePhaseLabels,
    riskCategoryLabels,
    sourceTypeLabels,
    uncertaintyLevelLabels,
} from '@/lib/labels';
import { edit, index, show } from '@/routes/ai-systems';
import {
    csv as reportCsv,
    json as reportJson,
} from '@/routes/ai-systems/report';
import { create as createRisk, show as showRisk } from '@/routes/risks';
import type { AiSystem, Risk } from '@/types/models';

type Props = {
    aiSystem: AiSystem;
};

export default function AiSystemsShow({ aiSystem }: Props) {
    const risks = aiSystem.risks ?? [];

    return (
        <>
            <Head title={aiSystem.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <header className="flex flex-wrap items-start justify-between gap-4">
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-xl font-semibold tracking-tight">
                            {aiSystem.name}
                        </h1>
                        <Badge
                            className={categoryBadgeClasses[aiSystem.category]}
                        >
                            {categoryLabels[aiSystem.category]}
                        </Badge>
                    </div>
                    <div className="flex items-center gap-2">
                        <Button variant="outline" asChild>
                            <Link href={edit(aiSystem.id)}>Editar</Link>
                        </Button>
                        {risks.length === 0 && (
                            <DeleteSystem aiSystem={aiSystem} />
                        )}
                    </div>
                </header>

                <Card>
                    <CardHeader>
                        <CardTitle>Detalhes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <Detail label="Origem">
                                {sourceTypeLabels[aiSystem.source_type]}
                            </Detail>
                            <Detail label="Categoria">
                                {categoryLabels[aiSystem.category]}
                            </Detail>
                            <Detail label="Cadastro">
                                {formatDate(aiSystem.registration_date)}
                            </Detail>
                            <Detail label="Riscos">{risks.length}</Detail>
                        </dl>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between gap-4">
                        <CardTitle>Riscos</CardTitle>
                        <Button size="sm" asChild>
                            <Link href={createRisk()}>Cadastrar risco</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {risks.length === 0 ? (
                            <p className="text-muted-foreground text-sm">
                                Nenhum risco foi mapeado para este sistema
                                ainda.
                            </p>
                        ) : (
                            <RisksTable risks={risks} />
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Relatório de rastreabilidade</CardTitle>
                        <CardDescription>
                            O arquivo traz a cadeia completa de cada risco deste
                            sistema: as mitigações aplicadas e as evidências que
                            as comprovam.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-2">
                        {/* Plain anchors: an Inertia Link expects an Inertia
                            response and would break on a file download. */}
                        <Button variant="outline" asChild>
                            <a href={reportCsv(aiSystem.id).url} download>
                                Baixar CSV
                            </a>
                        </Button>
                        <Button variant="outline" asChild>
                            <a href={reportJson(aiSystem.id).url} download>
                                Baixar JSON
                            </a>
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

function Detail({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div className="grid gap-1">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="font-medium">{children}</dd>
        </div>
    );
}

function RisksTable({ risks }: { risks: Risk[] }) {
    return (
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead>Descrição</TableHead>
                    <TableHead>Categoria</TableHead>
                    <TableHead>Fase do ciclo de vida</TableHead>
                    <TableHead>Incerteza</TableHead>
                    <TableHead className="text-right">Vínculos</TableHead>
                </TableRow>
            </TableHeader>
            <TableBody>
                {risks.map((risk) => (
                    <TableRow key={risk.id}>
                        <TableCell className="max-w-md whitespace-normal">
                            <Link
                                href={showRisk(risk.id)}
                                className="font-medium hover:underline"
                            >
                                {risk.description}
                            </Link>
                        </TableCell>
                        <TableCell>
                            {riskCategoryLabels[risk.category]}
                        </TableCell>
                        <TableCell>
                            {lifecyclePhaseLabels[risk.lifecycle_phase]}
                        </TableCell>
                        <TableCell>
                            {uncertaintyLevelLabels[risk.uncertainty_level]}
                        </TableCell>
                        <TableCell className="text-right tabular-nums">
                            {risk.links_count ?? 0}
                        </TableCell>
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}

function DeleteSystem({ aiSystem }: { aiSystem: AiSystem }) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="destructive">Excluir</Button>
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Excluir este sistema de IA?</DialogTitle>
                <DialogDescription>
                    O sistema "{aiSystem.name}" será removido do portfólio. Esta
                    ação não pode ser desfeita.
                </DialogDescription>
                <Form {...AiSystemController.destroy.form(aiSystem.id)}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary">Cancelar</Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                Excluir
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

AiSystemsShow.layout = ({ aiSystem }: Props) => ({
    breadcrumbs: [
        { title: 'Sistemas de IA', href: index() },
        { title: aiSystem.name, href: show(aiSystem.id) },
    ],
});
