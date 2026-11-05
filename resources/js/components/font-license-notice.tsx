import { useTranslations } from '@/hooks/use-translations';
import { cn } from '@/lib/utils';

/**
 * The notice the SIL Open Font License asks of a site that serves its own
 * fonts: authorship and license stay visible, with the full text one click
 * away.
 */
export function FontLicenseNotice({ className }: { className?: string }) {
    const { t } = useTranslations();

    return (
        <a
            href="/licenses/ibm-plex-ofl.txt"
            target="_blank"
            rel="noreferrer"
            className={cn(
                'text-subtle-foreground block text-xs tracking-[0.32px] hover:underline',
                className,
            )}
        >
            {t(
                'IBM Plex typefaces, © IBM Corp., under the SIL Open Font License 1.1.',
            )}
        </a>
    );
}
