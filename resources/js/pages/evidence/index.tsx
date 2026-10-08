import { Head, Link } from '@inertiajs/react';
import { ShieldCheckIcon } from 'lucide-react';
import Heading from '@/components/heading';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { PaginationLinks } from '@/components/pagination-links';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { formatDate } from '@/lib/format';
import { costLevelLabels, evidenceTypeLabels, linkLabel } from '@/lib/labels';
import { rowLink } from '@/lib/row-link';
import { show } from '@/routes/evidence';
import { index as linksIndex, show as showLink } from '@/routes/links';
import { create, index } from '@/routes/links/evidence';
import type { Evidence, Link as RiskLink, Paginated } from '@/types/models';

type Props = {
    link: RiskLink;
    evidence: Paginated<Evidence>;
    /** The link is declared and its evidence now allows verifying it. */
    canVerify: boolean;
};

export default function EvidenceIndex({ link, evidence, canVerify }: Props) {
    return (
        <>
            <Head title="Evidências" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading title="Evidências" description={linkLabel(link)} />
                    <Button asChild>
                        <Link href={create(link.id)}>Registrar evidência</Link>
                    </Button>
                </div>

                {canVerify && (
                    // The shortcut after registering evidence on a declared
                    // link (0018).
                    <Alert>
                        <ShieldCheckIcon aria-hidden />
                        <AlertTitle>
                            Este vínculo pode ser verificado
                        </AlertTitle>
                        <AlertDescription>
                            <p>
                                O vínculo está declarado e já tem a evidência
                                que a verificação exige.
                            </p>
                            <Button size="sm" asChild>
                                <Link
                                    href={showLink(link.id, {
                                        query: { verify: 1 },
                                    })}
                                >
                                    Verificar vínculo
                                </Link>
                            </Button>
                        </AlertDescription>
                    </Alert>
                )}

                {evidence.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-4 rounded-xl border border-dashed p-12 text-center">
                        <p className="text-muted-foreground max-w-md text-sm">
                            Nenhuma evidência foi registrada para este vínculo.
                            Sem evidência, não há como comprovar que a mitigação
                            foi aplicada.
                        </p>
                        <Button asChild>
                            <Link href={create(link.id)}>
                                Registrar evidência
                            </Link>
                        </Button>
                    </div>
                ) : (
                    <>
                        <EvidenceTable evidence={evidence.data} />
                        {evidence.last_page > 1 && (
                            <PaginationLinks links={evidence.links} />
                        )}
                    </>
                )}
            </div>
        </>
    );
}

function EvidenceTable({ evidence }: { evidence: Evidence[] }) {
    return (
        <div className="rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Tipo</TableHead>
                        <TableHead>Descrição</TableHead>
                        <TableHead>Custo observado</TableHead>
                        <TableHead>Registrada em</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {evidence.map((item) => (
                        <TableRow
                            key={item.id}
                            className="cursor-pointer"
                            onClick={rowLink(show(item.id))}
                        >
                            <TableCell className="font-medium">
                                <Link
                                    href={show(item.id)}
                                    className="hover:underline"
                                >
                                    {evidenceTypeLabels[item.type]}
                                </Link>
                            </TableCell>
                            <TableCell>
                                <p className="max-w-md truncate">
                                    {item.description}
                                </p>
                            </TableCell>
                            <TableCell>
                                {item.observed_cost ? (
                                    costLevelLabels[item.observed_cost]
                                ) : (
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                )}
                            </TableCell>
                            <TableCell>
                                {formatDate(item.registration_date)}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </div>
    );
}

EvidenceIndex.layout = ({ link }: Props) => ({
    breadcrumbs: [
        { title: 'Vínculos', href: linksIndex() },
        { title: linkLabel(link), href: showLink(link.id) },
        { title: 'Evidências', href: index(link.id) },
    ],
});
