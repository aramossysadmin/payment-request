<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class DocumentViewController extends Controller
{
    public function __invoke(Request $request): Response
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'El enlace ha expirado o no es válido.');
        }

        $path = $request->query('path');

        if (! $path || str_contains($path, '..') || str_starts_with($path, '/')) {
            abort(403, 'Ruta de documento no permitida.');
        }

        if (! Storage::disk('local')->exists($path)) {
            abort(404, 'Documento no encontrado.');
        }

        $mimeType = Storage::disk('local')->mimeType($path);
        $realFilename = basename($path);
        $extension = strtolower(pathinfo($realFilename, PATHINFO_EXTENSION));

        // Nombre de descarga opcional (viene en el signed URL). Permite servir el
        // archivo con un nombre descriptivo (ej. "CSF SASTRERIA VAN DER WOLF.pdf")
        // en vez del UUID de storage. Compatible hacia atrás: si no viene, usamos el
        // basename real.
        $requestedName = $request->query('download_name');
        $filename = (is_string($requestedName) && $requestedName !== '')
            ? preg_replace('#[/\\\\]#', '', $requestedName)
            : $realFilename;

        $inlineMimes = [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];

        if (isset($inlineMimes[$extension])) {
            return Storage::disk('local')->response($path, $filename, [
                'Content-Type' => $inlineMimes[$extension],
                'Content-Disposition' => 'inline; filename="'.$filename.'"',
                // Permitir embebido en <iframe>/<img> desde el mismo origen
                // (sobrescribe el X-Frame-Options: DENY global solo para esta respuesta).
                'X-Frame-Options' => 'SAMEORIGIN',
            ]);
        }

        return Storage::disk('local')->download($path, $filename);
    }
}
