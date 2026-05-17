import { Head } from '@inertiajs/react';
import { AlertTriangle } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';

type Props = {
    reason: string;
};

export default function BatchApprovalInvalid({ reason }: Props) {
    return (
        <>
            <Head title="Enlace no válido" />
            <div className="flex min-h-screen items-center justify-center bg-gray-50 p-4 dark:bg-gray-900">
                <Card className="max-w-md w-full">
                    <CardContent className="pt-6 text-center space-y-4">
                        <AlertTriangle className="mx-auto h-16 w-16 text-amber-500" />
                        <h2 className="text-2xl font-bold text-foreground">Enlace no válido</h2>
                        <p className="text-sm text-muted-foreground">{reason}</p>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
