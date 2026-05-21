{{-- ================================================================ --}}
{{-- CUSTOM SIS_VOUCHER PRINT LAYOUT (decoupled placeholder)          --}}
{{-- ================================================================ --}}
@include('challan.partials.institution_voucher', [
    'challan' => $challan,
    'snapshot' => $snapshot,
    'profile' => $profile,
    'qrCode' => $qrCode,
    'principalProfile' => $principalProfile,
    'principalCnic' => $principalCnic
])
