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
        'bg-[#0f62fe] px-5 py-3 text-sm text-white ' +
        'transition-colors hover:bg-[#0353e9] ' +
        'active:bg-[#002d9c] focus-visible:outline-2 ' +
        'focus-visible:outline-offset-2 focus-visible:outline-[#0f62fe]';

    const secondaryButton =
        'inline-flex min-h-11 items-center justify-center ' +
        'border border-[#e0e0e0] bg-[#f4f4f4] px-5 py-3 ' +
        'text-sm text-[#161616] transition-colors ' +
        'hover:bg-[#e0e0e0] focus-visible:outline-2 ' +
        'focus-visible:outline-[#0f62fe]';

    return (
        <>
            <Head title={t('Welcome')}>
                <link
                    rel="stylesheet"
                    href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&display=swap"
                />
            </Head>

            <div
                className="flex min-h-svh flex-col bg-white text-[#161616]"
                style={{
                    fontFamily: "'IBM Plex Sans', sans-serif",
                    letterSpacing: '0.16px',
                }}
            >
                {/* Cabeçalho */}
                <header className="sticky top-0 z-50 w-full border-b border-[#e0e0e0] bg-white">
                    <div className="mx-auto flex h-12 w-full max-w-[1312px] items-center justify-between gap-4 px-4 sm:px-8">
                        <Link
                            href={home()}
                            className="flex items-center gap-2.5 transition-opacity hover:opacity-80"
                            aria-label="Framework RME - Página inicial"
                        >
                            <AppLogoIcon className="h-7 w-7 shrink-0" />

                            <span className="text-[15px] font-medium text-[#161616]">
                                {name || 'Framework RME'}
                            </span>
                        </Link>

                        <nav
                            aria-label="Navegação de acesso"
                            className="flex items-center gap-1 sm:gap-2"
                        >
                            {auth.user ? (
                                <Link
                                    href={dashboard()}
                                    prefetch
                                    className="inline-flex min-h-9 items-center bg-[#161616] px-4 text-sm text-white hover:bg-[#393939]"
                                >
                                    {t('Dashboard')}
                                </Link>
                            ) : (
                                <>
                                    <Link
                                        href={login()}
                                        className="inline-flex min-h-9 items-center px-4 text-sm text-[#161616] hover:bg-[#e8e8e8]"
                                    >
                                        {t('Log in')}
                                    </Link>

                                    <Link
                                        href={register()}
                                        className="inline-flex min-h-9 items-center border border-[#161616] bg-[#161616] px-4 text-sm text-white hover:bg-[#393939]"
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
                            <div className="mb-8 flex h-16 w-16 items-center justify-center bg-[#161616] p-3">
                                <AppLogoIcon className="h-10 w-10 text-white" />
                            </div>

                            <h1
                                id="hero-title"
                                className="mb-6 text-3xl leading-[1.2] font-light tracking-normal text-[#161616] sm:text-4xl md:text-[42px]"
                            >
                                {t('Risk management for AI systems')}
                            </h1>

                            <div className="mb-8 max-w-3xl space-y-4 text-base leading-[1.5] text-[#525252]">
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
                        <hr className="border-t border-[#e0e0e0]" />
                    </div>

                    {/* Funcionalidades */}
                    <section
                        aria-labelledby="features-title"
                        className="mx-auto w-full max-w-[1312px] px-4 pt-10 pb-20 sm:px-8"
                    >
                        <h2
                            id="features-title"
                            className="mb-8 text-sm font-normal text-[#525252]"
                        >
                            {t('What the framework keeps')}
                        </h2>

                        <ul className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {sections.map(({ icon: Icon, title, summary }) => (
                                <li
                                    key={title}
                                    className="flex flex-col border border-[#e0e0e0] bg-white p-6 transition-colors duration-150 hover:border-[#c6c6c6] hover:bg-[#f4f4f4]"
                                >
                                    <Icon
                                        className="mb-4 h-6 w-6 text-[#161616]"
                                        strokeWidth={1.5}
                                        aria-hidden="true"
                                    />

                                    <h3 className="mb-2 text-base font-semibold text-[#161616]">
                                        {title}
                                    </h3>

                                    <p className="text-sm leading-[1.45] text-[#525252]">
                                        {summary}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>
                </main>

                {/* Rodapé */}
                <footer className="mt-auto w-full border-t border-[#e0e0e0] bg-white py-8">
                    <div className="mx-auto flex w-full max-w-[1312px] flex-col items-start justify-between gap-4 px-4 text-xs text-[#525252] sm:flex-row sm:items-center sm:px-8">
                        <span>
                            {t(
                                'Conformidade com EU AI Act & MIT AI Risk Repository',
                            )}
                        </span>

                        <span className="hidden md:inline">{t('·')}</span>

                        <span className="hidden md:inline">
                            {t('Governança Responsável de IA')}
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
