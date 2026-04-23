<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $challan->status === 'P' ? 'E-Payment Receipt' : 'Fee Challan' }} - {{ $challan->challan_no }}</title>
    <style>
        @page {
            size: A4 {{ $challan->status === 'P' ? 'portrait' : 'landscape' }};
            margin: 10mm;
        }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #1a1a1a;
            -webkit-print-color-adjust: exact;
        }
        .container {
            width: {{ $challan->status === 'P' ? '190mm' : '277mm' }};
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            background-color: transparent;
        }

        /* --- Paid Receipt Styles --- */
        .receipt-card {
            width: 100%;
            max-width: 160mm;
            margin: 20mm auto;
            background: #fff;
            border: 2px solid #27ae60;
            border-radius: 20px;
            padding: 15mm;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 120px;
            font-weight: 900;
            color: rgba(39, 174, 96, 0.08);
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            text-transform: uppercase;
        }
        .id-watermark {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            font-size: 12px;
            line-height: 40px;
            color: rgba(0,0,0,0.02);
            z-index: 0;
            pointer-events: none;
            overflow: hidden;
            word-break: break-all;
            text-align: justify;
        }
        .paid-stamp {
            position: absolute;
            bottom: 60px;
            right: 40px;
            border: 5px double #27ae60;
            color: #27ae60;
            padding: 10px 25px;
            font-size: 32px;
            font-weight: 900;
            transform: rotate(-15deg);
            border-radius: 10px;
            background: rgba(39, 174, 96, 0.05);
            z-index: 2;
        }

        /* --- New Diagonal Watermark --- */
        .diagonal-watermark {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            align-items: center;
            opacity: 0.04;
        }
        .watermark-row {
            display: flex;
            justify-content: space-around;
            width: 150%;
            transform: rotate(-25deg);
        }
        .watermark-item {
            font-size: 22px;
            font-weight: 900;
            white-space: nowrap;
            padding: 60px; /* Increased padding for vertical spacing */
            color: #000;
            letter-spacing: 2px;
        }

        /* --- Challan Copy Styles --- */
        .challan-copy {
            width: 88mm;
            height: 190mm;
            border: 1.5px solid #000;
            padding: 8mm;
            box-sizing: border-box;
            background-color: #fff;
            position: relative;
            display: flex;
            flex-direction: column;
        }
        .challan-copy:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -10px;
            top: 0;
            bottom: 0;
            border-right: 1px dashed #666;
            z-index: 10;
        }

        .footer {
            display: none; /* Hide old footer if any */
        }

        .signature-section {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding: 20px 5px 5px 5px;
            position: relative;
            z-index: 1;
        }
        .signature-box {
            width: 42%;
            text-align: center;
        }
        .signature-line {
            border-top: 1.5px solid #000;
            margin-bottom: 4px;
        }
        .signature-label {
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            color: #000;
            letter-spacing: 0.5px;
        }
        .bank-stamp-area {
            position: absolute;
            bottom: 45px;
            left: 50%;
            transform: translateX(-50%);
            border: 1px dashed #ccc;
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8px;
            color: #999;
            text-align: center;
            border-radius: 50%;
        }

        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            margin-bottom: 10px;
            padding-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        .logo-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 5px;
        }
        .logo {
            width: 50px;
            height: 50px;
            object-fit: contain;
        }
        .institution-name {
            font-size: 14px;
            font-weight: 800;
            margin: 0;
            line-height: 1.2;
            text-transform: uppercase;
        }
        .billing-month {
            font-size: 11px;
            font-weight: 700;
            margin: 4px 0;
            color: #000;
        }
        .copy-tag {
            background-color: #000;
            color: #fff;
            font-size: 10px;
            padding: 3px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-top: 5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .qr-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            padding: 10px;
            border: 1px solid #eee;
            border-radius: 12px;
            background: #fafafa;
        }
        .qr-code svg {
            width: 70px;
            height: 70px;
        }
        .consumer-info {
            text-align: right;
        }
        .consumer-label {
            font-size: 9px;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
        }
        .consumer-value {
            font-size: 14px;
            font-weight: 800;
            font-family: 'Courier New', Courier, monospace;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin: 15px 0;
            z-index: 1;
            position: relative;
        }
        .details-table th, .details-table td {
            border: 1px solid #000;
            padding: 8px 12px;
            text-align: left;
        }
        .details-table th {
            background-color: #f2f2f2;
            width: 40%;
            font-weight: 700;
        }
        .details-table td {
            font-weight: 600;
        }

        .success-banner {
            background-color: #e8f5e9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 20px;
            border: 1px solid #c8e6c9;
        }

        @media print {
            body { background-color: #fff; padding: 0; }
            .no-print { display: none; }
            .container { width: 100%; margin: 0; }
            .receipt-card { border-width: 2px; box-shadow: none; margin: 10mm auto; }
        }

        .no-print-zone {
            text-align: center;
            padding: 20px;
            background: #222;
            color: white;
            margin-bottom: 20px;
        }
        .btn-print {
            padding: 12px 40px;
            background: #fff;
            color: #000;
            border: none;
            border-radius: 30px;
            font-weight: 800;
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(255,255,255,0.2);
            font-size: 16px;
        }
    </style>
</head>
<body>

<div class="no-print-zone no-print">
    <button onclick="window.print()" class="btn-print">🖨️ {{ $challan->status === 'P' ? 'Print Official Receipt' : 'Print Challan Forms' }}</button>
</div>

<div class="container">
    @if($challan->status === 'P')
        {{-- PAID RECEIPT FORMAT --}}
        <div class="receipt-card">
            <div class="diagonal-watermark">
                @for($i=0; $i<8; $i++)
                <div class="watermark-row">
                    @for($j=0; $j<4; $j++)
                        <span class="watermark-item">{{ $challan->challan_no }}</span>
                    @endfor
                </div>
                @endfor
            </div>
            <div class="watermark">
                <img src="{{ asset('assets/logo/logo.png') }}" alt="Watermark" style="width: 400px; opacity: 0.04;">
            </div>
            <div class="id-watermark">
                @php
                    $paidDate = $challan->date_paid ? \Carbon\Carbon::parse($challan->date_paid)->format('d-M-Y') : $challan->updated_at->format('d-M-Y');
                    $refNo = $challan->tran_ref_number ?? 'REF-N/A';
                    $watermarkText = $paidDate . ' • ' . $refNo . ' • ' . $challan->consumer->consumer_number . ' • ' . $challan->challan_no . ' • ';
                @endphp

                {{ str_repeat($watermarkText, 60) }}
            </div>
            <div class="paid-stamp">PAID</div>

            <div class="header">
                <div class="logo-section">
                    <img src="{{ asset('assets/logo/logo.png') }}" alt="Logo" class="logo">
                    <div>
                        <h1 class="institution-name">{{ $challan->institution->name ?? ($profile->institution_name ?? 'FGEI (C/G) DIRECTORATE') }}</h1>
                        <div class="billing-month" style="font-size: 14px; margin-top: 5px;">OFFICIAL PAYMENT RECEIPT</div>
                    </div>
                </div>
            </div>

            <div class="success-banner">
                <strong style="font-size: 18px;">Payment Successful!</strong><br>
                This challan has been paid and verified by the system.
            </div>

            <div class="qr-section">
                <div class="qr-code">
                    {!! $qrCode !!}
                    <div style="font-size: 8px; font-weight: 800; margin-top: 5px; text-align: center; color: #444; line-height: 1;">SCAN TO VERIFY<br>STATUS</div>
                </div>
                <div class="consumer-info">
                    <div class="consumer-label">Consumer Number</div>
                    <div class="consumer-value">{{ $challan->consumer->consumer_number }}</div>
                    <div class="consumer-label" style="margin-top: 5px;">1Link PSID</div>
                    <div class="consumer-value" style="font-size: 15px; color: #007bff;">{{ '11474444' . $challan->institution_id . $challan->consumer->consumer_number }}</div>
                    <div class="consumer-label" style="margin-top: 5px;">Transaction ID / Ref</div>
                    <div class="consumer-value" style="font-size: 16px; color: #27ae60;">{{ $challan->tran_ref_number ?? 'EPAY-' . $challan->id }}</div>
                </div>
            </div>

            <table class="details-table">
                <tr>
                    <th>Student Name</th>
                    <td>{{ $profile->name }}</td>
                </tr>
                <tr>
                    <th>Father Name</th>
                    <td>{{ $profile->father_or_guardian_name ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th>Class / Section</th>
                    <td>{{ $challan->schoolClass->name ?? $profile->class }} - {{ $profile->section }}</td>
                </tr>
                <tr>
                    <th>Challan Number</th>
                    <td>{{ $challan->challan_no }}</td>
                </tr>
                <tr>
                    <th>Amount Paid</th>
                    <td style="font-size: 18px; font-weight: 900; color: #27ae60;">Rs. {{ number_format($challan->amount_within_dueDate, 0) }}/-</td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td>{{ $challan->date_paid ? \Carbon\Carbon::parse($challan->date_paid)->format('d-M-Y H:i') : $challan->updated_at->format('d-M-Y H:i') }}</td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td>{{ $challan->bank_mnemonic ?? 'e-Banking / 1Link' }}</td>
                </tr>
            </table>

            <div style="margin-top: 30px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; padding-top: 20px;">
                <p>This is a computer generated receipt and does not require a physical signature.</p>
                <p><strong>FGEI e-Portal</strong> - <i>Empowering Education through Technology</i></p>
            </div>
        </div>

    @else
        {{-- UNPAID CHALLAN FORMAT (3 COPIES) --}}
        @php
            $copies = ['Bank Copy', 'Student Copy', 'Institution Copy'];
            $billingMonth = $challan->reserved ? trim(explode('|', $challan->reserved)[1]) : 'Fee Challan';
        @endphp

        @foreach($copies as $copy)
        <div class="challan-copy">
            <div class="diagonal-watermark">
                @for($i=0; $i<10; $i++)
                <div class="watermark-row">
                    @for($j=0; $j<4; $j++)
                        <span class="watermark-item">{{ $challan->challan_no }}</span>
                    @endfor
                </div>
                @endfor
            </div>
            <div class="header">
                <div class="logo-section">
                    <img src="{{ asset('assets/logo/logo.png') }}" alt="Logo" class="logo" onerror="this.src='https://sis.fgei.gov.pk/assets/images/logo.png'">
                    <div>
                        <h1 class="institution-name" style="font-size: 12px;">{{ $challan->institution->name ?? ($profile->institution_name ?? 'FGEI (C/G) DIRECTORATE') }}</h1>
                        <div class="billing-month">{{ $billingMonth }}</div>
                    </div>
                </div>
                <span class="copy-tag">{{ $copy }}</span>
            </div>

            <div class="qr-section">
                <div class="qr-code">
                    {!! $qrCode !!}
                    <div style="font-size: 7px; font-weight: 800; margin-top: 4px; text-align: center; color: #000; line-height: 1;">SCAN TO CHECK<br>PAYMENT STATUS</div>
                </div>
                <div class="consumer-info">
                    <div class="consumer-label">Consumer Number</div>
                    <div class="consumer-value">{{ $challan->consumer->consumer_number }}</div>
                    <div class="consumer-label" style="margin-top: 3px;">Challan No</div>
                    <div class="consumer-value" style="font-size: 11px;">{{ $challan->challan_no }}</div>
                    <div class="consumer-label" style="margin-top: 3px;">1Link PSID</div>
                    <div class="consumer-value" style="font-size: 12px; color: #007bff;">{{ '111787474' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</div>
                    <div class="consumer-label" style="margin-top: 3px;">Askari App</div>
                    <div class="consumer-value" style="font-size: 11px;">{{ '447' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</div>
                </div>
            </div>

            <table class="details-table" style="font-size: 9px; margin: 5px 0;">
                <tr><th>Student</th><td>{{ $profile->name }}</td></tr>
                <tr><th>Class</th><td>{{ $challan->schoolClass->name ?? $profile->class }}</td></tr>
                <tr><th>Due Date</th><td style="color: red;">{{ $challan->due_date->format('d-M-Y') }}</td></tr>
                <tr><th>Payable</th><td style="font-weight: 900; font-size: 12px;">Rs. {{ number_format($challan->amount_within_dueDate, 0) }}/-</td></tr>
            </table>

            <div style="font-size: 10px; font-style: italic; margin-top: 5px;">
                <strong>In Words:</strong> {{ \App\Helpers\NumberHelper::amountInWords($challan->amount_within_dueDate) }}
            </div>

            <div class="signature-section">
                <div class="bank-stamp-area">Bank Stamp</div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Officer Signature</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">Depositor Signature</div>
                </div>
            </div>
        </div>
        @endforeach
    @endif
</div>

</body>
</html>
