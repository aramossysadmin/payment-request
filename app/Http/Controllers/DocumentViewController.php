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

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404, 'Documento no encontrado.');
        }

        $mimeType = Storage::disk('public')->mimeType($path);
        $filename = basename($path);

        return Storage::disk('public')->response($path, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }
}
