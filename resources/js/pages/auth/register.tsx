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
        'border-b-[#8d8d8d] bg-[#f4f4f4] px-4 text-sm ' +
        'text-[#161616] shadow-none placeholder:text-[#8c8c8c] ' +
        'focus:outline-2 focus:-outline-offset-2 ' +
        'focus:outline-[#0f62fe] focus:border-b-[#0f62fe]';

    return (
        <>
            <Head title={t('Create an account')}>
                <link
                    rel="stylesheet"
                    href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600&display=swap"
                />
            </Head>

            <div
                className="flex min-h-svh w-full flex-col bg-white text-[#161616]"
                style={{
                    fontFamily: "'IBM Plex Sans', sans-serif",
                    letterSpacing: '0.16px',
                }}
            >
                {/* Cabeçalho institucional */}
                <header className="flex h-12 w-full shrink-0 items-center justify-between border-b border-[#e0e0e0] bg-white px-6">
                    <Link
                        href={home()}
                        className="flex items-center gap-2 hover:opacity-75"
                    >
                        <span className="text-xs font-semibold tracking-widest">
                            PUCPR
                        </span>
                        <span className="text-[#e0e0e0]"> | </span>
                        <span className="text-xs text-[#525252]">
                            Framework RME
                        </span>
                    </Link>

                    <span className="hidden text-xs text-[#8c8c8c] sm:block">
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
                            <p className="text-sm leading-5 text-[#525252]">
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
                                            className="mb-2 text-xs text-[#525252]"
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
                                            className="mb-2 text-xs text-[#525252]"
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
                                            className="mb-2 text-xs text-[#525252]"
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
                                                className="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-[#525252] hover:text-[#161616] focus-visible:outline-2 focus-visible:outline-[#0f62fe]"
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
                                            className="mb-2 text-xs text-[#525252]"
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
                                                className="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-[#525252] hover:text-[#161616] focus-visible:outline-2 focus-visible:outline-[#0f62fe]"
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
                                            className="flex h-12 w-full items-center justify-between bg-[#0f62fe] px-4 text-sm text-white transition-colors hover:bg-[#0353e9] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0f62fe] active:bg-[#002d9c] disabled:cursor-not-allowed disabled:opacity-60"
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
                            <span className="text-[#525252]">
                                {t('Already have an account?')}
                            </span>

                            <Link
                                href={login()}
                                className="ml-1 text-[#0f62fe] hover:text-[#0353e9] hover:underline"
                            >
                                {t('Log in')}
                            </Link>
                        </div>
                    </section>
                </main>

                {/* Rodapé institucional */}
                <footer className="w-full border-t border-[#e0e0e0] bg-[#f4f4f4] px-6 py-6 text-xs text-[#525252]">
                    <div className="mx-auto flex max-w-4xl flex-col items-center justify-between gap-2 sm:flex-row">
                        <div className="flex items-center gap-2">
                            <span className="font-semibold text-[#161616]">
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
