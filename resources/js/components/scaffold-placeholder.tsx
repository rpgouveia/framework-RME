/**
 * Temporary stand-in for a page nobody has built yet.
 *
 * It renders the props the controller sent so you can see the exact shape of
 * the data while you build the real page. Delete it once the page is done.
 */
export function ScaffoldPlaceholder({
    title,
    todo,
    data,
}: {
    title: string;
    todo: string;
    data: Record<string, unknown>;
}) {
    return (
        <div className="flex h-full flex-1 flex-col gap-4 p-4">
            <div className="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border border-dashed p-6">
                <h1 className="text-xl font-semibold">{title}</h1>
                <p className="text-muted-foreground mt-1 text-sm">{todo}</p>
            </div>

            <div className="border-sidebar-border/70 dark:border-sidebar-border min-h-0 flex-1 overflow-auto rounded-xl border p-4">
                <h2 className="text-muted-foreground mb-2 text-xs font-medium tracking-wide uppercase">
                    Props received from the controller
                </h2>
                <pre className="text-xs">{JSON.stringify(data, null, 2)}</pre>
            </div>
        </div>
    );
}
