import { Head } from '@inertiajs/react';
import { CheckCircle2 } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type Props = {
    approved: number;
    rejected: number;
};

export default function BatchApprovalSuccess({ approved, rejected }: Props) {
    const total = approved + rejected;

    return (
        <>
            <Head title="Revisión guardada" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 p-4 dark:bg-gray-900">
                <Card className="max-w-md w-full">
                    <CardContent className="pt-6 text-center space-y-4">
                        <CheckCircle2 className="mx-auto h-16 w-16 text-green-500" />
                        <h2 className="text-2xl font-bold text-foreground">Revisión guardada</h2>
                        <div className="space-y-1 text-sm text-muted-foreground">
                            {approved > 0 && (
                                <p>
                                    Se aprobaron <span className="font-semibold text-green-700 dark:text-green-400">{approved}</span>{' '}
                                    {approved === 1 ? 'pago' : 'pagos'}.
                                </p>
                            )}
                            {rejected > 0 && (
                                <p>
                                    Se rechazaron <span className="font-semibold text-red-700 dark:text-red-400">{rejected}</span>{' '}
                                    {rejected === 1 ? 'pago' : 'pagos'}.
                                </p>
                            )}
                            {total > 0 && <p className="pt-2">Se notificó al solicitante y al equipo de Project Management.</p>}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
