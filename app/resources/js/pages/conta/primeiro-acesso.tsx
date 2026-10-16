import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { Check, LoaderCircle, LogOut, MailCheck, RefreshCw } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import InputError from '@/components/input-error';
import { Notice } from '@/components/portal/notice';
import { PasswordInput } from '@/components/portal/password-input';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { primeiroNome } from '@/lib/format';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';

interface PrimeiroAcessoProps {
    precisaSenha: boolean;
    precisaEmail: boolean;
    emailPendente: string | null;
    pedirNascimento: boolean;
}

const etapas = ['Criar senha', 'Informar e-mail'];

function Etapas({ atual }: { atual: number }) {
    return (
        <ol className="mb-8 flex items-center gap-2" aria-label="Etapas do primeiro acesso">
            {etapas.map((titulo, i) => {
                const feita = i < atual;
                const ativa = i === atual;

                return (
                    <li key={titulo} className="flex flex-1 items-center gap-2" aria-current={ativa ? 'step' : undefined}>
                        <span
                            className={cn(
                                'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-bold',
                                feita && 'bg-success text-white',
                                ativa && 'bg-primary text-primary-foreground',
                                !feita && !ativa && 'bg-muted text-muted-foreground',
                            )}
                        >
                            {feita ? <Check className="size-4" /> : i + 1}
                        </span>
                        <span className={cn('text-sm font-medium', !ativa && 'text-muted-foreground')}>{titulo}</span>
                        {i < etapas.length - 1 && <span aria-hidden className="bg-border ml-1 h-px flex-1" />}
                    </li>
                );
            })}
        </ol>
    );
}

function EtapaSenha({ pedirNascimento }: { pedirNascimento: boolean }) {
    const { data, setData, put, processing, errors, reset } = useForm({
        nascimento: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('primeiro-acesso.senha'), { onError: () => reset('password', 'password_confirmation') });
    };

    return (
        <form onSubmit={submit} className="flex flex-col gap-5">
            {pedirNascimento && (
                <div className="grid gap-2">
                    <Label htmlFor="nascimento">Sua data de nascimento</Label>
                    <Input
                        id="nascimento"
                        type="date"
                        required
                        max={new Date().toISOString().slice(0, 10)}
                        className="h-11"
                        value={data.nascimento}
                        onChange={(e) => setData('nascimento', e.target.value)}
                        aria-invalid={!!errors.nascimento}
                        aria-describedby="nascimento-ajuda"
                    />
                    <p id="nascimento-ajuda" className="text-muted-foreground text-xs">
                        Usamos para confirmar que é você.
                    </p>
                    <InputError message={errors.nascimento} />
                </div>
            )}

            <div className="grid gap-2">
                <Label htmlFor="password">Nova senha</Label>
                <PasswordInput
                    id="password"
                    required
                    autoComplete="new-password"
                    className="h-11"
                    value={data.password}
                    onChange={(e) => setData('password', e.target.value)}
                    aria-invalid={!!errors.password}
                    aria-describedby="senha-ajuda"
                />
                <p id="senha-ajuda" className="text-muted-foreground text-xs">
                    Pelo menos 8 caracteres e diferente do código do aluno.
                </p>
                <InputError message={errors.password} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="password_confirmation">Repita a nova senha</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    autoComplete="new-password"
                    className="h-11"
                    value={data.password_confirmation}
                    onChange={(e) => setData('password_confirmation', e.target.value)}
                />
            </div>

            <Button size="lg" className="mt-1 h-11 w-full text-base" disabled={processing}>
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                Criar senha e continuar
            </Button>
        </form>
    );
}

function EtapaEmail({ emailPendente }: { emailPendente: string | null }) {
    const [editando, setEditando] = useState(!emailPendente);
    const [verificando, setVerificando] = useState(false);
    const { data, setData, post, processing, errors } = useForm({ email: emailPendente ?? '' });

    const enviar: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('primeiro-acesso.email'), { preserveScroll: true, onSuccess: () => setEditando(false) });
    };

    if (emailPendente && !editando) {
        return (
            <div className="space-y-5">
                <Notice tone="info" icon={MailCheck} title="Agora é só confirmar">
                    Enviamos um link para <strong className="font-semibold break-all">{emailPendente}</strong>. Abra o e-mail e toque em{' '}
                    <strong className="font-semibold">Confirmar e-mail</strong>. Se não encontrar, veja a pasta de spam.
                </Notice>

                <Button
                    size="lg"
                    className="h-11 w-full text-base"
                    disabled={verificando}
                    onClick={() => router.reload({ onStart: () => setVerificando(true), onFinish: () => setVerificando(false) })}
                >
                    {verificando ? <LoaderCircle className="size-4 animate-spin" /> : <Check className="size-4" />}
                    Já confirmei
                </Button>

                <div className="grid grid-cols-2 gap-3">
                    <Button variant="outline" disabled={processing} onClick={() => post(route('primeiro-acesso.email'), { preserveScroll: true })}>
                        <RefreshCw className={cn('size-4', processing && 'animate-spin')} />
                        Reenviar
                    </Button>
                    <Button variant="outline" onClick={() => setEditando(true)}>
                        Usar outro e-mail
                    </Button>
                </div>
                <InputError message={errors.email} />
            </div>
        );
    }

    return (
        <form onSubmit={enviar} className="flex flex-col gap-5">
            <div className="grid gap-2">
                <Label htmlFor="email">Seu e-mail</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    autoFocus
                    autoComplete="email"
                    inputMode="email"
                    className="h-11"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder="seuemail@exemplo.com"
                    aria-invalid={!!errors.email}
                    aria-describedby="email-ajuda"
                />
                <p id="email-ajuda" className="text-muted-foreground text-xs">
                    Pode ser o e-mail de um responsável. Ele será usado para recuperar sua senha.
                </p>
                <InputError message={errors.email} />
            </div>

            <Button size="lg" className="h-11 w-full text-base" disabled={processing}>
                {processing && <LoaderCircle className="size-4 animate-spin" />}
                Enviar link de confirmação
            </Button>
        </form>
    );
}

export default function PrimeiroAcesso({ precisaSenha, precisaEmail, emailPendente, pedirNascimento }: PrimeiroAcessoProps) {
    const { auth, aluno } = usePage<SharedData>().props;
    const nome = primeiroNome(aluno?.nome ?? auth.user.name);
    const etapa = precisaSenha ? 0 : 1;

    return (
        <AuthLayout
            title={`Bem-vindo(a), ${nome}!`}
            description={
                precisaSenha
                    ? 'Este é o seu primeiro acesso. Vamos criar uma senha só sua — leva menos de um minuto.'
                    : 'Falta pouco: informe um e-mail para poder recuperar sua senha.'
            }
        >
            <Head title="Primeiro acesso" />

            {(precisaSenha || precisaEmail) && <Etapas atual={etapa} />}

            {precisaSenha ? <EtapaSenha pedirNascimento={pedirNascimento} /> : <EtapaEmail emailPendente={emailPendente} />}

            <p className="mt-8 text-center text-sm">
                <Link
                    href={route('logout')}
                    method="post"
                    as="button"
                    className="text-muted-foreground hover:text-foreground inline-flex items-center gap-1.5"
                >
                    <LogOut className="size-4" />
                    Sair e continuar depois
                </Link>
            </p>
        </AuthLayout>
    );
}
