<?php

namespace App\Services\Receipts;

/**
 * Turns a statement into a PDF with dompdf, when it is installed
 * (composer require dompdf/dompdf). Without it, statements are still emailed —
 * the full statement is in the email body — and can be printed to PDF from the page.
 */
class StatementPdf
{
    public static function available(): bool
    {
        return class_exists(\Dompdf\Dompdf::class);
    }

    public function render(array $statement): ?string
    {
        if (! self::available()) {
            return null;
        }

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'defaultFont' => 'DejaVu Sans']);
        $dompdf->loadHtml(view('clients.statement_pdf', ['statement' => $statement])->render(), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public static function filename(array $statement): string
    {
        $client = $statement['client'];
        $name = preg_replace('/[^A-Za-z0-9]+/', '-', $client->business_name ?: $client->name ?: 'client');

        return 'Statement-' . trim($name, '-') . '-' . ($statement['to'] ?? now())->format('Y-m-d') . '.pdf';
    }
}
