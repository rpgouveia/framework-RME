
import { Form, Head, Link, setLayoutProps } from '@inertiajs/react';
import { ArrowRight, Eye, EyeOff } from 'lucide-react';
import { useState } from 'react';

import InputError from '@/components/input-error';
import { Spinner } from '@/components/ui/spinner';
import { home, register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({
    status,
    canResetPassword,
}: Props) {
    const [showPassword, setShowPassword] = useState(false);

    setLayoutProps({
        title: '',
        description: '',
    });

    const inputClass =
        'block h-12 w-full rounded-none border-0 ' +
        'border-b border-[#8d8d8d] bg-[#f4f4f4] ' +
        'px-4 text-sm text-[#161616] shadow-none ' +
        'placeholder:text-[#8c8c8c] ' +
        'focus:outline-2 focus:-outline-offset-2 ' +
        'focus:outline-[#0f62fe] ' +
        'focus:border-b-2 focus:border-b-[#0f62fe]';

    return (
        <>
            <Head title="Entrar - Framework RME">
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
                {/* Barra azul do Stitch */}
                <div className="h-1 w-full shrink-0 bg-[#0f62fe]" />

                <main className="flex w-full flex-1 items-center justify-center px-4 py-12">
                    <div className="mx-auto w-full max-w-[420px]">

                        {/* Logo e título */}
                        <div className="mb-8 flex flex-col items-center">
                            <Link
                                href={home()}
                                aria-label="Voltar à página inicial"
                                className="mb-6 inline-flex h-12 w-12 items-center justify-center focus-visible:outline-2 focus-visible:outline-[#0f62fe]"
                            >
                                <img
                                    src="/favicon.svg"
                                    alt="Framework RME"
                                    className="h-12 w-12 object-contain"
                                />
                            </Link>

                            <h1 className="text-center text-[32px] leading-[1.25] font-light">
                                Entre na sua conta
                            </h1>

                            <p className="mt-2 text-center text-sm leading-relaxed text-[#525252]">
                                Digite seu e-mail e senha abaixo para entrar
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
                                            className="mb-2 block text-xs text-[#525252]"
                                        >
                                            Endereço de e-mail
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
                                                className="text-xs text-[#525252]"
                                            >
                                                Senha
                                            </label>

                                            {canResetPassword && (
                                                <Link
                                                    href={request.url()}
                                                    className="text-xs text-[#0f62fe] hover:underline focus-visible:outline-2 focus-visible:outline-[#0f62fe]"
                                                >
                                                    Esqueceu sua senha?
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
                                                placeholder="Senha"
                                                className={`${inputClass} pr-12`}
                                                aria-invalid={Boolean(errors.password)}
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
                                                        ? 'Ocultar senha'
                                                        : 'Mostrar senha'
                                                }
                                                aria-pressed={showPassword}
                                                className="absolute inset-y-0 right-0 flex w-12 items-center justify-center text-[#525252] hover:bg-[#e0e0e0] focus-visible:outline-2 focus-visible:outline-[#0f62fe]"
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
                                            className="h-[18px] w-[18px] cursor-pointer rounded-none border border-[#161616] accent-[#161616] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0f62fe]"
                                        />

                                        <label
                                            htmlFor="remember"
                                            className="cursor-pointer text-sm text-[#161616]"
                                        >
                                            Lembrar-me
                                        </label>
                                    </div>

                                    {/* Entrar */}
                                    <div className="pt-2">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            data-test="login-button"
                                            className="flex h-12 w-full items-center justify-between bg-[#161616] px-4 text-sm text-white transition-colors hover:bg-[#393939] active:bg-[#0f62fe] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0f62fe] disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            <span>
                                                {processing
                                                    ? 'Entrando...'
                                                    : 'Entrar'}
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
                            <span>Não tem uma conta? </span>

                            <Link
                                href={register.url()}
                                className="ml-1 underline hover:text-[#0f62fe] focus-visible:outline-2 focus-visible:outline-[#0f62fe]"
                            >
                                Cadastrar-se
                            </Link>
                        </div>
                    </div>
                </main>

                {/* Rodapé */}
                <footer className="w-full border-t border-[#e0e0e0] bg-white py-6 text-center">
                    <p className="px-4 text-xs text-[#8c8c8c]">
                        Framework RME · Projeto de pesquisa aplicada — PUCPR
                    </p>
                </footer>
            </div>
        </>
    );
}
