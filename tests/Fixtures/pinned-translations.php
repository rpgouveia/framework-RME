<?php

/*
 * The texts the project keeps in its own wording where Laravel Lang has a
 * different one (decision 0023). `php artisan lang:add pt_BR` overwrites every
 * key Laravel Lang publishes, so PinnedTranslationsTest fails until a pinned
 * text is restored, and also when a new departure from Laravel Lang is left
 * out of this list.
 */

return [

    // lang/pt_BR.json
    'json' => [
        // The domain screens (0022), where the screen kept its own wording.
        'Changes' => 'Mudanças',
        'Next' => 'Próxima',
        'Record' => 'Registrar',
        'Search' => 'Buscar',

        // The screens of the starter kit.
        'Dashboard' => 'Acompanhamento',
        'Email password reset link' => 'Enviar link de redefinição de senha',
        'Enter your email and password below to log in' => 'Digite seu e-mail e senha abaixo para entrar',
        'Forgot your password?' => 'Esqueceu sua senha?',
        'Log in to your account' => 'Entre na sua conta',
        'Or, return to' => 'Ou volte para',
        'Please confirm access to your account by entering one of your emergency recovery codes.' => 'Confirme o acesso à sua conta digitando um dos seus códigos de recuperação de emergência.',
        'Register' => 'Cadastrar',
        'Repository' => 'Repositório',
        'Sign up' => 'Cadastrar-se',

        // Two-factor authentication and password screens.
        'Disable 2FA' => 'Desativar 2FA',
        'Done' => 'Concluir',
        'Enable 2FA' => 'Ativar 2FA',
        'Enable two-factor authentication' => 'Ativar autenticação de dois fatores',
        'or, enter the code manually' => 'ou digite o código manualmente',
        'Please enter your new password below' => 'Digite sua nova senha abaixo',
        'Please proceed with caution, this cannot be undone.' => 'Prossiga com cautela, esta ação não pode ser desfeita.',
        'Please verify your email address by clicking on the link we just emailed to you.' => 'Verifique seu endereço de e-mail clicando no link que acabamos de enviar para você.',
        'Recovery codes let you regain access if you lose your 2FA device. Store them in a secure password manager.' => 'Os códigos de recuperação permitem recuperar o acesso caso você perca o dispositivo de 2FA. Guarde-os em um gerenciador de senhas seguro.',
        'Regenerate codes' => 'Gerar novos códigos',
        'Resend verification email' => 'Reenviar o e-mail de verificação',
        'Two-factor authentication enabled' => 'Autenticação de dois fatores ativada',
        'To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app' => 'Para concluir a ativação da autenticação de dois fatores, escaneie o código QR ou digite a chave de configuração no seu aplicativo autenticador',
        'Two-factor authentication is now enabled. Scan the QR code or enter the setup key in your authenticator app.' => 'A autenticação de dois fatores está ativada. Escaneie o código QR ou digite a chave de configuração no seu aplicativo autenticador.',
        'Warning' => 'Atenção',
        'When you enable two-factor authentication, you will be prompted for a secure pin during login. This pin can be retrieved from a TOTP-supported application on your phone.' => 'Ao ativar a autenticação de dois fatores, será solicitado um PIN seguro ao entrar. Esse PIN pode ser obtido em um aplicativo compatível com TOTP no seu telefone.',
        'You will be prompted for a secure, random pin during login, which you can retrieve from the TOTP-supported application on your phone.' => 'Ao entrar, será solicitado um PIN seguro e aleatório, que você pode obter no aplicativo compatível com TOTP do seu telefone.',
    ],

    // lang/pt_BR/validation.php: the rules the domain forms use
    'validation' => [
        'date' => 'O campo :attribute deve ser uma data válida.',
        'enum' => 'O valor selecionado em :attribute é inválido.',
        'exists' => 'O valor selecionado em :attribute é inválido.',
        'integer' => 'O campo :attribute deve ser um número inteiro.',
        'max.string' => 'O campo :attribute não pode ter mais de :max caracteres.',
        'required' => 'O campo :attribute é obrigatório.',
        'string' => 'O campo :attribute deve ser um texto.',
    ],

];
