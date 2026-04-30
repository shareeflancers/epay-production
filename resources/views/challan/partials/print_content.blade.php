@php
    $snapshot = is_string($challan->challan_snapshot) ? json_decode($challan->challan_snapshot, true) : $challan->challan_snapshot;
    $profile = $challan->consumer->profileDetails->first();
    $qrCode = SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate(route('challan.verify', ['consumer_number' => $challan->consumer->consumer_number]));
@endphp

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
                    <img src="{{ asset('assets/logo/logo.png') }}" alt="Logo" class="logo" onerror="this.src='https://sis.fgei.gov.pk/assets/images/logo.png'">
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
                    <div class="consumer-value" style="font-size: 15px; color: #007bff;">{{ '111787474' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</div>
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
                    <td>{{ $challan->schoolClass->name ?? $profile->class }} - {{ $profile->section ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Session</th>
                    <td>{{ $challan->yearSession->name ?? ($snapshot['year_session']['name'] ?? 'N/A') }}</td>
                </tr>
                <tr>
                    <th>Challan Number</th>
                    <td>{{ $challan->challan_no }}</td>
                </tr>
                @if($challan->amount_arrears > 0)
                <tr>
                    <th>Current Fee</th>
                    <td>Rs. {{ number_format($challan->amount_base, 0) }}/-</td>
                </tr>
                <tr>
                    <th>Arrears Paid</th>
                    <td>Rs. {{ number_format($challan->amount_arrears, 0) }}/-</td>
                </tr>
                @endif
                <tr>
                    <th>Total Amount Paid</th>
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
            $copies = ['Bank Copy', 'Student Copy', 'Institution Copy', 'Payment Instructions'];
            $billingMonth = $challan->reserved ? trim(explode('|', $challan->reserved)[1]) : 'Fee Challan';
        @endphp

        @foreach($copies as $copy)
        <div class="challan-copy">
            @if($copy === 'Payment Instructions')
                <div class="header">
                    <div class="logo-section">
                        <img src="{{ asset('assets/logo/logo.png') }}" alt="Logo" class="logo" onerror="this.src='https://sis.fgei.gov.pk/assets/images/logo.png'">
                        <div>
                            <h1 class="institution-name" style="font-size: 12px;">HOW TO PAY YOUR FEE</h1>
                            <div class="billing-month">Step-by-Step Guide</div>
                        </div>
                    </div>
                    <span class="copy-tag">{{ $copy }}</span>
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
                    <div class="consumer-value" style="font-size: 11px;">{{ '474' . $challan->consumer->institution_id . $challan->consumer->consumer_number }}</div>
                </div>
            </div>

            <table class="details-table" style="font-size: 9px; margin: 5px 0;">
                <tr><th>Student</th><td>{{ $profile->name }}</td></tr>
                <tr><th>Class / Section</th><td>{{ ($challan->schoolClass->name ?? $profile->class) }} - {{ $profile->section ?? '-' }}</td></tr>
                <tr><th>Session</th><td>{{ $challan->yearSession->name ?? ($snapshot['year_session']['name'] ?? 'N/A') }}</td></tr>
                <tr><th>Due Date</th><td style="color: red;">{{ $challan->due_date->format('d-M-Y') }}</td></tr>

                @if($challan->amount_arrears > 0)
                    <tr><th>Current Fee</th><td>Rs. {{ number_format($challan->amount_base, 0) }}/-</td></tr>
                    <tr><th>Arrears</th><td>Rs. {{ number_format($challan->amount_arrears, 0) }}/-</td></tr>
                @endif

                <tr><th>Payable</th><td style="font-weight: 900; font-size: 12px;">Rs. {{ number_format($challan->amount_within_dueDate, 0) }}/-</td></tr>
            </table>

            <div style="font-size: 8px; margin: 5px 0; padding: 4px; border: 1px dashed #ccc; text-align: center; color: #444; border-radius: 4px;">
                <strong>Note:</strong> Rs. 18/- Service Charges apply if paid via 1Bill / Bank Apps.
            </div>

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
            @endif
        </div>
        @endforeach
    @endif
</div>
