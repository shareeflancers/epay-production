<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Verification - {{ $challan->challan_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            min-height: 100vh;
        }

        .receipt-container {
            background: white;
            width: 100%;
            max-width: 800px;
            border: 2px solid #28a745;
            border-radius: 40px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
            box-sizing: border-box;
        }

        /* Watermark Background */
        .watermark-container {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            transform: rotate(-25deg);
            z-index: 0;
            pointer-events: none;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            opacity: 0.04;
        }

        .watermark-row {
            display: flex;
            justify-content: space-around;
            white-space: nowrap;
            font-size: 24px;
            font-weight: 800;
            color: #000;
        }

        .content {
            position: relative;
            z-index: 1;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            gap: 20px;
            text-align: center;
        }

        .logo {
            width: 80px;
            height: auto;
        }

        .header-text h1 {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #000;
            text-transform: uppercase;
        }

        .header-text p {
            margin: 5px 0 0;
            font-size: 16px;
            font-weight: 700;
            color: #000;
        }

        .success-banner {
            background-color: #e8f5e9;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            margin-bottom: 30px;
        }

        .success-banner h2 {
            margin: 0;
            color: #2e7d32;
            font-size: 24px;
            font-weight: 700;
        }

        .success-banner p {
            margin: 5px 0 0;
            color: #2e7d32;
            font-size: 18px;
        }

        .top-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .qr-code {
            width: 140px;
            height: 140px;
            background: #fff;
            padding: 10px;
            border: 1px solid #ddd;
        }

        .id-section {
            text-align: right;
        }

        .id-group {
            margin-bottom: 10px;
        }

        .id-label {
            font-size: 11px;
            font-weight: 700;
            color: #666;
            text-transform: uppercase;
        }

        .id-value {
            font-size: 18px;
            font-weight: 800;
            color: #000;
        }

        .id-value.psid {
            color: #0061ff;
            font-size: 16px;
        }

        .id-value.ref {
            color: #28a745;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            border: 1.5px solid #000;
        }

        .details-table th, .details-table td {
            border: 1px solid #000;
            padding: 12px 15px;
            text-align: left;
            font-size: 14px;
        }

        .details-table th {
            background-color: #f8f9fa;
            width: 35%;
            font-weight: 700;
        }

        .details-table td {
            font-weight: 600;
            text-transform: uppercase;
        }

        .amount-cell {
            font-size: 24px !important;
            font-weight: 800 !important;
            color: #28a745;
        }

        .footer {
            text-align: center;
            margin-top: 40px;
        }

        .footer p {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }

        .footer .portal-text {
            font-style: italic;
            font-weight: 600;
            margin-top: 15px;
        }

        .paid-stamp {
            position: absolute;
            bottom: 40px;
            right: 60px;
            border: 4px solid #28a745;
            color: #28a745;
            font-size: 40px;
            font-weight: 900;
            padding: 5px 20px;
            border-radius: 15px;
            transform: rotate(-15deg);
            opacity: 0.8;
            pointer-events: none;
            background: white;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.1);
        }

        @media (max-width: 600px) {
            body { padding: 10px; }
            .receipt-container { padding: 20px; border-radius: 20px; }
            .top-info { flex-direction: column; align-items: center; text-align: center; }
            .id-section { text-align: center; margin-top: 20px; }
            .amount-cell { font-size: 20px !important; }
            .paid-stamp { position: static; transform: none; margin: 20px auto; display: table; }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Repeating Watermark Background -->
        <div class="watermark-container">
            @for ($i = 0; $i < 15; $i++)
                <div class="watermark-row">
                    @for ($j = 0; $j < 8; $j++)
                        <span>{{ $challan->challan_no }} • REF{{ $challan->tran_auth_id }}</span>
                        <span style="width: 100px;"></span>
                    @endfor
                </div>
            @endfor
        </div>

        <div class="content">
            <div class="header">
                <img src="{{ asset('assets/logo/logo.png') }}" class="logo" alt="Logo" onerror="this.src='https://sis.fgei.gov.pk/assets/images/logo.png'">
                <div class="header-text">
                    <h1>{{ $challan->institution->name ?? ($profile->institution_name ?? 'FGEI (C/G) DIRECTORATE') }}</h1>
                    <p>OFFICIAL PAYMENT RECEIPT</p>
                </div>
            </div>

            <div class="success-banner">
                @if($challan->status === 'P')
                    <h2>Payment Successful!</h2>
                    <p>This challan has been paid and verified by the system.</p>
                @else
                    <h2 style="color: #f57f17;">Payment Pending</h2>
                    <p style="color: #f57f17;">This challan is currently awaiting payment.</p>
                @endif
            </div>

            <div class="top-info">
                <div class="qr-code">
                    {!! QrCode::size(120)->generate(route('challan.verify', ['consumer_number' => $challan->consumer->consumer_number])) !!}
                    <div style="font-size: 9px; font-weight: 800; text-align: center; margin-top: 8px; color: #666;">SCAN TO VERIFY STATUS</div>
                </div>
                <div class="id-section">
                    <div class="id-group">
                        <div class="id-label">Consumer Number</div>
                        <div class="id-value">{{ $challan->consumer->consumer_number }}</div>
                    </div>
                    <div class="id-group">
                        <div class="id-label">1Link PSID</div>
                        <div class="id-value psid">111787474{{ $challan->institution_id }}{{ $challan->consumer->consumer_number }}</div>
                    </div>
                    @if($challan->status === 'P')
                        <div class="id-group">
                            <div class="id-label">Transaction ID / REF</div>
                            <div class="id-value ref">{{ $challan->tran_auth_id }}</div>
                        </div>
                    @endif
                </div>
            </div>

            <table class="details-table">
                <tr>
                    <th>Student Name</th>
                    <td>{{ $profile->name }}</td>
                </tr>
                <tr>
                    <th>Father Name</th>
                    <td>{{ $profile->father_or_guardian_name }}</td>
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
                    <td class="amount-cell">Rs. {{ number_format($challan->amount_within_dueDate, 0) }}/-</td>
                </tr>
                <tr>
                    <th>Payment Date</th>
                    <td>{{ $challan->date_paid ? \Carbon\Carbon::parse($challan->date_paid)->format('d-M-Y H:i') : ($challan->status === 'P' ? $challan->updated_at->format('d-M-Y H:i') : '-') }}</td>
                </tr>
                <tr>
                    <th>Payment Method</th>
                    <td>{{ $challan->bank_mnemonic ?? '1LINK / BRANCHLESS' }}</td>
                </tr>
            </table>

            <div class="footer">
                <p>This is a computer generated receipt and does not require a physical signature.</p>
                <p class="portal-text">FGEI e-Portal - Empowering Education through Technology</p>
            </div>

            @if($challan->status === 'P')
                <div class="paid-stamp">PAID</div>
            @endif
        </div>
    </div>
</body>
</html>
