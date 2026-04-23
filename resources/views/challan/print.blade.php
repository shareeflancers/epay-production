<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Challan - {{ $challan->challan_no }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f0f0f0;
            color: #333;
        }
        .container {
            width: 210mm;
            margin: 0 auto;
            background-color: white;
            padding: 10px;
            box-sizing: border-box;
        }
        .challan-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px dashed #ccc;
        }
        .challan-copy {
            width: 32%;
            border: 1px solid #000;
            padding: 10px;
            box-sizing: border-box;
            position: relative;
            background-color: #fff;
        }
        .header {
            text-align: center;
            border-bottom: 1px solid #000;
            margin-bottom: 10px;
            padding-bottom: 5px;
        }
        .logo {
            width: 40px;
            height: 40px;
            margin-bottom: 5px;
        }
        .institution-name {
            font-size: 14px;
            font-weight: bold;
            margin: 0;
            text-transform: uppercase;
        }
        .form-title {
            font-size: 11px;
            margin: 2px 0;
            font-weight: 500;
        }
        .copy-tag {
            background-color: #000;
            color: #fff;
            font-size: 10px;
            padding: 2px 5px;
            display: inline-block;
            margin-top: 5px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .bank-info {
            display: flex;
            justify-content: space-between;
            font-size: 9px;
            margin-top: 10px;
            border-top: 1px solid #eee;
            padding-top: 5px;
        }
        .bank-logo {
            height: 15px;
            margin-bottom: 3px;
        }
        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }
        .details-table th, .details-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            text-align: left;
        }
        .details-table th {
            background-color: #f9f9f9;
            width: 35%;
        }
        .amount-words {
            font-size: 9px;
            margin-top: 10px;
            font-style: italic;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        .footer {
            margin-top: 20px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
        }
        .sign-line {
            width: 45%;
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 5px;
            margin-top: 30px;
        }
        .qr-code {
            text-align: center;
            margin-top: 10px;
        }
        .qr-code svg {
            width: 60px;
            height: 60px;
        }
        .barcode-text {
            font-size: 8px;
            letter-spacing: 2px;
            margin-top: 2px;
        }
        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .container {
                padding: 0;
                box-shadow: none;
            }
            .no-print {
                display: none;
            }
        }
        .print-btn-container {
            text-align: center;
            margin-bottom: 20px;
        }
        .btn-print {
            padding: 10px 30px;
            background: #2D3436;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<div class="print-btn-container no-print">
    <button onclick="window.print()" class="btn-print">Print Challan Form</button>
</div>

<div class="container">
    <div class="challan-row">
        @php
            $copies = ['Bank Copy', 'Student Copy', 'Board Copy'];
        @endphp

        @foreach($copies as $copy)
        <div class="challan-copy">
            <div class="header">
                {{-- Logo Placeholder --}}
                <img src="{{ asset('assets/logo.png') }}" alt="Logo" class="logo" onerror="this.src='https://via.placeholder.com/100x100?text=LOGO'">
                <h1 class="institution-name">{{ $profile->institution_name ?? 'FGEI (C/G) DIRECTORATE' }}</h1>
                <p class="form-title">{{ $challan->reserved ? explode('|', $challan->reserved)[1] : 'FEE CHALLAN' }}</p>
                <span class="copy-tag">[{{ $copy }}]</span>
            </div>

            <div class="qr-code">
                {!! $qrCode !!}
                <div class="barcode-text">{{ $challan->consumer->consumer_number }}</div>
            </div>

            <div class="bank-info">
                <div style="width: 48%;">
                    <div style="font-weight: bold;">HBL</div>
                    <div>A/C No: 00427900084303</div>
                </div>
                <div style="width: 48%; text-align: right;">
                    <div style="font-weight: bold;">UBL</div>
                    <div>A/C No: 225592551 (MCA)</div>
                </div>
            </div>

            <table class="details-table">
                <tr>
                    <th>Challan No:</th>
                    <td style="font-weight: bold;">{{ $challan->challan_no }}</td>
                </tr>
                <tr>
                    <th>Due Date:</th>
                    <td style="color: red; font-weight: bold;">{{ $challan->due_date->format('d/m/Y') }}</td>
                </tr>
                <tr>
                    <th>Name:</th>
                    <td>{{ $profile->name }}</td>
                </tr>
                <tr>
                    <th>Father Name:</th>
                    <td>{{ $profile->father_or_guardian_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Dept/Level:</th>
                    <td>{{ $profile->institution_level }}</td>
                </tr>
                <tr>
                    <th>Category:</th>
                    <td>{{ $challan->fee_type }}</td>
                </tr>
                <tr>
                    <th>Amount:</th>
                    <td style="font-weight: bold; font-size: 12px;">Rs. {{ number_format($challan->amount_within_dueDate, 2) }}</td>
                </tr>
                <tr>
                    <th>After Due:</th>
                    <td>Rs. {{ number_format($challan->amount_after_dueDate, 2) }}</td>
                </tr>
            </table>

            <div class="amount-words">
                <strong>Amount in words:</strong><br>
                {{ \App\Helpers\NumberHelper::amountInWords($challan->amount_within_dueDate) }}
            </div>

            <div style="font-size: 8px; color: red; margin-top: 5px; font-weight: bold; text-align: center;">
                IT IS MANDATORY TO UPLOAD DEPOSITED FEE SLIP TILL DUE DATE ON THE ADMISSIONS PORTAL
            </div>

            <div class="footer">
                <div class="sign-line">Officer</div>
                <div class="sign-line" style="text-align: right;">Cashier</div>
            </div>
        </div>
        @endforeach
    </div>
</div>

</body>
</html>
