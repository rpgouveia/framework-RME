import { Form } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import type { RouteFormDefinition } from '@/wayfinder';

type Props = {
    /** The `destroy.form(id)` definition from the controller's actions. */
    form: RouteFormDefinition<'post'>;
    title: string;
    description: ReactNode;
    /** Defaults to a destructive "Excluir" button. */
    trigger?: ReactNode;
};

/** Confirmation dialog that submits a delete request when confirmed. */
export function DeleteDialog({ form, title, description, trigger }: Props) {
    return (
        <Dialog>
            <DialogTrigger asChild>
                {trigger ?? <Button variant="destructive">Excluir</Button>}
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <Form {...form}>
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
