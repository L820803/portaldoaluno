import { Head } from '@inertiajs/react';

import AppearanceTabs from '@/components/appearance-tabs';
import HeadingSmall from '@/components/heading-small';

import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';

export default function Appearance() {
    return (
        <AppLayout>
            <Head title="Aparência" />

            <SettingsLayout>
                <div className="space-y-6">
                    <HeadingSmall title="Tema" description="Escolha entre o tema claro, o escuro ou o mesmo do seu aparelho." />
                    <AppearanceTabs />
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
