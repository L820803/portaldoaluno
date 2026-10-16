import { Head, useForm } from '@inertiajs/react';
import { ChevronDown, LoaderCircle, Sparkles } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Notice } from '@/components/portal/notice';
import { PasswordInput } from '@/components/portal/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

type LoginForm = {
    login: string;
    password: string;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

export default function Login({ status, canResetPassword }: LoginProps) {
    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        login: '',
        password: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title="Entrar no portal" description="Use o código do aluno informado pela escola e a sua senha.">
            <Head title="Entrar" />

            {status && (
                <Notice tone="success" className="mb-6">
                    {status}
                </Notice>
            )}

            <form className="flex flex-col gap-5" onSubmit={submit}>
                <div className="grid gap-2">
                    <Label htmlFor="login">Código do aluno</Label>
                    <Input
                        id="login"
                        type="text"
                        required
                        autoFocus
                        autoComplete="username"
                        autoCapitalize="none"
                        spellCheck={false}
                        className="h-11"
                        value={data.login}
                        onChange={(e) => setData('login', e.target.value)}
                        placeholder="Somente números"
                        aria-invalid={!!errors.login}
                    />
                    <InputError message={errors.login} />
                </div>

                <div className="grid gap-2">
                    <div className="flex items-center">
                        <Label htmlFor="password">Senha</Label>
                        {canResetPassword && (
                            <TextLink href={route('password.request')} className="ml-auto text-sm">
                                Esqueci minha senha
                            </TextLink>
                        )}
                    </div>
                    <PasswordInput
                        id="password"
                        required
                        autoComplete="current-password"
                        className="h-11"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        aria-invalid={!!errors.password}
                    />
                    <InputError message={errors.password} />
                </div>

                <Button type="submit" size="lg" className="mt-2 h-11 w-full text-base" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Entrar
                </Button>
            </form>

            <details className="group bg-muted/50 mt-8 rounded-xl border px-4 py-3 text-sm">
                <summary className="flex cursor-pointer list-none items-center gap-2 font-semibold [&::-webkit-details-marker]:hidden">
                    <Sparkles className="text-primary size-4" />
                    Primeiro acesso?
                    <ChevronDown className="text-muted-foreground ml-auto size-4 transition-transform group-open:rotate-180" />
                </summary>
                <ol className="text-muted-foreground mt-3 list-decimal space-y-1.5 pl-5">
                    <li>Digite o código do aluno nos dois campos: código e senha.</li>
                    <li>Confirme sua data de nascimento e crie uma senha só sua.</li>
                    <li>Informe um e-mail para recuperar a senha quando precisar.</li>
                </ol>
            </details>

            <p className="text-muted-foreground mt-6 text-center text-sm">Problemas para acessar? Procure a secretaria da sua escola.</p>
        </AuthLayout>
    );
}
