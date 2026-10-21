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
        'border-[#8d8d8d] bg-[#f4f4f4] px-4 text-sm ' +
        'text-[#161616] shadow-none ' +
        'placeholder:text-[#8c8c8c] ' +
        'focus:outline-2 focus:-outline-offset-2 ' +
        'focus:outline-[#0f62fe] ' +
        'focus:border-b-2 focus:border-b-[#0f62fe]';

    return (
        <>
            <Head title={t('Forgot password')}>
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
                {/* Conteúdo principal */}
                <main className="flex w-full flex-1 items-center justify-center px-4 py-12 sm:px-6">
                    <div className="flex w-full max-w-[400px] flex-col items-center">
                        {/* Logo */}
                        <div className="mb-6 flex items-center justify-center">
                            <Link
                                href={login()}
                                aria-label={t('Back to log in')}
                                className="flex h-12 w-12 items-center justify-center focus-visible:outline-2 focus-visible:outline-[#0f62fe]"
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

                            <p className="mx-auto max-w-[340px] text-sm leading-[1.4] text-[#525252]">
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
                                            className="block text-xs text-[#525252]"
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
                                            className="flex h-12 w-full items-center justify-between bg-[#161616] px-4 text-sm text-white transition-colors hover:bg-[#393939] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0f62fe] active:bg-black disabled:cursor-not-allowed disabled:opacity-60"
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
                            <span className="text-[#525252]">
                                {t('Or, return to')}
                            </span>

                            <Link
                                href={login()}
                                className="ml-1 text-[#0f62fe] hover:text-[#0353e9] hover:underline focus-visible:outline-2 focus-visible:outline-[#0f62fe]"
                            >
                                {t('log in')}
                            </Link>
                        </div>
                    </div>
                </main>

                {/* Rodapé institucional */}
                <footer className="w-full border-t border-[#e0e0e0] bg-white py-6">
                    <div className="mx-auto flex max-w-6xl flex-col items-center justify-between gap-4 px-4 text-xs text-[#525252] sm:flex-row">
                        <div className="flex items-center gap-2">
                            <span className="font-semibold text-[#161616]">
                                PUCPR
                            </span>

                            <span className="text-[#8d8d8d]"> | </span>

                            <span>
                                {t(
                                    'Framework RME • Risk and Maturity Governance',
                                )}
                            </span>
                        </div>

                        <span className="text-center text-[#8c8c8c] sm:text-right">
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
