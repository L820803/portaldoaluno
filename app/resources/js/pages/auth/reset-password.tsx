import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { PasswordInput } from '@/components/portal/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

interface ResetPasswordProps {
    token: string;
    login: string;
}

type ResetPasswordForm = {
    token: string;
    login: string;
    password: string;
    password_confirmation: string;
};

export default function ResetPassword({ token, login }: ResetPasswordProps) {
    const { data, setData, post, processing, errors, reset } = useForm<ResetPasswordForm>({
        token,
        login,
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.store'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <AuthLayout title="Criar nova senha" description={`Código do aluno ${login}. Use pelo menos 8 caracteres.`}>
            <Head title="Criar nova senha" />

            <form onSubmit={submit} className="flex flex-col gap-5">
                <div className="grid gap-2">
                    <Label htmlFor="password">Nova senha</Label>
                    <PasswordInput
                        id="password"
                        autoComplete="new-password"
                        autoFocus
                        required
                        className="h-11"
                        value={data.password}
                        onChange={(e) => setData('password', e.target.value)}
                        aria-invalid={!!errors.password}
                    />
                    <InputError message={errors.password ?? errors.login ?? errors.token} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="password_confirmation">Repita a nova senha</Label>
                    <PasswordInput
                        id="password_confirmation"
                        autoComplete="new-password"
                        required
                        className="h-11"
                        value={data.password_confirmation}
                        onChange={(e) => setData('password_confirmation', e.target.value)}
                    />
                    <InputError message={errors.password_confirmation} />
                </div>

                <Button size="lg" className="h-11 w-full text-base" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Salvar nova senha
                </Button>
            </form>

            <p className="text-muted-foreground mt-8 text-center text-sm">
                Link expirado? <TextLink href={route('password.request')}>Peça um novo</TextLink>
            </p>
        </AuthLayout>
    );
}
