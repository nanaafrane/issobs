<?php

namespace App\Support;

use App\Models\InvoiceData;
use App\Models\Service;

/**
 * The one place that decides what "number of guards on an invoice" means.
 *
 * Only invoice lines flagged counts_as_guards are headcount. Deduction lines
 * (e.g. ABSENT DEDUCTION) keep their quantity for pricing but are not guards.
 */
class InvoiceGuards
{
    /**
     * Total guards across a collection / array of Invoice models (or plain ids).
     */
    public static function total(iterable $invoices): float
    {
        $ids = [];
        foreach ($invoices as $invoice) {
            $ids[] = is_object($invoice) ? $invoice->id : $invoice;
        }

        if ($ids === []) {
            return 0.0;
        }

        return (float) InvoiceData::guards()->whereIn('invoice_id', $ids)->sum('quantity');
    }

    /**
     * Names of services whose quantity is NOT headcount.
     *
     * @return array<int, string>
     */
    public static function nonHeadcountServices(): array
    {
        return Service::where('counts_as_guards', false)->pluck('name')->all();
    }

    /**
     * Flag to store on an invoice line for the given service name.
     *
     * @param array<int, string> $nonHeadcount result of nonHeadcountServices()
     */
    public static function flagFor(?string $serviceName, array $nonHeadcount): bool
    {
        return ! in_array($serviceName, $nonHeadcount, true);
    }
}
