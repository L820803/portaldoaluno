import { Head, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle, MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/components/input-error';
import { Notice } from '@/components/portal/notice';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';

export default function ForgotPassword({ status }: { status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        login: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('password.email'));
    };

    return (
        <AuthLayout title="Esqueci minha senha" description="Informe o código do aluno. Enviaremos um link para o e-mail cadastrado.">
            <Head title="Esqueci minha senha" />

            {status && (
                <Notice tone="success" icon={MailCheck} title="Confira seu e-mail" className="mb-6">
                    {status} Se não encontrar, veja também a pasta de spam.
                </Notice>
            )}

            <form onSubmit={submit} className="flex flex-col gap-5">
                <div className="grid gap-2">
                    <Label htmlFor="login">Código do aluno</Label>
                    <Input
                        id="login"
                        type="text"
                        inputMode="numeric"
                        autoComplete="username"
                        autoFocus
                        required
                        className="h-11"
                        value={data.login}
                        onChange={(e) => setData('login', e.target.value)}
                        placeholder="Somente números"
                        aria-invalid={!!errors.login}
                    />
                    <InputError message={errors.login} />
                </div>

                <Button size="lg" className="h-11 w-full text-base" disabled={processing}>
                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                    Enviar link
                </Button>
            </form>

            <p className="mt-8 text-center text-sm">
                <TextLink href={route('login')} className="inline-flex items-center gap-1.5">
                    <ArrowLeft className="size-4" />
                    Voltar para o login
                </TextLink>
            </p>
        </AuthLayout>
    );
}
