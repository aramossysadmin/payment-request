<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Currency;
use App\Models\Project;
use Illuminate\Http\Request;

trait ResolvesDisplayCurrency
{
    protected function resolveDisplayCurrency(Request $request, Project $project): Currency
    {
        $requested = (int) $request->input('display_currency_id');

        if ($requested > 0) {
            $currency = Currency::find($requested);
            if ($currency !== null) {
                return $currency;
            }
        }

        if ($project->currency_id) {
            $currency = Currency::find($project->currency_id);
            if ($currency !== null) {
                return $currency;
            }
        }

        return Currency::query()->where('prefix', 'MXN')->firstOrFail();
    }

    protected function mxnToDisplay(float $mxn, Currency $display): float
    {
        $rate = (float) $display->exchange_rate;

        return $rate > 0.0 ? $mxn / $rate : $mxn;
    }

    /**
     * @return array<int, array{prefix: string, name: string, exchange_rate: float}>
     */
    protected function exchangeRateNote(Currency $display): array
    {
        return Currency::query()
            ->orderBy('prefix')
            ->get(['prefix', 'name', 'exchange_rate'])
            ->map(fn (Currency $c) => [
                'prefix' => $c->prefix,
                'name' => $c->name,
                'exchange_rate' => (float) $c->exchange_rate,
            ])
            ->all();
    }
}
