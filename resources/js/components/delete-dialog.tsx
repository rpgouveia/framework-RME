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
import { useTranslations } from '@/hooks/use-translations';
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
    const { t } = useTranslations();

    return (
        <Dialog>
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button variant="destructive">{t('Delete')}</Button>
                )}
            </DialogTrigger>
            <DialogContent>
                <DialogTitle>{title}</DialogTitle>
                <DialogDescription>{description}</DialogDescription>
                <Form {...form}>
                    {({ processing }) => (
                        <DialogFooter className="gap-2">
                            <DialogClose asChild>
                                <Button variant="secondary">
                                    {t('Cancel')}
                                </Button>
                            </DialogClose>
                            <Button
                                type="submit"
                                variant="destructive"
                                disabled={processing}
                            >
                                {t('Delete')}
                            </Button>
                        </DialogFooter>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
