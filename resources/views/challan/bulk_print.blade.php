<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Fee Challans</title>
    {{-- We use the first challan to get the styles, as styles are mostly constant --}}
    @include('challan.partials.print_styles', ['challan' => $challans->first()])
</head>
<body>

<div class="no-print-zone no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Print Bulk Challans ({{ $challans->count() }} Students)</button>
</div>

@foreach($challans as $challan)
    <div class="bulk-page-break">
        @include('challan.partials.print_content', ['challan' => $challan])
    </div>
@endforeach

</body>
</html>
