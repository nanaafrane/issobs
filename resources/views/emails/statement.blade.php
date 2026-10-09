<!doctype html>
<html>
<body style="margin:0;padding:16px;background:#ffffff;">
    @if($note)
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#1f2937;white-space:pre-line;">{{ $note }}</p>
    @else
        <p style="font-family:Arial,Helvetica,sans-serif;font-size:13px;color:#1f2937;">Dear {{ $statement['client']->name ?: 'Sir/Madam' }},<br><br>Please find your statement of account below{{ \App\Services\Receipts\StatementPdf::available() ? ' and attached as a PDF' : '' }}.</p>
    @endif
    @include('clients.partials.statement_document', ['statement' => $statement])
</body>
</html>
