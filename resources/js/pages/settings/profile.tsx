import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { edit } from '@/routes/profile';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Configuración del perfil',
        href: edit().url,
    },
];

export default function Profile() {
    const { auth } = usePage().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración del perfil" />

            <h1 className="sr-only">Configuración del Perfil</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Información del perfil"
                        description="Tu nombre y correo electrónico. Para realizar cambios, contacta al administrador."
                    />

                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Nombre</Label>

                            <Input
                                id="name"
                                className="mt-1 block w-full bg-muted"
                                value={auth.user.name}
                                readOnly
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Correo electrónico</Label>

                            <Input
                                id="email"
                                type="email"
                                className="mt-1 block w-full bg-muted"
                                value={auth.user.email}
                                readOnly
                            />
                        </div>
                    </div>
                </div>

            </SettingsLayout>
        </AppLayout>
    );
}
