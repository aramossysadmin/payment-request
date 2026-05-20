<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentRequestDocumentController extends Controller
{
    public function __invoke(PaymentRequest $paymentRequest, int $index): StreamedResponse
    {
        Gate::authorize('view', $paymentRequest);

        $documents = array_values(array_filter(
            $paymentRequest->advance_documents ?? [],
            fn ($doc): bool => is_string($doc) && $doc !== '',
        ));

        abort_unless(isset($documents[$index]), 404);

        $path = $documents[$index];

        abort_unless(Storage::disk('local')->exists($path), 404);

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($extension === 'pdf') {
            return Storage::disk('local')->response($path);
        }

        return Storage::disk('local')->download($path);
    }
}
