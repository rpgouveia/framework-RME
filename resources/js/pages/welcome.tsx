import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    ArrowRight,
    Bot,
    Link2,
    ScrollText,
    ShieldAlert,
    ShieldCheck,
    TriangleAlert,
} from 'lucide-react';

import AppLogoIcon from '@/components/app-logo-icon';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard, home, login, register } from '@/routes';

export default function Welcome() {
    const { auth, name } = usePage().props;
    const { t } = useTranslations();

    const sections: {
        icon: LucideIcon;
        title: string;
        summary: string;
    }[] = [
        {
            icon: Bot,
            title: t('AI systems'),
            summary: t(
                'The portfolio of systems, with their application domain, origin and tier under the EU AI Act.',
            ),
        },
        {
            icon: ShieldAlert,
            title: t('Risks'),
            summary: t(
                'The risks of each system, classified by the MIT AI Risk Repository taxonomy, by lifecycle phase and by level of uncertainty.',
            ),
        },
        {
            icon: ShieldCheck,
            title: t('Mitigations'),
            summary: t(
                'A catalogue of measures classified by the taxonomy of Saeri et al., each one with the evidence it is expected to produce.',
            ),
        },
        {
            icon: Link2,
            title: t('Links'),
            summary: t(
                'The core of the chain: one mitigation applied to one risk under one accountable owner, proven by evidence and under periodic review after its verification.',
            ),
        },
        {
            icon: TriangleAlert,
            title: t('Adverse events'),
            summary: t(
                'Incidents and near misses in systems in operation, with actual or plausible harm to people, the organization or the ecosystem, classified by risk subdomain, which send the links they touch back to reassessment.',
            ),
        },
        {
            icon: ScrollText,
            title: t('Traceability'),
            summary: t(
                'Every change of status, with its origin and its author, exported as a traceability report.',
            ),
        },
    ];

    const primaryButton =
        'inline-flex min-h-11 items-center justify-center ' +
        'bg-primary px-5 py-3 text-sm text-primary-foreground ' +
        'transition-colors hover:bg-primary-hover ' +
        'active:bg-primary-active focus-visible:outline-2 ' +
        'focus-visible:outline-offset-2 focus-visible:outline-ring';

    const secondaryButton =
        'inline-flex min-h-11 items-center justify-center ' +
        'border border-border bg-field px-5 py-3 ' +
        'text-sm text-foreground transition-colors ' +
        'hover:bg-secondary focus-visible:outline-2 ' +
        'focus-visible:outline-ring';

    return (
        <>
            <Head title={t('Welcome')}></Head>

            <div className="bg-background text-foreground flex min-h-svh flex-col">
                {/* Cabeçalho */}
                <header className="border-border bg-background sticky top-0 z-50 w-full border-b">
                    <div className="mx-auto flex h-12 w-full max-w-[1312px] items-center justify-between gap-4 px-4 sm:px-8">
                        <Link
                            href={home()}
                            className="flex items-center gap-2.5 transition-opacity hover:opacity-80"
                            aria-label={t('Framework RME - Home page')}
                        >
                            <AppLogoIcon className="h-7 w-7 shrink-0" />

                            <span className="text-foreground text-[15px] font-medium">
                                {name || 'Framework RME'}
                            </span>
                        </Link>

                        <nav
                            aria-label={t('Access navigation')}
                            className="flex items-center gap-1 sm:gap-2"
                        >
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    prefetch
                                    className="bg-primary text-primary-foreground hover:bg-primary-hover inline-flex min-h-9 items-center px-4 text-sm"
                                >
                                    {t('Dashboard')}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="text-foreground hover:bg-accent inline-flex min-h-9 items-center px-4 text-sm"
                                    >
                                        {t('Log in')}
                                    </Link>

                                    <Link
                                        href={register()}
                                        className="border-foreground bg-primary text-primary-foreground hover:bg-primary-hover inline-flex min-h-9 items-center border px-4 text-sm"
                                    >
                                        {t('Sign up')}
                                    </Link>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="w-full flex-1">
                    {/* Seção principal */}
                    <section
                        aria-labelledby="hero-title"
                        className="mx-auto w-full max-w-[1312px] px-4 pt-12 pb-12 sm:px-8 sm:pt-16"
                    >
                        <div className="max-w-4xl">
                            {/* Logo original */}
                            <div className="bg-primary mb-8 flex h-16 w-16 items-center justify-center p-3">
                                <AppLogoIcon className="text-primary-foreground h-10 w-10" />
                            </div>

                            <h1
                                id="hero-title"
                                className="text-foreground mb-6 text-3xl leading-[1.2] font-light tracking-normal sm:text-4xl md:text-[42px]"
                            >
                                {t('Risk management for AI systems')}
                            </h1>

                            <div className="text-muted-foreground mb-8 max-w-3xl space-y-4 text-base leading-[1.5]">
                                <p>
                                    {t(
                                        'Keep a registry of the AI systems your organization runs, the risks identified for each one, the mitigations applied to those risks, who is accountable for each, and the evidence proving they are in place.',
                                    )}
                                </p>

                                <p>
                                    {t(
                                        'Review deadlines, adverse events and changes to a system take verified mitigations back to reassessment, and every step lands in an append-only audit trail.',
                                    )}
                                </p>
                            </div>

                            <div className="flex flex-wrap items-center gap-3">
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        prefetch
                                        className={primaryButton}
                                    >
                                        {t('Open the dashboard')}
                                        <ArrowRight
                                            className="ml-3 h-4 w-4"
                                            aria-hidden="true"
                                        />
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={register()}
                                            className={primaryButton}
                                        >
                                            {t('Create an account')}
                                        </Link>

                                        <Link
                                            href={login()}
                                            className={secondaryButton}
                                        >
                                            {t('Log in')}
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    </section>

                    {/* Divisor */}
                    <div className="mx-auto w-full max-w-[1312px] px-4 sm:px-8">
                        <hr className="border-border border-t" />
                    </div>

                    {/* Funcionalidades */}
                    <section
                        aria-labelledby="features-title"
                        className="mx-auto w-full max-w-[1312px] px-4 pt-10 pb-20 sm:px-8"
                    >
                        <h2
                            id="features-title"
                            className="text-muted-foreground mb-8 text-sm font-normal"
                        >
                            {t('What the framework keeps')}
                        </h2>

                        <ul className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {sections.map(({ icon: Icon, title, summary }) => (
                                <li
                                    key={title}
                                    className="border-border bg-background hover:border-border-strong hover:bg-muted flex flex-col border p-6 transition-colors duration-150"
                                >
                                    <Icon
                                        className="text-foreground mb-4 h-6 w-6"
                                        strokeWidth={1.5}
                                        aria-hidden="true"
                                    />

                                    <h3 className="text-foreground mb-2 text-base font-semibold">
                                        {title}
                                    </h3>

                                    <p className="text-muted-foreground text-sm leading-[1.45]">
                                        {summary}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>
                </main>

                {/* Rodapé */}
                <footer className="border-border bg-background mt-auto w-full border-t py-8">
                    <div className="text-muted-foreground mx-auto flex w-full max-w-[1312px] flex-col items-start justify-between gap-4 px-4 text-xs sm:flex-row sm:items-center sm:px-8">
                        <span>
                            {t(
                                'Compliance with the EU AI Act & MIT AI Risk Repository',
                            )}
                        </span>

                        <span className="hidden md:inline">·</span>

                        <span className="hidden md:inline">
                            {t('Responsible AI governance')}
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
