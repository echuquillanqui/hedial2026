@php
    $stampUser = $user ?? null;
    $stampRole = $role ?? 'doctor';
    $stampName = mb_strtoupper($name ?? $stampUser?->name ?? 'PROFESIONAL NO REGISTRADO');
    $stampProfession = match($stampRole) {
        'nurse' => 'ENFERMERA(O)',
        'nutritionist' => 'NUTRICIONISTA',
        'psychologist' => 'PSICOLOGA(O)',
        'social_worker' => 'TRABAJADOR(A) SOCIAL',
        default => 'NEFROLOGO',
    };
    $stampLicense = $license ?? $stampUser?->license_number;
    $stampSpecialty = $specialty ?? $stampUser?->specialty_number;
    $licensePrefix = match($stampRole) {
        'nurse' => 'C.E.P.',
        'doctor' => 'C.M.P.',
        default => 'COLEGIATURA',
    };
@endphp
<div style="display:inline-block;min-width:190px;border-top:1px solid #111;padding:3px 8px 0;text-align:center;font-family:DejaVu Sans,sans-serif;font-size:7px;font-weight:bold;line-height:1.35;text-transform:uppercase;">
    <div>{{ $stampName }}</div>
    <div>{{ $stampProfession }}</div>
    <div>{{ $licensePrefix }} {{ $stampLicense ?: 'NO REGISTRADO' }}@if($stampRole === 'doctor' && $stampSpecialty) · R.N.E. {{ $stampSpecialty }}@endif</div>
</div>
