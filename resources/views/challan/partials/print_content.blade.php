@php
    $snapshot         = is_string($challan->challan_snapshot) ? json_decode($challan->challan_snapshot, true) : $challan->challan_snapshot;
    $profile          = $challan->consumer->profileDetails->first();
    $qrCode           = SimpleSoftwareIO\QrCode\Facades\QrCode::size(100)->generate(route('challan.verify', ['consumer_number' => $challan->consumer->consumer_number]));
    $principalProfile = $principalProfile ?? null;
    $principalCnic    = $principalCnic ?? null;
@endphp

@switch($challan->fee_type)
    @case('voucher')
        @include('challan.partials.institution_voucher', compact('challan', 'snapshot', 'profile', 'qrCode', 'principalProfile', 'principalCnic'))
        @break
    @case('sis_voucher')
        @include('challan.partials.layouts.sis_voucher', compact('challan', 'snapshot', 'profile', 'qrCode', 'principalProfile', 'principalCnic'))
        @break
    @case('induction_fee')
        @include('challan.partials.layouts.induction_fee', compact('challan', 'snapshot', 'profile', 'qrCode', 'principalProfile', 'principalCnic'))
        @break
    @default
        @include('challan.partials.student_challan', compact('challan', 'snapshot', 'profile', 'qrCode'))
@endswitch
