<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>@page { margin: 18mm 14mm; } body { margin: 0; }</style>
</head>
<body>
    @include('clients.partials.statement_document', ['statement' => $statement])
</body>
</html>
