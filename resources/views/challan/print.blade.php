<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ ($challan->status ?? 'U') === 'P' ? 'E-Payment Receipt' : 'Fee Challan' }} - {{ $challan->challan_no }}</title>
    @include('challan.partials.print_styles', ['challan' => $challan])
</head>
<body>

<div class="no-print-zone no-print">
    <button onclick="window.print()" class="btn-print">🖨️ {{ ($challan->status ?? 'U') === 'P' ? 'Print Official Receipt' : 'Print Challan Forms' }}</button>
</div>

@include('challan.partials.print_content', ['challan' => $challan])

</body>
</html>
