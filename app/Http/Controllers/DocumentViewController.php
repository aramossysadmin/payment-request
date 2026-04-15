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
        $filename = basename($path);
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return Storage::disk('local')->response($path, $filename, [
                'Content-Type' => 'application/pdf',
            ]);
        }

        return Storage::disk('local')->download($path, $filename);
    }
}
