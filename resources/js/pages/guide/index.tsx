import { Head } from '@inertiajs/react';
import {
    ArrowRight,
    Banknote,
    Bell,
    CheckCircle,
    Clock,
    Download,
    FileText,
    LayoutGrid,
    Pencil,
    Plus,
    Search,
    Settings,
    Upload,
    User,
    XCircle,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Guia de Usuario', href: '/guide' },
];

function SectionTitle({ children }: { children: React.ReactNode }) {
    return (
        <h3 className="flex items-center gap-2 text-base font-semibold text-foreground">
            {children}
        </h3>
    );
}

function Step({ number, children }: { number: number; children: React.ReactNode }) {
    return (
        <div className="flex gap-3">
            <span className="flex size-6 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground">
                {number}
            </span>
            <p className="text-sm leading-relaxed text-muted-foreground">{children}</p>
        </div>
    );
}

function StatusBadgeGuide({ label, color }: { label: string; color: string }) {
    return (
        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${color}`}>
            {label}
        </span>
    );
}

function FlowArrow() {
    return <ArrowRight className="mx-1 inline size-4 text-muted-foreground" />;
}

function PaymentRequestsGuide() {
    return (
        <div className="space-y-6">
            {/* Crear solicitud */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Plus className="size-5 text-primary" />
                            Crear una Solicitud de Pago
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <Step number={1}>
                        Desde el panel de <strong>Solicitudes de Pago</strong>, haz clic en el boton <strong>Nueva</strong> ubicado en la esquina superior derecha.
                    </Step>
                    <Step number={2}>
                        Completa los campos del formulario: <strong>Razon Social</strong> (proveedor), <strong>RFC</strong>, <strong>Folio Factura</strong>, <strong>Moneda</strong>, <strong>Sucursal</strong>, <strong>Concepto de Gasto</strong> y <strong>Descripcion</strong> (opcional).
                    </Step>
                    <Step number={3}>
                        Selecciona el <strong>Tipo de Pago</strong>. Segun el tipo seleccionado se habilitaran los campos de documentos: PDF/XML de factura y/o documentos adicionales.
                    </Step>
                    <Step number={4}>
                        Ingresa los montos: <strong>Subtotal</strong> (el IVA y Total se calculan automaticamente). Marca <strong>Retencion</strong> si aplica.
                    </Step>
                    <Step number={5}>
                        <strong>Adjunta los documentos</strong> requeridos segun el tipo de pago (facturas en PDF/XML, documentos de soporte).
                    </Step>
                    <Step number={6}>
                        Haz clic en <strong>Enviar Solicitud</strong>. El sistema asignara automaticamente el primer autorizador y le enviara una notificacion por correo.
                    </Step>
                </CardContent>
            </Card>

            {/* Consultar solicitudes */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Search className="size-5 text-primary" />
                            Consultar Solicitudes
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        La pantalla principal muestra un <strong>panel dividido</strong>: a la izquierda la lista de solicitudes y a la derecha el detalle de la solicitud seleccionada.
                    </p>
                    <div className="space-y-2">
                        <p className="text-sm font-medium text-foreground">Filtros disponibles:</p>
                        <ul className="space-y-1.5 text-sm text-muted-foreground">
                            <li className="flex items-center gap-2">
                                <Clock className="size-4 text-yellow-500" />
                                <strong>Pendientes:</strong> Solicitudes en proceso de autorizacion.
                            </li>
                            <li className="flex items-center gap-2">
                                <CheckCircle className="size-4 text-green-500" />
                                <strong>Finalizados:</strong> Solicitudes que completaron todas las aprobaciones.
                            </li>
                            <li className="flex items-center gap-2">
                                <FileText className="size-4 text-muted-foreground" />
                                <strong>Todos:</strong> Todas las solicitudes sin filtro.
                            </li>
                        </ul>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        Usa la <strong>barra de busqueda</strong> para encontrar solicitudes por razon social, folio factura o numero de folio.
                    </p>
                </CardContent>
            </Card>

            {/* Flujo de aprobacion */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <CheckCircle className="size-5 text-primary" />
                            Flujo de Aprobacion
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Cada solicitud de pago pasa por <strong>3 etapas de autorizacion</strong>. Cada etapa puede tener hasta 2 niveles de autorizacion.
                    </p>

                    {/* Diagrama de flujo */}
                    <div className="flex flex-wrap items-center justify-center gap-2 rounded-lg border border-border bg-muted/30 px-4 py-6">
                        <div className="text-center">
                            <StatusBadgeGuide label="Departamento" color="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400" />
                            <p className="mt-1 text-[10px] text-muted-foreground">Nivel 1 y 2</p>
                        </div>
                        <FlowArrow />
                        <div className="text-center">
                            <StatusBadgeGuide label="Administracion" color="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400" />
                            <p className="mt-1 text-[10px] text-muted-foreground">Nivel 1 y 2</p>
                        </div>
                        <FlowArrow />
                        <div className="text-center">
                            <StatusBadgeGuide label="Tesoreria" color="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400" />
                            <p className="mt-1 text-[10px] text-muted-foreground">Nivel 1</p>
                        </div>
                        <FlowArrow />
                        <div className="text-center">
                            <StatusBadgeGuide label="Completado" color="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400" />
                        </div>
                    </div>

                    <div className="space-y-2 text-sm text-muted-foreground">
                        <p>
                            <strong>Nivel 1:</strong> El primer autorizador del departamento correspondiente revisa y aprueba.
                        </p>
                        <p>
                            <strong>Nivel 2:</strong> Si el departamento tiene un segundo autorizador, este debe aprobar tambien antes de pasar a la siguiente etapa.
                        </p>
                        <p>
                            Si el <strong>Nivel 2 rechaza</strong>, la solicitud regresa al Nivel 1 de la misma etapa para revision.
                        </p>
                    </div>
                </CardContent>
            </Card>

            {/* Aprobar / Rechazar */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <CheckCircle className="size-5 text-green-500" />
                            Aprobar o Rechazar una Solicitud
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm font-medium text-foreground">Desde el panel web:</p>
                    <Step number={1}>
                        Selecciona la solicitud pendiente en la lista. Si tienes permisos de autorizacion, veras los botones <strong>Autorizar</strong> (verde) y <strong>Rechazar</strong> (rojo).
                    </Step>
                    <Step number={2}>
                        Al aprobar en la etapa de <strong>Administracion</strong> puedes capturar el Folio SAP Factura Proveedores. En la etapa de <strong>Tesoreria</strong> puedes capturar el Folio SAP Pago Efectuado.
                    </Step>
                    <Step number={3}>
                        Al rechazar, debes escribir un <strong>motivo</strong> (minimo 10 caracteres). Se notificara al solicitante.
                    </Step>

                    <div className="mt-4 rounded-lg border border-border bg-muted/30 p-3">
                        <p className="text-sm font-medium text-foreground">Desde el correo electronico:</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Cada notificacion incluye un boton <strong>Autorizar / Rechazar Solicitud</strong> que abre una pagina web donde puedes realizar la accion directamente. Este enlace es <strong>valido por 48 horas</strong>.
                        </p>
                    </div>
                </CardContent>
            </Card>

            {/* Folios SAP */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Settings className="size-5 text-primary" />
                            Folios SAP
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 text-sm text-muted-foreground">
                    <p>Los folios SAP se pueden capturar en dos momentos:</p>
                    <ul className="list-inside list-disc space-y-1">
                        <li><strong>Folio SAP Factura Proveedores:</strong> Al aprobar en la etapa de Administracion o editandolo despues desde el detalle de la solicitud.</li>
                        <li><strong>Folio SAP Pago Efectuado:</strong> Al aprobar en la etapa de Tesoreria o editandolo despues desde el detalle de la solicitud.</li>
                    </ul>
                    <p>Solo los autorizadores que aprobaron en la etapa correspondiente pueden editar estos campos.</p>
                </CardContent>
            </Card>

            {/* Descargar PDF */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Download className="size-5 text-primary" />
                            Descargar PDF
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Una vez que la solicitud alcanza el estado <StatusBadgeGuide label="Completado" color="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400" />, aparece un boton <strong>PDF</strong> en el detalle de la solicitud que permite descargar un resumen con toda la informacion y el historial de aprobaciones.
                    </p>
                </CardContent>
            </Card>

            {/* Editar solicitud */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Pencil className="size-5 text-primary" />
                            Editar una Solicitud
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Solo es posible editar una solicitud mientras se encuentra en estado <StatusBadgeGuide label="Pendiente Departamento" color="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400" />. Una vez que el primer autorizador aprueba, la solicitud ya no puede modificarse.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}

function InvestmentRequestsGuide() {
    return (
        <div className="space-y-6">
            {/* Crear solicitud */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Plus className="size-5 text-primary" />
                            Crear una Solicitud de Inversion
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <Step number={1}>
                        Desde el panel de <strong>Solicitudes de Inversion</strong>, haz clic en el boton <strong>Nueva</strong>.
                    </Step>
                    <Step number={2}>
                        Completa los campos del formulario: <strong>Razon Social</strong>, <strong>RFC</strong>, <strong>Folio Factura</strong>, <strong>Moneda</strong>, <strong>Sucursal</strong>, <strong>Concepto de Gasto</strong> y <strong>Descripcion</strong> (opcional).
                    </Step>
                    <Step number={3}>
                        Selecciona el <strong>Tipo de Pago</strong> (solo se muestran tipos de pago de inversiones).
                    </Step>
                    <Step number={4}>
                        Ingresa los montos: <strong>Subtotal</strong> (el IVA y Total se calculan automaticamente). Marca <strong>Retencion</strong> si aplica.
                    </Step>
                    <Step number={5}>
                        <strong>Adjunta los documentos</strong> requeridos (facturas PDF/XML y documentos de soporte).
                    </Step>
                    <Step number={6}>
                        Haz clic en <strong>Enviar Solicitud</strong>. El sistema notificara automaticamente al autorizador designado.
                    </Step>
                </CardContent>
            </Card>

            {/* Consultar solicitudes */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Search className="size-5 text-primary" />
                            Consultar Solicitudes
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        Funciona igual que el panel de Solicitudes de Pago: <strong>panel dividido</strong> con lista a la izquierda y detalle a la derecha. Los mismos filtros de <strong>Pendientes</strong>, <strong>Finalizados</strong> y <strong>Todos</strong> estan disponibles.
                    </p>
                </CardContent>
            </Card>

            {/* Flujo de aprobacion */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <CheckCircle className="size-5 text-primary" />
                            Flujo de Aprobacion
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        A diferencia de las solicitudes de pago, las solicitudes de inversion tienen un <strong>flujo simplificado con un unico autorizador</strong>.
                    </p>

                    {/* Diagrama de flujo */}
                    <div className="flex items-center justify-center gap-2 rounded-lg border border-border bg-muted/30 px-4 py-6">
                        <div className="text-center">
                            <StatusBadgeGuide label="Direccion" color="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400" />
                            <p className="mt-1 text-[10px] text-muted-foreground">Autorizador unico</p>
                        </div>
                        <FlowArrow />
                        <div className="text-center">
                            <StatusBadgeGuide label="Completado" color="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400" />
                        </div>
                    </div>

                    <p className="text-sm text-muted-foreground">
                        Cuando el autorizador aprueba la solicitud, esta pasa directamente al estado <strong>Completado</strong>. No hay etapas intermedias.
                    </p>
                </CardContent>
            </Card>

            {/* Aprobar / Rechazar */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <CheckCircle className="size-5 text-green-500" />
                            Aprobar o Rechazar una Solicitud
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm font-medium text-foreground">Desde el panel web:</p>
                    <Step number={1}>
                        Selecciona la solicitud pendiente. Si eres el autorizador designado, veras los botones <strong>Autorizar</strong> y <strong>Rechazar</strong>.
                    </Step>
                    <Step number={2}>
                        Al rechazar, debes escribir un <strong>motivo</strong> (minimo 10 caracteres). Se notificara al solicitante.
                    </Step>

                    <div className="mt-4 rounded-lg border border-border bg-muted/30 p-3">
                        <p className="text-sm font-medium text-foreground">Desde el correo electronico:</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            El correo de notificacion incluye un boton <strong>Autorizar / Rechazar Solicitud</strong> con un enlace <strong>valido por 48 horas</strong>.
                        </p>
                    </div>
                </CardContent>
            </Card>

            {/* Descargar PDF */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Download className="size-5 text-primary" />
                            Descargar PDF
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Disponible una vez que la solicitud alcanza el estado <StatusBadgeGuide label="Completado" color="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400" />. El boton <strong>PDF</strong> aparece en el detalle de la solicitud.
                    </p>
                </CardContent>
            </Card>

            {/* Editar solicitud */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Pencil className="size-5 text-primary" />
                            Editar una Solicitud
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <p className="text-sm text-muted-foreground">
                        Solo es posible editar una solicitud en estado <StatusBadgeGuide label="Pendiente Departamento" color="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400" />. Una vez aprobada por el autorizador, ya no puede modificarse.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}

function GeneralGuide() {
    return (
        <div className="space-y-6">
            {/* Dashboard */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <LayoutGrid className="size-5 text-primary" />
                            Dashboard
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 text-sm text-muted-foreground">
                    <p>
                        El Dashboard muestra un resumen general de la actividad:
                    </p>
                    <ul className="list-inside list-disc space-y-1">
                        <li><strong>Tarjetas de estadisticas:</strong> Total de solicitudes, solicitudes pendientes, completadas y rechazadas.</li>
                        <li><strong>Solicitudes por Aprobar:</strong> Si eres autorizador, veras una seccion con las solicitudes que requieren tu atencion, con acceso directo a cada una.</li>
                    </ul>
                </CardContent>
            </Card>

            {/* Notificaciones */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Bell className="size-5 text-primary" />
                            Notificaciones
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 text-sm text-muted-foreground">
                    <p>El sistema envia notificaciones en dos canales:</p>
                    <ul className="list-inside list-disc space-y-1">
                        <li><strong>Correo electronico:</strong> Se envia al autorizador correspondiente cuando se crea una solicitud, cuando pasa a la siguiente etapa, y al solicitante cuando se completa o rechaza.</li>
                        <li><strong>Panel de notificaciones:</strong> El icono de campana en la esquina superior derecha muestra las notificaciones pendientes. Puedes marcarlas como leidas individualmente o todas a la vez.</li>
                    </ul>
                </CardContent>
            </Card>

            {/* Documentos adjuntos */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Upload className="size-5 text-primary" />
                            Documentos Adjuntos
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 text-sm text-muted-foreground">
                    <p>
                        Los documentos adjuntos (facturas PDF/XML y documentos de soporte) se suben al momento de crear la solicitud. Se pueden visualizar y descargar desde:
                    </p>
                    <ul className="list-inside list-disc space-y-1">
                        <li>El <strong>detalle de la solicitud</strong> en el panel web.</li>
                        <li>Los <strong>enlaces en el correo</strong> de notificacion (enlaces firmados, validos por 48 horas).</li>
                        <li>La <strong>pagina de aprobacion</strong> por correo.</li>
                    </ul>
                </CardContent>
            </Card>

            {/* Perfil */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <User className="size-5 text-primary" />
                            Perfil y Seguridad
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2 text-sm text-muted-foreground">
                    <p>Desde el menu de usuario en la esquina inferior izquierda del sidebar puedes acceder a:</p>
                    <ul className="list-inside list-disc space-y-1">
                        <li><strong>Configuracion:</strong> Actualiza tu nombre y correo electronico.</li>
                        <li><strong>Contrasena:</strong> Cambia tu contrasena actual por una nueva.</li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    );
}

export default function Guide() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Guia de Usuario" />

            <div className="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">
                        Guia de Usuario
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Consulta como utilizar las funcionalidades del sistema de solicitudes.
                    </p>
                </div>

                <Tabs defaultValue="payment-requests">
                    <TabsList className="w-full">
                        <TabsTrigger value="payment-requests">
                            <FileText className="size-4" />
                            Solicitudes de Pago
                        </TabsTrigger>
                        <TabsTrigger value="investment-requests">
                            <Banknote className="size-4" />
                            Solicitudes de Inversion
                        </TabsTrigger>
                        <TabsTrigger value="general">
                            <Settings className="size-4" />
                            General
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="payment-requests">
                        <PaymentRequestsGuide />
                    </TabsContent>

                    <TabsContent value="investment-requests">
                        <InvestmentRequestsGuide />
                    </TabsContent>

                    <TabsContent value="general">
                        <GeneralGuide />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
