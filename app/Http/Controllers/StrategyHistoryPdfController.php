<?php

namespace App\Http\Controllers;

use App\Services\StrategyPaymentHistory;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class StrategyHistoryPdfController extends Controller
{
    public function __invoke(Request $request, StrategyPaymentHistory $history): Response
    {
        $report = $history->report($request);
        $slug = Str::slug($report['projectName']);
        $stamp = Carbon::now()->format('Ymd');

        return Pdf::loadView('pdf.strategies', [
            ...$report,
            'statusLabels' => StrategyPaymentHistory::STATUS_LABELS,
            'generatedAt' => Carbon::now(),
            'generatedBy' => $request->user()->name,
        ])->setPaper('legal', 'landscape')->download("estrategias-{$slug}-{$stamp}.pdf");
    }
}
