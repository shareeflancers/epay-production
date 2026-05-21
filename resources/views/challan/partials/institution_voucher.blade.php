{{-- ================================================================ --}}
{{-- VOUCHER PRINT FORMAT (4 Copies: Bank / Student / Inst / Paym)    --}}
{{-- ================================================================ --}}
@php
    $voucherCopies  = ['Bank Copy', 'Student Copy', 'Institution Copy', 'Payment Instructions'];
    $billingMonth   = $snapshot['billing_month'] ?? (trim(explode('|', $challan->reserved)[1]) ?? 'Voucher');
    $institutionName = $challan->institution->name ?? ($snapshot['institution']['name'] ?? 'FGEI (C/G) DIRECTORATE');
    $aggregatedHeads = $snapshot['aggregated_heads'] ?? [];
    $studentStats    = $snapshot['student_challan_stats'] ?? [];
    $arrears         = $snapshot['arrears_calculation'] ?? [];
    $arrearsAmount   = (float)($arrears['amount_arrears'] ?? $challan->amount_arrears ?? 0);
    $amountBase      = (float)($challan->amount_base ?? 0);
    $totalPayable    = (float)($challan->amount_within_dueDate ?? 0);
@endphp

<div class="container">
@foreach($voucherCopies as $vCopy)
<div class="challan-copy voucher-copy">
    @if($vCopy === 'Payment Instructions')
        <div class="header">
            <div class="logo-section">
                <img src="{{ asset('assets/logo/logo.png') }}" alt="Logo" class="logo" onerror="this.src='https://sis.fgei.gov.pk/assets/images/logo.png'">
                <div>
                    <h1 class="institution-name" style="font-size: 12px;">HOW TO PAY YOUR FEE</h1>
                    <div class="billing-month">Step-by-Step Guide</div>
                </div>
            </div>
            <span class="copy-tag">{{ $vCopy }}</span>
        </div>

        <div style="font-size: 9px; margin-top: 10px;">
            <div style="background: #f1fcf4; padding: 7px; border-radius: 8px; border: 1px solid #d1f2d9; margin-bottom: 8px;">
                <strong style="color: #198754; display: block; margin-bottom: 3px; font-size: 8px;">METHOD 1: ASKARI MOBILE BANKING APP / OVER-THE-COUNTER</strong>
                <div style="font-size: 8px; line-height: 1.2;">
                    • <strong>App:</strong> Login → Payments → School Fee → Select <strong>FGEI e-Portal</strong> → Enter ID: <strong style="font-size: 10px; color: #198754;">{{ '474' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</strong><br>
                    • <strong>OTC:</strong> Visit any <strong>Askari Bank</strong> branch. Pay "FGEI Fee" using ID: <strong style="font-size: 10px; color: #198754;">{{ '474' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</strong>
                </div>
            </div>

            <div style="background: #f8f9fa; padding: 7px; border-radius: 8px; border: 1px solid #e9ecef; margin-bottom: 8px;">
                <strong style="color: #007bff; display: block; margin-bottom: 3px; font-size: 8px;">METHOD 2: ALL OTHER BANKING APPS / MOBILE WALLETS</strong>
                <p style="font-size: 8px; margin: 0 0 4px 0;">(EasyPaisa, JazzCash, HBL, Bank Alfalah, UBL etc.)</p>
                <ol style="margin: 0; padding-left: 12px; font-size: 8px;">
                    <li>Open App → <strong>Bill Payment</strong> → <strong>1Bill</strong> → <strong>Invoice/Challan</strong>.</li>
                    <li>Enter PSID: <strong style="font-size: 10px; color: #d63384;">{{ '111787474' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</strong></li>
                </ol>
            </div>

            <div style="background: #f0f7ff; padding: 7px; border-radius: 8px; border: 1px solid #cfe2ff;">
                <strong style="color: #052c65; display: block; margin-bottom: 3px; font-size: 8px;">METHOD 3: OVER-THE-COUNTER (OTHER THAN ASKARI BANK)</strong>
                <p style="margin: 0; padding-left: 5px; font-size: 8px;">Visit any other bank branch and pay <strong>1Bill Invoice</strong> using PSID: <strong style="color: #052c65;">{{ '111787474' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</strong></p>
            </div>
        </div>
    @else
        {{-- watermark --}}
        <div class="diagonal-watermark">
            @for($i=0;$i<10;$i++)
            <div class="watermark-row">
                @for($j=0;$j<4;$j++)<span class="watermark-item">{{ $challan->challan_no }}</span>@endfor
            </div>
            @endfor
        </div>

        {{-- Header --}}
        <div class="header">
            <div class="logo-section">
                <img src="{{ asset('assets/logo/logo.png') }}" alt="Logo" class="logo" onerror="this.src='https://sis.fgei.gov.pk/assets/images/logo.png'">
                <div>
                    <h1 class="institution-name" style="font-size:11px;">{{ $institutionName }}</h1>
                    <div class="billing-month">{{ $billingMonth }}</div>
                </div>
            </div>
            <span class="copy-tag">{{ $vCopy }}</span>
        </div>

        {{-- QR + Consumer Info --}}
        <div class="qr-section">
            <div class="qr-code">
                {!! $qrCode !!}
                <div style="font-size:7px;font-weight:800;margin-top:4px;text-align:center;color:#000;line-height:1;">SCAN TO CHECK<br>STATUS</div>
            </div>
            <div class="consumer-info">
                <div class="consumer-label">Consumer Number</div>
                <div class="consumer-value">{{ $challan->consumer->consumer_number }}</div>
                <div class="consumer-label" style="margin-top:3px;">Challan No</div>
                <div class="consumer-value" style="font-size:11px;">{{ $challan->challan_no }}</div>
                <div class="consumer-label" style="margin-top:3px;">1Link PSID</div>
                <div class="consumer-value" style="font-size:12px;color:#007bff;">{{ '111787474' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</div>
            </div>
        </div>

        {{-- Principal Info --}}
        <table class="details-table" style="font-size:9px;margin:5px 0;">
            <tr>
                <th style="width:38%;">Principal</th>
                <td>{{ $principalProfile->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <th>Institution</th>
                <td>{{ $institutionName }}</td>
            </tr>
            <tr>
                <th>Billing Month</th>
                <td>{{ $billingMonth }}</td>
            </tr>
            <tr>
                <th>Due Date</th>
                <td style="color:red;">{{ $challan->due_date->format('d-M-Y') }}</td>
            </tr>
        </table>

        {{-- Fee Head Breakdown --}}
        @php
            $prevDetails = $snapshot['previous_voucher_details'] ?? null;
        @endphp

        @if($prevDetails)
            {{-- Comparative breakdown when previous voucher is found --}}
            @php
                $prevHeads = $prevDetails['aggregated_heads'] ?? [];
                $allHeads = array_unique(array_merge(array_keys($aggregatedHeads), array_keys($prevHeads)));
            @endphp
            <div style="font-size:8px;font-weight:700;margin:4px 0 2px;border-bottom:1px solid #333;padding-bottom:2px;text-transform:uppercase;letter-spacing:.5px;">
                Fund Head Comparison
            </div>
            <table class="details-table" style="font-size:8.5px;margin:0 0 4px;width:100%;">
                <thead>
                    <tr style="border-bottom:1px solid #ddd;background:#f5f5f5;">
                        <th style="text-align:left;font-weight:700;padding:2px 4px;width:46%;">Fund Head</th>
                        <th style="text-align:right;font-weight:700;padding:2px 4px;width:27%;">Prev ({{ $prevDetails['billing_month'] ?? 'Month' }})</th>
                        <th style="text-align:right;font-weight:700;padding:2px 4px;width:27%;">Curr ({{ $billingMonth }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allHeads as $headName)
                        @php
                            $currAmt = $aggregatedHeads[$headName] ?? 0;
                            $prevAmt = $prevHeads[$headName] ?? 0;
                        @endphp
                        @if($currAmt > 0 || $prevAmt > 0)
                        <tr>
                            <th style="text-align:left;font-weight:600;padding:2px 4px;">{{ $headName }}</th>
                            <td style="text-align:right;padding:2px 4px;">{{ $prevAmt > 0 ? 'Rs. ' . number_format($prevAmt, 0) : '-' }}</td>
                            <td style="text-align:right;font-weight:700;padding:2px 4px;">{{ $currAmt > 0 ? 'Rs. ' . number_format($currAmt, 0) : '-' }}</td>
                        </tr>
                        @endif
                    @endforeach
                    @if(!empty($arrears['details']))
                        @foreach($arrears['details'] as $detail)
                            @if(($detail['amount'] ?? 0) > 0)
                            <tr style="background:#fff8e1;">
                                <th style="text-align:left;color:#c0392b;font-weight:700;padding:2px 4px;">Arrears ({{ $detail['billing_month'] ?? 'Prev Month' }})</th>
                                <td style="text-align:right;padding:2px 4px;color:#c0392b;">-</td>
                                <td style="text-align:right;font-weight:700;padding:2px 4px;color:#c0392b;">Rs. {{ number_format($detail['amount'], 0) }}/-</td>
                            </tr>
                            @endif
                        @endforeach
                    @elseif($arrearsAmount > 0)
                    <tr style="background:#fff8e1;">
                        <th style="text-align:left;color:#c0392b;font-weight:700;padding:2px 4px;">Arrears (Prev Month)</th>
                        <td style="text-align:right;padding:2px 4px;color:#c0392b;">-</td>
                        <td style="text-align:right;font-weight:700;padding:2px 4px;color:#c0392b;">Rs. {{ number_format($arrearsAmount, 0) }}/-</td>
                    </tr>
                    @endif
                    <tr style="background:#f0f7ff;border-top:1px solid #333;">
                        <th style="text-align:left;font-size:9.5px;font-weight:900;padding:2px 4px;">Total Payable</th>
                        <td style="text-align:right;font-size:9px;font-weight:600;padding:2px 4px;">{{ ($prevDetails['amount'] ?? null) ? 'Rs. ' . number_format($prevDetails['amount'], 0) : '-' }}</td>
                        <td style="text-align:right;font-size:9.5px;font-weight:900;padding:2px 4px;color:#007bff;">Rs. {{ number_format($totalPayable, 0) }}/-</td>
                    </tr>
                </tbody>
            </table>
        @else
            {{-- Standard single month breakdown --}}
            @if(!empty($aggregatedHeads))
            <div style="font-size:8px;font-weight:700;margin:6px 0 2px;border-bottom:1px solid #333;padding-bottom:2px;text-transform:uppercase;letter-spacing:.5px;">
                Fund Head Breakdown
            </div>
            <table class="details-table" style="font-size:9px;margin:0 0 4px;">
                @foreach($aggregatedHeads as $headName => $headAmount)
                @if($headAmount > 0)
                <tr>
                    <th style="width:60%;font-weight:600;">{{ $headName }}</th>
                    <td>Rs. {{ number_format($headAmount, 0) }}/-</td>
                </tr>
                @endif
                @endforeach
                @if(!empty($arrears['details']))
                    @foreach($arrears['details'] as $detail)
                        @if(($detail['amount'] ?? 0) > 0)
                        <tr style="background:#fff8e1;">
                            <th style="color:#c0392b;">Arrears ({{ $detail['billing_month'] ?? 'Previous Month' }})</th>
                            <td style="color:#c0392b;">Rs. {{ number_format($detail['amount'], 0) }}/-</td>
                        </tr>
                        @endif
                    @endforeach
                @elseif($arrearsAmount > 0)
                <tr style="background:#fff8e1;">
                    <th style="color:#c0392b;">Arrears (Previous Month)</th>
                    <td style="color:#c0392b;">Rs. {{ number_format($arrearsAmount, 0) }}/-</td>
                </tr>
                @endif
                <tr style="background:#f0f7ff;">
                    <th style="font-size:11px;font-weight:900;">Total Payable</th>
                    <td style="font-size:12px;font-weight:900;">Rs. {{ number_format($totalPayable, 0) }}/-</td>
                </tr>
            </table>
            @else
            <table class="details-table" style="font-size:9px;margin:5px 0;">
                @if(!empty($arrears['details']))
                    <tr><th>Fund Collection</th><td>Rs. {{ number_format($amountBase, 0) }}/-</td></tr>
                    @foreach($arrears['details'] as $detail)
                        @if(($detail['amount'] ?? 0) > 0)
                        <tr style="background:#fff8e1;">
                            <th style="color:#c0392b;">Arrears ({{ $detail['billing_month'] ?? 'Arrears' }})</th>
                            <td style="color:#c0392b;">Rs. {{ number_format($detail['amount'], 0) }}/-</td>
                        </tr>
                        @endif
                    @endforeach
                @elseif($arrearsAmount > 0)
                <tr><th>Fund Collection</th><td>Rs. {{ number_format($amountBase, 0) }}/-</td></tr>
                <tr style="background:#fff8e1;"><th style="color:#c0392b;">Arrears</th><td style="color:#c0392b;">Rs. {{ number_format($arrearsAmount, 0) }}/-</td></tr>
                @endif
                <tr style="background:#f0f7ff;"><th style="font-weight:900;">Total Payable</th><td style="font-size:12px;font-weight:900;">Rs. {{ number_format($totalPayable, 0) }}/-</td></tr>
            </table>
            @endif
        @endif

        {{-- Student Challan Stats --}}
        @if(!empty($studentStats))
            @php
                $prevStats = $prevDetails['student_challan_stats'] ?? null;
            @endphp
            @if($prevDetails && !empty($prevStats))
                <div style="font-size:8px;font-weight:700;margin:5px 0 2px;border-bottom:1px solid #333;padding-bottom:2px;text-transform:uppercase;letter-spacing:.5px;">
                    Student Challan Statistics Comparison
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:8px;margin-bottom:4px;text-align:center;">
                    <thead>
                        <tr style="background:#f5f5f5;font-weight:700;">
                            <th style="padding:2px;border:1px solid #ddd;text-align:left;font-size:7px;">Billing Month</th>
                            <th style="padding:2px;border:1px solid #ddd;font-size:7px;">Total</th>
                            <th style="padding:2px;border:1px solid #ddd;color:#27ae60;font-size:7px;">Paid</th>
                            <th style="padding:2px;border:1px solid #ddd;color:#c0392b;font-size:7px;">Unpaid</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding:2px;border:1px solid #ddd;text-align:left;font-weight:600;background:#fafafa;font-size:7px;">Prev ({{ $prevDetails['billing_month'] ?? 'Month' }})</td>
                            <td style="padding:2px;border:1px solid #ddd;font-size:7px;">{{ $prevStats['total'] ?? '-' }}</td>
                            <td style="padding:2px;border:1px solid #ddd;color:#27ae60;font-size:7px;">{{ $prevStats['paid'] ?? '-' }}</td>
                            <td style="padding:2px;border:1px solid #ddd;color:#c0392b;font-size:7px;">{{ $prevStats['unpaid'] ?? '-' }}</td>
                        </tr>
                        <tr style="font-weight:700;background:#f0f8ff;">
                            <td style="padding:2px;border:1px solid #ddd;text-align:left;font-weight:700;font-size:7px;">Curr ({{ $billingMonth }})</td>
                            <td style="padding:2px;border:1px solid #ddd;font-size:7px;">{{ $studentStats['total'] ?? '-' }}</td>
                            <td style="padding:2px;border:1px solid #ddd;color:#27ae60;font-size:7px;">{{ $studentStats['paid'] ?? '-' }}</td>
                            <td style="padding:2px;border:1px solid #ddd;color:#c0392b;font-size:7px;">{{ $studentStats['unpaid'] ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <div style="font-size:8px;font-weight:700;margin:6px 0 2px;border-bottom:1px solid #333;padding-bottom:2px;text-transform:uppercase;letter-spacing:.5px;">
                    Student Challan Statistics — {{ $studentStats['billing_month'] ?? $billingMonth }}
                </div>
                <table style="width:100%;border-collapse:collapse;font-size:8.5px;margin-bottom:4px;">
                    <tr style="background:#f5f5f5;text-align:center;">
                        <th style="padding:3px 4px;border:1px solid #ddd;">Total</th>
                        <th style="padding:3px 4px;border:1px solid #ddd;color:#27ae60;">Paid</th>
                        <th style="padding:3px 4px;border:1px solid #ddd;color:#c0392b;">Unpaid</th>
                    </tr>
                    <tr style="text-align:center;font-weight:700;">
                        <td style="padding:3px 4px;border:1px solid #ddd;">{{ $studentStats['total'] ?? '-' }}</td>
                        <td style="padding:3px 4px;border:1px solid #ddd;color:#27ae60;">{{ $studentStats['paid'] ?? '-' }}</td>
                        <td style="padding:3px 4px;border:1px solid #ddd;color:#c0392b;">{{ $studentStats['unpaid'] ?? '-' }}</td>
                    </tr>
                </table>
            @endif
        @endif

        {{-- In Words --}}
        <div style="font-size:9px;font-style:italic;margin-top:4px;">
            <strong>In Words:</strong> {{ \App\Helpers\NumberHelper::amountInWords($totalPayable) }}
        </div>

        {{-- Signatures --}}
        <div class="signature-section">
            <div class="bank-stamp-area">Bank Stamp</div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Principal Signature</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Bank Officer</div>
            </div>
        </div>
    @endif
</div>
@endforeach
</div>
