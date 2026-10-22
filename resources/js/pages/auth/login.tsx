import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowRight, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';
import { useTranslations } from '@/hooks/use-translations';

import InputError from '@/components/input-error';
import { Spinner } from '@/components/ui/spinner';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    const [showPassword, setShowPassword] = useState(false);

    setLayoutProps({
        title: '',
        description: '',
    });

    const inputClass =
        'block h-12 w-full rounded-none border-0 ' +
        'border-b border-input bg-field ' +
        'px-4 text-sm text-foreground shadow-none ' +
        'placeholder:text-subtle-foreground ' +
        'focus:outline-2 focus:-outline-offset-2 ' +
        'focus:outline-ring ' +
        'focus:border-b-2 focus:border-b-primary';

    const { t } = useTranslations();

    return (
        <>
            <Head title={t('Log in')}></Head>

            <div className="bg-background text-foreground flex min-h-svh w-full flex-col">
                <main className="flex w-full flex-1 items-center justify-center px-4 py-12">
                    <div className="mx-auto w-full max-w-[420px]">
                        {/* Logo e título */}
                        <div className="mb-8 flex flex-col items-center">
                            <Link
                                href={home()}
                                aria-label={t('Back to the home page')}
                                className="focus-visible:outline-ring mb-6 inline-flex h-12 w-12 items-center justify-center focus-visible:outline-2"
                            >
                                <img
                                    src="/favicon.svg"
                                    alt="Framework RME"
                                    className="h-12 w-12 object-contain"
                                />
                            </Link>

                            <h1 className="text-center">
                                {t('Log in to your account')}
                            </h1>

                            <p className="text-muted-foreground mt-2 text-center text-sm leading-relaxed">
                                {t(
                                    'Enter your email and password below to log in',
                                )}
                            </p>
                        </div>

                        {/* Mensagem do Laravel */}
                        {status && (
                            <div
                                role="status"
                                className="mb-6 border-l-2 border-green-600 bg-green-50 p-3 text-sm text-green-800"
                            >
                                {status}
                            </div>
                        )}

                        {/* Formulário de autenticação */}
                        <Form
                            {...store.form()}
                            resetOnSuccess={['password']}
                            className="space-y-6"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {/* E-mail */}
                                    <div>
                                        <label
                                            htmlFor="email"
                                            className="text-muted-foreground mb-2 block text-xs"
                                        >
                                            {t('Email address')}
                                        </label>

                                        <input
                                            id="email"
                                            name="email"
                                            type="email"
                                            required
                                            autoFocus
                                            autoComplete="email"
                                            placeholder="email@example.com"
                                            className={inputClass}
                                            aria-invalid={Boolean(errors.email)}
                                        />

                                        <InputError message={errors.email} />
                                    </div>

                                    {/* Senha */}
                                    <div>
                                        <div className="mb-2 flex items-center justify-between gap-3">
                                            <label
                                                htmlFor="password"
                                                className="text-muted-foreground text-xs"
                                            >
                                                {t('Password')}
                                            </label>

                                            {canResetPassword && (
                                                <Link
                                                    href={request.url()}
                                                    className="text-link focus-visible:outline-ring text-xs hover:underline focus-visible:outline-2"
                                                >
                                                    {t('Forgot your password?')}
                                                </Link>
                                            )}
                                        </div>

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
                                                autoComplete="current-password"
                                                placeholder={t('Password')}
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
                                                className="text-muted-foreground hover:bg-secondary focus-visible:outline-ring absolute inset-y-0 right-0 flex w-12 items-center justify-center focus-visible:outline-2"
                                            >
                                                {showPassword ? (
                                                    <EyeOff
                                                        size={20}
                                                        aria-hidden="true"
                                                    />
                                                ) : (
                                                    <Eye
                                                        size={20}
                                                        aria-hidden="true"
                                                    />
                                                )}
                                            </button>
                                        </div>

                                        <InputError message={errors.password} />
                                    </div>

                                    {/* Lembrar-me */}
                                    <div className="flex items-center gap-2.5 pt-1">
                                        <input
                                            id="remember"
                                            name="remember"
                                            type="checkbox"
                                            value="1"
                                            className="border-foreground accent-foreground focus-visible:outline-ring h-[18px] w-[18px] cursor-pointer rounded-none border focus-visible:outline-2 focus-visible:outline-offset-2"
                                        />

                                        <label
                                            htmlFor="remember"
                                            className="text-foreground cursor-pointer text-sm"
                                        >
                                            {t('Remember me')}
                                        </label>
                                    </div>

                                    {/* Entrar */}
                                    <div className="pt-2">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            data-test="login-button"
                                            className="bg-primary text-primary-foreground hover:bg-primary-hover focus-visible:outline-ring active:bg-primary flex h-12 w-full items-center justify-between px-4 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <span>
                                                {processing
                                                    ? t('Logging in...')
                                                    : t('Log in')}
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

                        {/* Cadastro */}
                        <div className="mt-8 text-center text-sm">
                            <span>{t("Don't have an account?")}</span>

                            <Link
                                href={register.url()}
                                className="hover:text-link focus-visible:outline-ring ml-1 underline focus-visible:outline-2"
                            >
                                {t('Sign up')}
                            </Link>
                        </div>
                    </div>
                </main>

                {/* Rodapé */}
                <footer className="border-border bg-background w-full border-t py-6 text-center">
                    <p className="text-subtle-foreground px-4 text-xs">
                        {t('Framework RME · Applied research project — PUCPR')}
                    </p>
                </footer>
            </div>
        </>
    );
}
