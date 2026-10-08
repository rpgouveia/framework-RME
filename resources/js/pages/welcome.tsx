import { Head, Link, usePage } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import {
    Bot,
    Link2,
    ScrollText,
    ShieldAlert,
    ShieldCheck,
    TriangleAlert,
} from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';
import { dashboard, login, register } from '@/routes';

export default function Welcome() {
    const { auth, name } = usePage().props;
    const { t } = useTranslations();

    const sections: { icon: LucideIcon; title: string; summary: string }[] = [
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
                'The core of the chain: one mitigation applied to one risk under one accountable owner, proven by evidence and carrying a review date.',
            ),
        },
        {
            icon: TriangleAlert,
            title: t('Adverse events'),
            summary: t(
                'Incidents and near misses on a system, classified by risk subdomain, which send the links they touch back to reassessment.',
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

    return (
        <>
            <Head title={t('Welcome')} />

            <div className="bg-background text-foreground flex min-h-svh flex-col">
                <header className="border-border/70 border-b">
                    <div className="mx-auto flex w-full max-w-5xl items-center justify-between gap-4 px-6 py-4">
                        <Link
                            href={auth.user ? dashboard() : login()}
                            className="flex items-center gap-2.5"
                        >
                            <AppLogoIcon className="size-8 shrink-0" />
                            <span className="text-sm font-semibold tracking-tight">
                                {name}
                            </span>
                        </Link>

                        <nav className="flex items-center gap-2">
                            {auth.user ? (
                                <Button size="sm" asChild>
                                    <Link href={dashboard()} prefetch>
                                        {t('Dashboard')}
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" size="sm" asChild>
                                        <Link href={login()}>
                                            {t('Log in')}
                                        </Link>
                                    </Button>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={register()}>
                                            {t('Sign up')}
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main className="mx-auto w-full max-w-5xl flex-1 px-6">
                    <section className="flex flex-col items-start gap-6 py-14 lg:py-20">
                        <AppLogoIcon className="size-16 shrink-0 lg:size-20" />

                        <div className="space-y-4">
                            <h1 className="text-3xl font-semibold tracking-tight text-balance lg:text-4xl">
                                {t('Risk management for AI systems')}
                            </h1>
                            <p className="text-muted-foreground max-w-2xl text-base leading-relaxed">
                                {t(
                                    'Keep a registry of the AI systems your organization runs, the risks identified for each one, the mitigations applied to those risks, who is accountable for each, and the evidence proving they are in place.',
                                )}
                            </p>
                            <p className="text-muted-foreground max-w-2xl text-base leading-relaxed">
                                {t(
                                    'Review deadlines, adverse events and changes to a system take verified mitigations back to reassessment, and every step lands in an append-only audit trail.',
                                )}
                            </p>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()} prefetch>
                                        {t('Open the dashboard')}
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button asChild>
                                        <Link href={register()}>
                                            {t('Create an account')}
                                        </Link>
                                    </Button>
                                    <Button variant="outline" asChild>
                                        <Link href={login()}>
                                            {t('Log in')}
                                        </Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </section>

                    <section className="border-border/70 border-t py-12">
                        <h2 className="text-muted-foreground mb-6 text-xs font-medium tracking-wider uppercase">
                            {t('What the framework keeps')}
                        </h2>

                        <ul className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {sections.map(({ icon: Icon, title, summary }) => (
                                <li
                                    key={title}
                                    className="bg-card border-border/70 flex flex-col gap-2 rounded-xl border p-5"
                                >
                                    <Icon
                                        className="text-muted-foreground size-5"
                                        aria-hidden="true"
                                    />
                                    <h3 className="text-sm font-semibold">
                                        {title}
                                    </h3>
                                    <p className="text-muted-foreground text-sm leading-relaxed">
                                        {summary}
                                    </p>
                                </li>
                            ))}
                        </ul>
                    </section>
                </main>

                <footer className="border-border/70 border-t">
                    <div className="text-muted-foreground mx-auto w-full max-w-5xl px-6 py-6 text-xs">
                        {t('Applied research project — PUCPR.')}
                    </div>
                </footer>
            </div>
        </>
    );
}
