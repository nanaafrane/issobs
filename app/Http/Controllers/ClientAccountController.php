<?php

namespace App\Http\Controllers;

use App\Mail\StatementOfAccountMail;
use App\Models\Client;
use App\Models\Field;
use App\Models\StatementSend;
use App\Services\Receipts\ClientStatement;
use App\Services\Receipts\ReceivablesAging;
use App\Services\Receipts\StatementPdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

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
    public function email(Request $request, Client $client, StatementPdf $pdf)
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

        $statement = $this->statements->build($client, $this->date($data['from'] ?? null), $this->date($data['period_to'] ?? null));
        $file = $pdf->render($statement);

        Mail::to($to)->cc($cc)->send(new StatementOfAccountMail($statement, $data['note'] ?? null, $file));

        StatementSend::create([
            'client_id' => $client->id,
            'sent_to' => implode(', ', array_merge($to, $cc)),
            'period_from' => $statement['from'],
            'period_to' => $statement['to'] ?? now(),
            'closing_balance' => $statement['closing'],
            'with_pdf' => $file !== null,
            'user_id' => Auth::id(),
        ]);

        // Remember the address for next time if the client had none.
        if (! $client->email) {
            $client->email = $to[0];
            $client->save();
        }

        return back()->with('primary', 'Statement emailed to ' . implode(', ', $to) . ($file ? ' with PDF attached.' : '.'));
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
