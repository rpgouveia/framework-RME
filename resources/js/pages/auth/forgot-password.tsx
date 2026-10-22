import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useTranslations } from '@/hooks/use-translations';

import InputError from '@/components/input-error';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';

type Props = {
    status?: string;
};

export default function ForgotPassword({ status }: Props) {
    const { t } = useTranslations();

    setLayoutProps({
        title: '',
        description: '',
    });

    const inputClass =
        'h-12 w-full rounded-none border-0 border-b ' +
        'border-input bg-field px-4 text-sm ' +
        'text-foreground shadow-none ' +
        'placeholder:text-subtle-foreground ' +
        'focus:outline-2 focus:-outline-offset-2 ' +
        'focus:outline-ring ' +
        'focus:border-b-2 focus:border-b-primary';

    return (
        <>
            <Head title={t('Forgot password')}></Head>

            <div className="bg-background text-foreground flex min-h-svh w-full flex-col">
                {/* Conteúdo principal */}
                <main className="flex w-full flex-1 items-center justify-center px-4 py-12 sm:px-6">
                    <div className="flex w-full max-w-[400px] flex-col items-center">
                        {/* Logo */}
                        <div className="mb-6 flex items-center justify-center">
                            <Link
                                href={login()}
                                aria-label={t('Back to log in')}
                                className="focus-visible:outline-ring flex h-12 w-12 items-center justify-center focus-visible:outline-2"
                            >
                                <img
                                    src="/favicon.svg"
                                    alt="Framework RME"
                                    className="h-12 w-12 object-contain"
                                />
                            </Link>
                        </div>

                        {/* Título e descrição */}
                        <div className="mb-8 w-full text-center">
                            <h1 className="mb-2 text-[32px] leading-[1.25] font-light tracking-tight">
                                {t('Forgot password')}
                            </h1>

                            <p className="text-muted-foreground mx-auto max-w-[340px] text-sm leading-[1.4]">
                                {t(
                                    'Enter your email to receive a password reset link',
                                )}
                            </p>
                        </div>

                        {/* Mensagem do Laravel */}
                        {status && (
                            <div
                                role="status"
                                className="mb-6 w-full border-l-2 border-green-600 bg-green-50 p-3 text-sm text-green-800"
                            >
                                {status}
                            </div>
                        )}

                        {/* Formulário */}
                        <Form {...email.form()} className="w-full space-y-6">
                            {({ processing, errors }) => (
                                <>
                                    <div className="space-y-2">
                                        <label
                                            htmlFor="email"
                                            className="text-muted-foreground block text-xs"
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

                                    <div>
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            data-test="email-password-reset-link"
                                            className="bg-primary text-primary-foreground hover:bg-primary-hover focus-visible:outline-ring flex h-12 w-full items-center justify-between px-4 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 active:bg-black disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <span>
                                                {processing
                                                    ? t('Sending...')
                                                    : t(
                                                          'Email password reset link',
                                                      )}
                                            </span>

                                            {processing ? (
                                                <Spinner />
                                            ) : (
                                                <ArrowRight
                                                    size={18}
                                                    aria-hidden="true"
                                                    className="shrink-0"
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
                                {t('Or, return to')}
                            </span>

                            <Link
                                href={login()}
                                className="text-link hover:text-link-hover focus-visible:outline-ring ml-1 hover:underline focus-visible:outline-2"
                            >
                                {t('log in')}
                            </Link>
                        </div>
                    </div>
                </main>

                {/* Rodapé institucional */}
                <footer className="border-border bg-background w-full border-t py-6">
                    <div className="text-muted-foreground mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 text-xs sm:flex-row">
                        <div className="flex items-center gap-2">
                            <span className="text-foreground font-semibold">
                                PUCPR
                            </span>

                            <span className="text-subtle-foreground"> | </span>

                            <span>
                                {t(
                                    'Framework RME • Risk and Maturity Governance',
                                )}
                            </span>
                        </div>

                        <span className="text-subtle-foreground text-center sm:text-right">
                            {t(
                                '© Pontifical Catholic University of Paraná. All rights reserved.',
                            )}
                        </span>
                    </div>
                </footer>
            </div>
        </>
    );
}
