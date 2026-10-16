import { Head, useForm, usePage } from '@inertiajs/react';
import { LoaderCircle, MailCheck } from 'lucide-react';
import { FormEventHandler } from 'react';

import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { InfoGrid } from '@/components/portal/info-grid';
import { Notice } from '@/components/portal/notice';
import { StatusBadge } from '@/components/portal/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { titleCase } from '@/lib/format';
import { type SharedData } from '@/types';

interface ProfileProps {
    login: string;
    email: string | null;
    emailConfirmado: boolean;
    emailPendente: string | null;
}

export default function Profile({ login, email, emailConfirmado, emailPendente }: ProfileProps) {
    const { auth, aluno } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing } = useForm({
        email: emailPendente ?? email ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('profile.update'), { preserveScroll: true });
    };

    const mesmoEmail = data.email.trim().toLowerCase() === (email ?? '').toLowerCase();

    return (
        <AppLayout>
            <Head title="Perfil" />

            <SettingsLayout>
                <div className="space-y-5">
                    <HeadingSmall title="Seus dados" description="Vêm do cadastro da escola. Para corrigir, procure a secretaria." />
                    <InfoGrid
                        columns={2}
                        items={[
                            { label: 'Nome', value: titleCase(aluno?.nome ?? auth.user.name) },
                            { label: 'Código do aluno', value: login },
                        ]}
                    />
                </div>

                <div className="space-y-5">
                    <HeadingSmall title="E-mail de contato" description="Usado para recuperar sua senha. Pode ser o e-mail de um responsável." />

                    <div className="flex flex-wrap items-center gap-2 text-sm">
                        <span className="font-medium break-all">{email ?? 'Nenhum e-mail cadastrado'}</span>
                        {email && (
                            <StatusBadge tone={emailConfirmado ? 'success' : 'warning'} dot>
                                {emailConfirmado ? 'Confirmado' : 'Não confirmado'}
                            </StatusBadge>
                        )}
                    </div>

                    {emailPendente && (
                        <Notice tone="info" icon={MailCheck} title="Aguardando confirmação">
                            Enviamos um link para <strong className="font-semibold break-all">{emailPendente}</strong>. O e-mail só muda depois que
                            você clicar nele.
                        </Notice>
                    )}

                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="email">{email ? 'Novo e-mail' : 'E-mail'}</Label>
                            <Input
                                id="email"
                                type="email"
                                inputMode="email"
                                autoComplete="email"
                                required
                                value={data.email}
                                onChange={(e) => setData('email', e.target.value)}
                                placeholder="seuemail@exemplo.com"
                                aria-invalid={!!errors.email}
                            />
                            <InputError message={errors.email} />
                        </div>

                        <Button disabled={processing}>
                            {processing && <LoaderCircle className="size-4 animate-spin" />}
                            {mesmoEmail && !emailConfirmado ? 'Confirmar este e-mail' : 'Enviar link de confirmação'}
                        </Button>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
