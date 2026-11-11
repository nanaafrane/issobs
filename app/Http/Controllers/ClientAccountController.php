<?php

namespace App\Http\Controllers;

use App\Jobs\SendClientStatement;
use App\Models\Client;
use App\Models\Field;
use App\Services\Receipts\ClientStatement;
use App\Services\Receipts\ReceivablesAging;
use App\Services\Receipts\StatementPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sending statements to clients, and the aged receivables report.
 * (The statement page itself is ClientController::statementOfAccount.)
 */
class ClientAccountController extends Controller
{
    public function __construct(private ClientStatement $statements)
    {
        $this->middleware('auth');
    }

    /** Email the statement for the chosen period to the client. */
    public function email(Request $request, Client $client)
    {
        $this->authorizeClient($client);
        $data = $request->validate([
            'to' => ['required', 'string', 'max:500'],
            'cc' => ['nullable', 'string', 'max:500'],
            'note' => ['nullable', 'string', 'max:2000'],
            'from' => ['nullable', 'date'],
            'period_to' => ['nullable', 'date'],
        ]);

        $to = $this->addresses($data['to']);
        $cc = $this->addresses($data['cc'] ?? '');
        if (! $to || count($to) + count($cc) !== count(array_filter(preg_split('/[,;\s]+/', ($data['to'] ?? '') . ' ' . ($data['cc'] ?? ''))))) {
            return back()->withInput()->withErrors(['to' => 'Check the email address(es) — separate several with commas.']);
        }

        // Built and sent in the background (SendClientStatement): the page doesn't wait for the
        // mail server, and a dropped connection to it is retried instead of losing the email.
        SendClientStatement::dispatch(
            $client->id, $to, $cc, $data['note'] ?? null,
            $this->date($data['from'] ?? null)?->toDateString(), $this->date($data['period_to'] ?? null)?->toDateString(), Auth::id(),
        );

        return back()->with('primary', 'Statement queued for ' . implode(', ', $to) . '. It is sent within a minute or two; it shows under "Sent" once it has gone.');
    }

    /** Download the statement as a PDF (needs dompdf). */
    public function pdf(Request $request, Client $client, StatementPdf $pdf)
    {
        $this->authorizeClient($client);
        $statement = $this->statements->build($client, $this->date($request->input('from')), $this->date($request->input('to')));
        $file = $pdf->render($statement);
        if ($file === null) {
            return back()->with('warning', 'PDF download needs the dompdf package (composer require dompdf/dompdf). Use Print → Save as PDF meanwhile.');
        }

        return response($file, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . StatementPdf::filename($statement) . '"',
        ]);
    }

    /** Aged receivables: what each client owes, by how overdue. */
    public function aged(Request $request, ReceivablesAging $aging)
    {
        $allowed = $this->allowedFieldIds();
        $fieldId = $request->integer('field') ?: null;
        $fieldIds = $fieldId ? [$fieldId] : $allowed;
        if ($allowed !== null && $fieldId && ! in_array($fieldId, $allowed, true)) {
            abort(403);
        }

        $report = $aging->build($fieldIds);
        $fields = $allowed === null ? Field::orderBy('name')->get() : Field::whereIn('id', $allowed)->orderBy('name')->get();

        return view('receivables.aged', compact('report', 'fields', 'fieldId'));
    }

    /** Aged receivables, one client: only the invoices they still owe on. */
    public function agedClient(Client $client, ReceivablesAging $aging)
    {
        $this->authorizeClient($client);
        $report = $aging->forClient($client);

        return view('receivables.aged_client', compact('client', 'report'));
    }

    /** Head office sees every office; everyone else only their own (Tema also sees Shai Hills). */
    private function allowedFieldIds(): ?array
    {
        $user = Auth::user();
        if ($user?->hasRole(['Finance Manager', 'Invoice', 'Director'])) {
            return null;
        }

        return $user?->field_id == 3 ? [3, 7] : array_values(array_filter([(int) $user?->field_id]));
    }

    private function authorizeClient(Client $client): void
    {
        $allowed = $this->allowedFieldIds();
        if ($allowed !== null && ! in_array((int) $client->field_id, $allowed, true)) {
            abort(403);
        }
    }

    /** @return array<int, string> valid addresses only */
    private function addresses(string $list): array
    {
        return array_values(array_filter(
            array_map('trim', preg_split('/[,;\s]+/', $list) ?: []),
            fn ($a) => $a !== '' && filter_var($a, FILTER_VALIDATE_EMAIL)
        ));
    }

    private function date(?string $value): ?Carbon
    {
        return $value ? Carbon::parse($value) : null;
    }
}
