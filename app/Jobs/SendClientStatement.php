<?php

namespace App\Jobs;

use App\Mail\StatementOfAccountMail;
use App\Models\Client;
use App\Models\StatementSend;
use App\Services\Receipts\ClientStatement;
use App\Services\Receipts\StatementPdf;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Build and email a client's statement of account in the background.
 *
 * The page no longer waits for the mail server: the cashier gets "queued" at once,
 * and if Mailgun is unreachable the job tries again (3 tries, a few minutes apart).
 * The statement is built when the job runs, so the PDF never sits in the queue table,
 * and the "sent" history (statement_sends) is only written once the mail actually went.
 */
class SendClientStatement implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 3;

    /**
     * @param  array<int, string>  $to
     * @param  array<int, string>  $cc
     */
    public function __construct(
        public int $clientId,
        public array $to,
        public array $cc,
        public ?string $note,
        public ?string $from,
        public ?string $periodTo,
        public ?int $userId,
    ) {
    }

    /** @return array<int, int> */
    public function backoff(): array
    {
        return [60, 300];
    }

    public function handle(ClientStatement $statements, StatementPdf $pdf): void
    {
        $client = Client::find($this->clientId);
        if (! $client) {
            return;
        }

        $statement = $statements->build($client, $this->from ? Carbon::parse($this->from) : null, $this->periodTo ? Carbon::parse($this->periodTo) : null);
        $file = $pdf->render($statement);

        Mail::to($this->to)->cc($this->cc)->send(new StatementOfAccountMail($statement, $this->note, $file));

        StatementSend::create([
            'client_id' => $client->id,
            'sent_to' => implode(', ', array_merge($this->to, $this->cc)),
            'period_from' => $statement['from'],
            'period_to' => $statement['to'] ?? now(),
            'closing_balance' => $statement['closing'],
            'with_pdf' => $file !== null,
            'user_id' => $this->userId,
        ]);

        if (! $client->email) {
            $client->email = $this->to[0];
            $client->save();
        }
    }

    public function failed(Throwable $e): void
    {
        Log::error('Statement email failed for client ' . $this->clientId . ' to ' . implode(', ', $this->to) . ': ' . $e->getMessage());
    }
}
