export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'space-y-0.5'}>
            <h2
                className={
                    variant === 'small'
                        ? 'mb-0.5 text-base font-semibold'
                        : 'text-[28px] leading-9 font-normal tracking-normal'
                }
            >
                {title}
            </h2>
            {description && (
                <p className="text-muted-foreground max-w-3xl text-base tracking-normal">
                    {description}
                </p>
            )}
        </header>
    );
}
