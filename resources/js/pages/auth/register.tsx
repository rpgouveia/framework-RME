import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowRight, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';

import InputError from '@/components/input-error';
import { Spinner } from '@/components/ui/spinner';
import { home, login } from '@/routes';
import { store } from '@/routes/register';

type Props = {
    passwordRules: string;
};

export default function Register({ passwordRules }: Props) {
    const { t } = useTranslations();
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmation, setShowConfirmation] = useState(false);

    setLayoutProps({
        title: '',
        description: '',
    });

    const inputClass =
        'block h-12 w-full rounded-none border border-transparent ' +
        'border-b-input bg-field px-4 text-sm ' +
        'text-foreground shadow-none placeholder:text-subtle-foreground ' +
        'focus:outline-2 focus:-outline-offset-2 ' +
        'focus:outline-ring focus:border-b-primary';

    return (
        <>
            <Head title={t('Create an account')}></Head>

            <div className="bg-background text-foreground flex min-h-svh w-full flex-col">
                {/* Cabeçalho institucional */}
                <header className="border-border bg-background flex h-12 w-full shrink-0 items-center justify-between border-b px-6">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 hover:opacity-75"
                    >
                        <span className="text-xs font-semibold tracking-widest">
                            PUCPR
                        </span>
                        <span className="text-border"> | </span>
                        <span className="text-muted-foreground text-xs">
                            Framework RME
                        </span>
                    </Link>

                    <span className="text-subtle-foreground hidden text-xs sm:block">
                        {t('Governance & Software Engineering')}
                    </span>
                </header>

                {/* Conteúdo principal */}
                <main className="flex w-full flex-1 items-center justify-center px-4 py-12">
                    <section className="mx-auto flex w-full max-w-[400px] flex-col">
                        {/* Logo original */}
                        <div className="mb-6 flex justify-center">
                            <Link
                                href={home()}
                                aria-label={t('Back to the home page')}
                                className="flex h-14 w-14 items-center justify-center"
                            >
                                <img
                                    src="/favicon.svg"
                                    alt="Framework RME"
                                    className="h-14 w-14 object-contain"
                                />
                            </Link>
                        </div>

                        {/* Título */}
                        <div className="mb-8 text-center">
                            <h1 className="mb-2 text-[32px] leading-10 font-light">
                                {t('Create an account')}
                            </h1>
                            <p className="text-muted-foreground text-sm leading-5">
                                {t(
                                    'Enter your details below to create your account',
                                )}
                            </p>
                        </div>

                        {/* Formulário conectado ao Laravel */}
                        <Form
                            {...store.form()}
                            resetOnSuccess={[
                                'password',
                                'password_confirmation',
                            ]}
                            disableWhileProcessing
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* Nome */}
                                    <div className="flex flex-col">
                                        <label
                                            htmlFor="name"
                                            className="text-muted-foreground mb-2 text-xs"
                                        >
                                            {t('Name')}
                                        </label>

                                        <input
                                            id="name"
                                            name="name"
                                            type="text"
                                            required
                                            autoFocus
                                            autoComplete="name"
                                            placeholder={t('Full name')}
                                            className={inputClass}
                                            aria-invalid={Boolean(errors.name)}
                                        />

                                        <InputError message={errors.name} />
                                    </div>

                                    {/* E-mail */}
                                    <div className="flex flex-col">
                                        <label
                                            htmlFor="email"
                                            className="text-muted-foreground mb-2 text-xs"
                                        >
                                            {t('Email address')}
                                        </label>

                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            required
                                            autoComplete="email"
                                            placeholder="email@example.com"
                                            className={inputClass}
                                            aria-invalid={Boolean(errors.email)}
                                        />

                                        <InputError message={errors.email} />
                                    </div>

                                    {/* Senha */}
                                    <div className="flex flex-col">
                                        <label
                                            htmlFor="password"
                                            className="text-muted-foreground mb-2 text-xs"
                                        >
                                            {t('Password')}
                                        </label>

                                        <div className="relative">
                                            <input
                                                id="password"
                                                name="password"
                                                type={
                                                    showPassword
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                required
                                                autoComplete="new-password"
                                                placeholder={t('Password')}
                                                passwordrules={passwordRules}
                                                className={`${inputClass} pr-12`}
                                                aria-invalid={Boolean(
                                                    errors.password,
                                                )}
                                            />

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setShowPassword(
                                                        (previous) => !previous,
                                                    )
                                                }
                                                aria-label={
                                                    showPassword
                                                        ? t('Hide password')
                                                        : t('Show password')
                                                }
                                                aria-pressed={showPassword}
                                                className="text-muted-foreground hover:text-foreground focus-visible:outline-ring absolute inset-y-0 right-0 flex w-12 items-center justify-center focus-visible:outline-2"
                                            >
                                                {showPassword ? (
                                                    <EyeOff size={20} />
                                                ) : (
                                                    <Eye size={20} />
                                                )}
                                            </button>
                                        </div>

                                        <InputError message={errors.password} />
                                    </div>

                                    {/* Confirmar senha */}
                                    <div className="flex flex-col">
                                        <label
                                            htmlFor="password_confirmation"
                                            className="text-muted-foreground mb-2 text-xs"
                                        >
                                            {t('Confirm password')}
                                        </label>

                                        <div className="relative">
                                            <input
                                                id="password_confirmation"
                                                name="password_confirmation"
                                                type={
                                                    showConfirmation
                                                        ? 'text'
                                                        : 'password'
                                                }
                                                required
                                                autoComplete="new-password"
                                                placeholder={t(
                                                    'Confirm password',
                                                )}
                                                passwordrules={passwordRules}
                                                className={`${inputClass} pr-12`}
                                                aria-invalid={Boolean(
                                                    errors.password_confirmation,
                                                )}
                                            />

                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setShowConfirmation(
                                                        (previous) => !previous,
                                                    )
                                                }
                                                aria-label={
                                                    showConfirmation
                                                        ? t('Hide confirmation')
                                                        : t('Show confirmation')
                                                }
                                                aria-pressed={showConfirmation}
                                                className="text-muted-foreground hover:text-foreground focus-visible:outline-ring absolute inset-y-0 right-0 flex w-12 items-center justify-center focus-visible:outline-2"
                                            >
                                                {showConfirmation ? (
                                                    <EyeOff size={20} />
                                                ) : (
                                                    <Eye size={20} />
                                                )}
                                            </button>
                                        </div>

                                        <InputError
                                            message={
                                                errors.password_confirmation
                                            }
                                        />
                                    </div>

                                    {/* Criar conta */}
                                    <div className="pt-2">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            data-test="register-user-button"
                                            className="bg-primary text-primary-foreground hover:bg-primary-hover focus-visible:outline-ring active:bg-primary-active flex h-12 w-full items-center justify-between px-4 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <span>
                                                {processing
                                                    ? t('Creating account...')
                                                    : t('Create account')}
                                            </span>

                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <ArrowRight
                                                    size={18}
                                                    aria-hidden="true"
                                                />
                                            )}
                                        </button>
                                    </div>
                                </>
                            )}
                        </Form>

                        {/* Voltar ao login */}
                        <div className="mt-8 text-center text-sm">
                            <span className="text-muted-foreground">
                                {t('Already have an account?')}
                            </span>

                            <Link
                                href={login()}
                                className="text-link hover:text-link-hover ml-1 hover:underline"
                            >
                                {t('Log in')}
                            </Link>
                        </div>
                    </section>
                </main>

                {/* Rodapé institucional */}
                <footer className="border-border bg-field text-muted-foreground w-full border-t px-6 py-6 text-xs">
                    <div className="mx-auto flex max-w-4xl flex-col items-center justify-between gap-2 sm:flex-row">
                        <div className="flex items-center gap-2">
                            <span className="text-foreground font-semibold">
                                PUCPR
                            </span>

                            <span>
                                {t(
                                    '— Pontifical Catholic University of Paraná',
                                )}
                            </span>
                        </div>

                        <span>
                            {t('Framework RME © 2025. All rights reserved.')}
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
