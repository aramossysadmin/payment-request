import { Head } from '@inertiajs/react';
import {
    ArrowRight,
    Banknote,
    Bell,
    Calendar,
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
    Wallet,
    XCircle,
} from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Guía de Usuario', href: '/guide' },
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
                        Desde el panel de <strong>Solicitudes de Pago</strong>, haz clic en el botón <strong>Nueva</strong> ubicado en la esquina superior derecha.
                    </Step>
                    <Step number={2}>
                        Completa los campos del formulario: <strong>Razón Social</strong> (proveedor), <strong>RFC</strong>, <strong>Folio Factura</strong>, <strong>Moneda</strong>, <strong>Sucursal</strong>, <strong>Concepto de Gasto</strong> y <strong>Descripción</strong> (opcional).
                    </Step>
                    <Step number={3}>
                        Selecciona el <strong>Tipo de Pago</strong>. Según el tipo seleccionado se habilitarán los campos de documentos: PDF/XML de factura y/o documentos adicionales.
                    </Step>
                    <Step number={4}>
                        Ingresa los montos: <strong>Subtotal</strong> (el IVA y Total se calculan automáticamente). Marca <strong>Retención</strong> si aplica.
                    </Step>
                    <Step number={5}>
                        <strong>Adjunta los documentos</strong> requeridos según el tipo de pago (facturas en PDF/XML, documentos de soporte).
                    </Step>
                    <Step number={6}>
                        Haz clic en <strong>Enviar Solicitud</strong>. El sistema asignará automáticamente el primer autorizador y le enviará una notificación por correo.
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
                                <strong>Pendientes:</strong> Solicitudes en proceso de autorización.
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
                        Usa la <strong>barra de búsqueda</strong> para encontrar solicitudes por razón social, folio factura o número de folio.
                    </p>
                </CardContent>
            </Card>

            {/* Flujo de aprobacion */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <CheckCircle className="size-5 text-primary" />
                            Flujo de Aprobación
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        Cada solicitud de pago pasa por <strong>3 etapas de autorización</strong>. Cada etapa puede tener hasta 2 niveles de autorización.
                    </p>

                    {/* Diagrama de flujo */}
                    <div className="flex flex-wrap items-center justify-center gap-2 rounded-lg border border-border bg-muted/30 px-4 py-6">
                        <div className="text-center">
                            <StatusBadgeGuide label="Departamento" color="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400" />
                            <p className="mt-1 text-[10px] text-muted-foreground">Nivel 1 y 2</p>
                        </div>
                        <FlowArrow />
                        <div className="text-center">
                            <StatusBadgeGuide label="Administración" color="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400" />
                            <p className="mt-1 text-[10px] text-muted-foreground">Nivel 1 y 2</p>
                        </div>
                        <FlowArrow />
                        <div className="text-center">
                            <StatusBadgeGuide label="Tesorería" color="bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400" />
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
                            <strong>Nivel 2:</strong> Si el departamento tiene un segundo autorizador, éste debe aprobar también antes de pasar a la siguiente etapa.
                        </p>
                        <p>
                            Si el <strong>Nivel 2 rechaza</strong>, la solicitud regresa al Nivel 1 de la misma etapa para revisión.
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
                        Selecciona la solicitud pendiente en la lista. Si tienes permisos de autorización, verás los botones <strong>Autorizar</strong> (verde) y <strong>Rechazar</strong> (rojo).
                    </Step>
                    <Step number={2}>
                        Al aprobar en la etapa de <strong>Administración</strong> puedes capturar el Folio SAP Factura Proveedores. En la etapa de <strong>Tesorería</strong> puedes capturar el Folio SAP Pago Efectuado.
                    </Step>
                    <Step number={3}>
                        Al rechazar, debes escribir un <strong>motivo</strong> (mínimo 10 caracteres). Se notificará al solicitante.
                    </Step>

                    <div className="mt-4 rounded-lg border border-border bg-muted/30 p-3">
                        <p className="text-sm font-medium text-foreground">Desde el correo electrónico:</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Cada notificación incluye un botón <strong>Autorizar / Rechazar Solicitud</strong> que abre una página web donde puedes realizar la acción directamente. Este enlace es <strong>válido por 48 horas</strong>.
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
                        <li><strong>Folio SAP Factura Proveedores:</strong> Al aprobar en la etapa de Administración o editándolo después desde el detalle de la solicitud.</li>
                        <li><strong>Folio SAP Pago Efectuado:</strong> Al aprobar en la etapa de Tesorería o editándolo después desde el detalle de la solicitud.</li>
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
                        Una vez que la solicitud alcanza el estado <StatusBadgeGuide label="Completado" color="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400" />, aparece un botón <strong>PDF</strong> en el detalle de la solicitud que permite descargar un resumen con toda la información y el historial de aprobaciones.
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
            {/* Crear concepto */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Plus className="size-5 text-primary" />
                            Crear un Concepto de Inversión
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <Step number={1}>
                        Desde el panel de <strong>Presupuestos de Inversión</strong> > <strong>Conceptos</strong>, haz clic en el botón <strong>Nueva</strong>.
                    </Step>
                    <Step number={2}>
                        Completa los campos del formulario: <strong>Razón Social</strong>, <strong>RFC</strong>, <strong>Folio Factura</strong>, <strong>Moneda</strong>, <strong>Proyecto</strong>, <strong>Sucursal</strong>, <strong>Concepto de Gasto</strong> y <strong>Descripción</strong> (opcional).
                    </Step>
                    <Step number={3}>
                        Selecciona el <strong>Tipo de Pago</strong> (solo se muestran tipos de pago de inversiones).
                    </Step>
                    <Step number={4}>
                        Ingresa los montos: <strong>Subtotal</strong> (el IVA y Total se calculan automáticamente). Marca <strong>Retención</strong> si aplica.
                    </Step>
                    <Step number={5}>
                        <strong>Adjunta los documentos</strong> requeridos (facturas PDF/XML y documentos de soporte).
                    </Step>
                    <Step number={6}>
                        Haz clic en <strong>Enviar Solicitud</strong>. El sistema notificará automáticamente al autorizador designado.
                    </Step>
                </CardContent>
            </Card>

            {/* Consultar conceptos */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Search className="size-5 text-primary" />
                            Consultar Conceptos
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        Funciona igual que el panel de Solicitudes de Pago: <strong>panel dividido</strong> con lista a la izquierda y detalle a la derecha. Los mismos filtros de <strong>Pendientes</strong>, <strong>Finalizados</strong> y <strong>Todos</strong> están disponibles.
                    </p>
                    <p className="text-sm text-muted-foreground">
                        Usa la <strong>barra de búsqueda</strong> para encontrar conceptos por razón social, folio factura o número de folio.
                    </p>
                </CardContent>
            </Card>

            {/* Flujo de aprobacion */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <CheckCircle className="size-5 text-primary" />
                            Flujo de Aprobación
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <p className="text-sm text-muted-foreground">
                        A diferencia de las solicitudes de pago, los conceptos de inversión tienen un <strong>flujo simplificado con un único autorizador</strong>.
                    </p>

                    {/* Diagrama de flujo */}
                    <div className="flex items-center justify-center gap-2 rounded-lg border border-border bg-muted/30 px-4 py-6">
                        <div className="text-center">
                            <StatusBadgeGuide label="Dirección" color="bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400" />
                            <p className="mt-1 text-[10px] text-muted-foreground">Autorizador único</p>
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
                        Selecciona la solicitud pendiente. Si eres el autorizador designado, verás los botones <strong>Autorizar</strong> y <strong>Rechazar</strong>.
                    </Step>
                    <Step number={2}>
                        Al rechazar, debes escribir un <strong>motivo</strong> (mínimo 10 caracteres). Se notificará al solicitante.
                    </Step>

                    <div className="mt-4 rounded-lg border border-border bg-muted/30 p-3">
                        <p className="text-sm font-medium text-foreground">Desde el correo electrónico:</p>
                        <p className="mt-1 text-sm text-muted-foreground">
                            El correo de notificación incluye un botón <strong>Autorizar / Rechazar Solicitud</strong> con un enlace <strong>válido por 48 horas</strong>.
                        </p>
                    </div>
                </CardContent>
            </Card>

            {/* Hojas Consolidadas */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <FileText className="size-5 text-primary" />
                            Hojas Consolidadas
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        El panel de <strong>Hojas Consolidadas</strong> agrupa todos los conceptos completados por <strong>concepto de gasto</strong> y <strong>departamento solicitante</strong>.
                    </p>
                    <Step number={1}>
                        Desde <strong>Presupuestos de Inversión</strong> > <strong>Hojas Consolidadas</strong>, visualiza el resumen de conceptos agrupados.
                    </Step>
                    <Step number={2}>
                        Expande cada grupo para ver el detalle de conceptos incluidos, con su subtotal, IVA y total.
                    </Step>
                    <Step number={3}>
                        En cada grupo consolidado aparece un botón <strong>Solicitar Pago</strong> que permite generar una solicitud de pago para todos los conceptos del grupo.
                    </Step>
                </CardContent>
            </Card>

            {/* Solicitar Pago */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Banknote className="size-5 text-primary" />
                            Solicitar Pago
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        Una vez que tienes conceptos completados, puedes generar una <strong>solicitud de pago</strong> basada en estos conceptos.
                    </p>
                    <Step number={1}>
                        Desde el panel de <strong>Hojas Consolidadas</strong>, haz clic en <strong>Solicitar Pago</strong> en el grupo que deseas pagar.
                    </Step>
                    <Step number={2}>
                        Se abrirá un modal con los conceptos del grupo. Selecciona los conceptos que deseas incluir en la solicitud de pago.
                    </Step>
                    <Step number={3}>
                        Haz clic en <strong>Solicitar Pago</strong> para crear la solicitud. Esta se agregará al panel de <strong>Solicitudes de Pago</strong> y seguirá el flujo de aprobación estándar.
                    </Step>
                </CardContent>
            </Card>

            {/* Control Presupuestal */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <LayoutGrid className="size-5 text-primary" />
                            Control Presupuestal
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        El panel de <strong>Control Presupuestal</strong> muestra el estado del presupuesto de inversión.
                    </p>
                    <Step number={1}>
                        Desde <strong>Presupuestos de Inversión</strong> > <strong>Control Presupuestal</strong>, visualiza el presupuesto asignado por concepto y departamento.
                    </Step>
                    <Step number={2}>
                        Para cada concepto se muestra: presupuesto disponible, monto comprometido en conceptos pendientes, y monto ejecutado en conceptos completados.
                    </Step>
                    <Step number={3}>
                        Esta información te ayuda a controlar que no se supere el presupuesto asignado en cada concepto.
                    </Step>
                </CardContent>
            </Card>

            {/* Editar y PDF */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Pencil className="size-5 text-primary" />
                            Editar y Descargar PDF
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm font-medium text-foreground">Editar:</p>
                    <p className="text-sm text-muted-foreground">
                        Solo es posible editar un concepto en estado <StatusBadgeGuide label="Pendiente Departamento" color="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400" />. Una vez aprobado por el autorizador, ya no puede modificarse.
                    </p>
                    <p className="text-sm font-medium text-foreground">Descargar PDF:</p>
                    <p className="text-sm text-muted-foreground">
                        Disponible una vez que el concepto alcanza el estado <StatusBadgeGuide label="Completado" color="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400" />. El botón <strong>PDF</strong> aparece en el detalle del concepto.
                    </p>
                </CardContent>
            </Card>
        </div>
    );
}

function GeneralGuide() {
    return (
        <div className="space-y-6">
            {/* Navegación */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <LayoutGrid className="size-5 text-primary" />
                            Navegación del Sistema
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3 text-sm">
                    <p className="text-muted-foreground">
                        El menú lateral (sidebar) organiza todas las funcionalidades del sistema en las siguientes secciones:
                    </p>
                    <ul className="space-y-2 text-muted-foreground">
                        <li>
                            <strong>Plataforma:</strong> Acceso rápido al Dashboard y principal del sistema.
                        </li>
                        <li>
                            <strong>Solicitudes:</strong> Gestión de Solicitudes de Pago con opciones para crear, consultar y aprobar solicitudes.
                        </li>
                        <li>
                            <strong>Presupuestos de Inversión:</strong> Gestión de Conceptos de Inversión, Hojas Consolidadas, Control Presupuestal y Solicitud de Pagos desde inversiones.
                        </li>
                        <li>
                            <strong>Tesorería:</strong> Programación semanal de pagos, autorización de lotes de pago e historial de ejecuciones.
                        </li>
                        <li>
                            <strong>Documentación:</strong> Acceso a esta guía de usuario.
                        </li>
                    </ul>
                </CardContent>
            </Card>

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
                        <li><strong>Tarjetas de estadísticas:</strong> Total de solicitudes, solicitudes pendientes, completadas y rechazadas.</li>
                        <li><strong>Solicitudes por Aprobar:</strong> Si eres autorizador, verás una sección con las solicitudes que requieren tu atención, con acceso directo a cada una.</li>
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
                    <p>El sistema envía notificaciones en dos canales:</p>
                    <ul className="list-inside list-disc space-y-1">
                        <li><strong>Correo electrónico:</strong> Se envía al autorizador correspondiente cuando se crea una solicitud, cuando pasa a la siguiente etapa, y al solicitante cuando se completa o rechaza.</li>
                        <li><strong>Panel de notificaciones:</strong> El icono de campana en la esquina superior derecha muestra las notificaciones pendientes. Puedes marcarlas como leídas individualmente o todas a la vez.</li>
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
                        <li>Los <strong>enlaces en el correo</strong> de notificación (enlaces firmados, válidos por 48 horas).</li>
                        <li>La <strong>página de aprobación</strong> por correo.</li>
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
                    <p>Desde el menú de usuario en la esquina inferior izquierda del sidebar puedes acceder a:</p>
                    <ul className="list-inside list-disc space-y-1">
                        <li><strong>Configuración:</strong> Actualiza tu nombre y correo electrónico.</li>
                        <li><strong>Contraseña:</strong> Cambia tu contraseña actual por una nueva.</li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    );
}

function TesoreryGuide() {
    return (
        <div className="space-y-6">
            {/* Programación de pagos semanal */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Calendar className="size-5 text-primary" />
                            Programación de Pagos Semanal
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        El módulo de <strong>Tesorería</strong> permite programar los pagos a realizar durante la semana. Los pagos se organizan por <strong>número de semana natural</strong> (semana del año calendario).
                    </p>
                    <Step number={1}>
                        Desde <strong>Tesorería</strong> > <strong>Programación de Pagos</strong>, visualiza el navegador de semanas.
                    </Step>
                    <Step number={2}>
                        Selecciona la semana que deseas programar usando los botones de navegación o directamente desde el selector de semana.
                    </Step>
                    <Step number={3}>
                        Para cada semana se muestra una lista de <strong>solicitudes de pago completadas</strong> disponibles para pagar.
                    </Step>
                </CardContent>
            </Card>

            {/* Seleccionar pagos */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <CheckCircle className="size-5 text-primary" />
                            Seleccionar Pagos
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        En la pantalla de programación puedes <strong>seleccionar cuáles solicitudes deseas ejecutar</strong> esa semana.
                    </p>
                    <Step number={1}>
                        Marca el <strong>checkbox</strong> de cada solicitud que deseas incluir en el pago de esta semana.
                    </Step>
                    <Step number={2}>
                        Si hay motivos para excluir una solicitud de la semana programada, selecciona el <strong>motivo de exclusión</strong> en el dropdown correspondiente.
                    </Step>
                    <Step number={3}>
                        Usa el botón <strong>Guardar Programación</strong> para confirmar la selección de pagos para la semana.
                    </Step>
                </CardContent>
            </Card>

            {/* Autorizar lote de pagos */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <CheckCircle className="size-5 text-green-500" />
                            Autorizar Lote de Pagos
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        Una vez programados los pagos de la semana, los <strong>autorizadores de tesorería</strong> deben autorizar el lote para que se ejecuten los pagos.
                    </p>
                    <Step number={1}>
                        Desde <strong>Tesorería</strong> > <strong>Programación de Pagos</strong>, selecciona la semana que requiere autorización.
                    </Step>
                    <Step number={2}>
                        Revisa el <strong>monto total</strong> a pagar y los detalles de cada solicitud programada.
                    </Step>
                    <Step number={3}>
                        Haz clic en el botón <strong>Autorizar Lote</strong> para autorizar todos los pagos de la semana. Si necesitas rechazar, haz clic en <strong>Rechazar Lote</strong> y proporciona un motivo.
                    </Step>
                    <Step number={4}>
                        Una vez autorizado el lote, el estado de cada solicitud de pago incluida pasará a <strong>Pagado</strong> y se marcará con la fecha de autorización.
                    </Step>
                </CardContent>
            </Card>

            {/* Historial de pagos */}
            <Card>
                <CardHeader>
                    <CardTitle>
                        <SectionTitle>
                            <Clock className="size-5 text-primary" />
                            Historial de Pagos
                        </SectionTitle>
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        El panel muestra un <strong>historial de todos los lotes de pagos autorizados</strong> organizados cronológicamente.
                    </p>
                    <Step number={1}>
                        Desde <strong>Tesorería</strong> > <strong>Programación de Pagos</strong>, desplázate hasta la sección <strong>Historial</strong> en la parte inferior.
                    </Step>
                    <Step number={2}>
                        Para cada lote autorizado, se muestra: semana, fecha de autorización, cantidad de solicitudes pagadas, monto total pagado y estado.
                    </Step>
                    <Step number={3}>
                        Puedes hacer clic en cada registro para ver el detalle de solicitudes incluidas en ese lote de pago.
                    </Step>
                </CardContent>
            </Card>

            {/* Notificaciones de tesorería */}
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
                    <p>El sistema envía notificaciones sobre actividades en Tesorería:</p>
                    <ul className="list-inside list-disc space-y-1">
                        <li><strong>Programación actualizada:</strong> Cuando se actualiza la programación de pagos de una semana.</li>
                        <li><strong>Solicitud de autorización:</strong> Notificación al autorizador de tesorería cuando hay un lote listo para autorizar.</li>
                        <li><strong>Lote autorizado:</strong> Confirmación cuando el lote de pagos ha sido autorizado exitosamente.</li>
                        <li><strong>Lote rechazado:</strong> Notificación cuando un lote es rechazado, con el motivo del rechazo.</li>
                    </ul>
                </CardContent>
            </Card>
        </div>
    );
}

export default function Guide() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Guía de Usuario" />

            <div className="mx-auto max-w-4xl space-y-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-foreground">
                        Guía de Usuario
                    </h1>
                    <p className="mt-1 text-sm text-muted-foreground">
                        Consulta cómo utilizar las funcionalidades del sistema de solicitudes.
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
                            Presupuestos de Inversión
                        </TabsTrigger>
                        <TabsTrigger value="tesorery">
                            <Wallet className="size-4" />
                            Tesorería
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

                    <TabsContent value="tesorery">
                        <TesoreryGuide />
                    </TabsContent>

                    <TabsContent value="general">
                        <GeneralGuide />
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
