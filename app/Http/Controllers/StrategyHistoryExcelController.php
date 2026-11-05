<?php

namespace App\Http\Controllers;

use App\Exports\StrategyHistoryExport;
use App\Services\StrategyPaymentHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StrategyHistoryExcelController extends Controller
{
    public function __invoke(Request $request, StrategyPaymentHistory $history): BinaryFileResponse
    {
        $report = $history->report($request);
        $slug = Str::slug($report['projectName']);
        $stamp = Carbon::now()->format('Ymd');

        return Excel::download(new StrategyHistoryExport(collect($report['rows'])), "estrategias-{$slug}-{$stamp}.xlsx");
    }
}
