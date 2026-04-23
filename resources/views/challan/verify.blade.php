<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Challan Verification - {{ $challan->challan_no }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0061ff;
            --secondary: #60efff;
            --success: #00c853;
            --warning: #ffab00;
            --dark: #1a1a1a;
            --bg: #f8fafc;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg);
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: var(--dark);
        }
        .card {
            background: white;
            width: 100%;
            max-width: 450px;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .logo {
            width: 70px;
            margin-bottom: 15px;
        }
        .title {
            font-size: 20px;
            font-weight: 800;
            margin: 0;
            color: #333;
            text-transform: uppercase;
        }
        .status-badge {
            display: inline-block;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 18px;
            margin: 20px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-paid {
            background: #e8f5e9;
            color: #2e7d32;
            border: 2px solid #2e7d32;
        }
        .status-unpaid {
            background: #fff8e1;
            color: #f57f17;
            border: 2px solid #f57f17;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 15px;
            margin-top: 20px;
            background: #f1f5f9;
            padding: 20px;
            border-radius: 16px;
        }
        .info-item label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 4px;
        }
        .info-item span {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
        }
        .action-btn {
            display: block;
            width: 100%;
            padding: 15px;
            border-radius: 12px;
            border: none;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            margin-top: 25px;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            box-shadow: 0 10px 20px rgba(0, 97, 255, 0.2);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 25px rgba(0, 97, 255, 0.3);
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <img src="{{ asset('assets/logo/logo.png') }}" class="logo" alt="Logo">
            <h1 class="title">Payment Verification</h1>
        </div>

        <div style="text-align: center;">
            @if($challan->status === 'P')
                <div class="status-badge status-paid">✓ Paid</div>
            @else
                <div class="status-badge status-unpaid">! Unpaid</div>
            @endif
        </div>

        <div class="info-grid">
            <div class="info-item">
                <label>Student Name</label>
                <span>{{ $profile->name }}</span>
            </div>
            <div class="info-item">
                <label>Institution</label>
                <span>{{ $challan->institution->name ?? ($profile->institution_name ?? 'FGEI Directorate') }}</span>
            </div>
            <div class="info-item">
                <label>Challan Number</label>
                <span>{{ $challan->challan_no }}</span>
            </div>
            <div class="info-item">
                <label>Consumer Number</label>
                <span>{{ $challan->consumer->consumer_number }}</span>
            </div>
            <div class="info-item">
                <label>Amount</label>
                <span>Rs. {{ number_format($challan->amount_within_dueDate, 0) }}/-</span>
            </div>
            @if($challan->status === 'P')
                <div class="info-item">
                    <label>Payment Date</label>
                    <span>{{ $challan->date_paid ? \Carbon\Carbon::parse($challan->date_paid)->format('d-M-Y H:i') : $challan->updated_at->format('d-M-Y H:i') }}</span>
                </div>
            @endif
        </div>

        @if($challan->status === 'P')
            <a href="{{ route('challan.view', ['challan_no' => $challan->challan_no]) }}" class="action-btn btn-primary">
                Download Official Receipt
            </a>
        @else
            <a href="{{ route('challan.view', ['challan_no' => $challan->challan_no]) }}" class="action-btn btn-primary">
                View & Print Challan Form
            </a>
        @endif

        <div class="footer">
            &copy; {{ date('Y') }} FGEI (C/G) Directorate. All rights reserved.<br>
            Computer generated verification record.
        </div>
    </div>
</body>
</html>
