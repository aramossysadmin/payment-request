<?php

namespace App\Http\Controllers;

use App\Models\PaymentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = $request->string('q')->trim();
        $field = $request->string('field', 'provider')->toString();

        if ($query->length() < 2 || ! in_array($field, ['provider', 'rfc'])) {
            return response()->json([]);
        }

        $results = PaymentRequest::query()
            ->select('provider', 'rfc')
            ->where($field, 'like', "%{$query}%")
            ->whereNotNull('provider')
            ->where('provider', '!=', '')
            ->groupBy('provider', 'rfc')
            ->orderBy('provider')
            ->limit(10)
            ->get();

        return response()->json($results);
    }
}
