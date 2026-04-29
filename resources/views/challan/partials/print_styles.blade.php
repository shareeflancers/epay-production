<style>
    @page {
        size: A4 {{ ($challan->status ?? 'U') === 'P' ? 'portrait' : 'landscape' }};
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
        width: {{ ($challan->status ?? 'U') === 'P' ? '190mm' : '277mm' }};
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
        padding: 60px;
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
        .bulk-page-break { page-break-after: always; }
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
