<?php

namespace App\Mail;

use App\Services\Receipts\StatementPdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/** A client's statement of account: full statement in the body, PDF attached when dompdf is installed. */
class StatementOfAccountMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public array $statement, public ?string $note = null, public ?string $pdf = null)
    {
    }

    public function envelope(): Envelope
    {
        $client = $this->statement['client'];

        return new Envelope(
            subject: 'Statement of account — ' . ($client->business_name ?: $client->name) . ' — ' . ($this->statement['to'] ?? now())->format('d M Y'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.statement', with: ['statement' => $this->statement, 'note' => $this->note]);
    }

    public function attachments(): array
    {
        if (! $this->pdf) {
            return [];
        }

        return [Attachment::fromData(fn () => $this->pdf, StatementPdf::filename($this->statement))->withMime('application/pdf')];
    }
}
