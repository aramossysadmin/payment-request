import { ExternalLink, FileText } from 'lucide-react';

type Document = { name: string; url: string };

function extensionOf(name: string): string {
    return (name.split('.').pop() ?? '').toLowerCase();
}

function PreviewBody({ doc }: { doc: Document }) {
    const ext = extensionOf(doc.name);

    if (ext === 'pdf') {
        // <object> con el visor nativo del navegador es más confiable que <iframe>
        // para PDFs servidos inline. Fallback: link a "Abrir en pestaña".
        return (
            <object
                data={`${doc.url}#view=FitH&toolbar=1`}
                type="application/pdf"
                className="h-[32rem] w-full rounded border bg-white"
            >
                <div className="flex h-full flex-col items-center justify-center gap-2 p-4 text-sm text-muted-foreground">
                    <span>Tu navegador no puede previsualizar este PDF.</span>
                    <a
                        href={doc.url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1 rounded border px-3 py-1.5 text-primary hover:bg-accent"
                    >
                        <ExternalLink className="h-3.5 w-3.5" /> Abrir PDF en pestaña
                    </a>
                </div>
            </object>
        );
    }

    if (['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext)) {
        return (
            <img
                src={doc.url}
                alt={doc.name}
                className="max-h-96 w-full rounded border bg-white object-contain"
            />
        );
    }

    return (
        <a
            href={doc.url}
            target="_blank"
            rel="noopener noreferrer"
            className="flex items-center gap-2 rounded border px-3 py-2 text-sm text-primary hover:bg-accent"
        >
            <ExternalLink className="h-4 w-4" />
            Abrir {ext.toUpperCase() || 'documento'}
        </a>
    );
}

export function DocumentPreview({ documents }: { documents: Document[] }) {
    if (!documents || documents.length === 0) {
        return null;
    }

    return (
        <div className="space-y-3">
            {documents.map((doc, index) => (
                <div key={index} className="space-y-2 rounded-lg border p-3">
                    <div className="flex items-center justify-between gap-2">
                        <div className="flex min-w-0 items-center gap-2">
                            <FileText className="h-4 w-4 shrink-0 text-muted-foreground" />
                            <span className="truncate text-sm font-medium" title={doc.name}>
                                {doc.name}
                            </span>
                        </div>
                        <a
                            href={doc.url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                        >
                            <ExternalLink className="h-3 w-3" />
                            Abrir en pestaña
                        </a>
                    </div>
                    <PreviewBody doc={doc} />
                </div>
            ))}
        </div>
    );
}
