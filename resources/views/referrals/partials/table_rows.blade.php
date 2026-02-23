@forelse($referrals as $referral)
    <tr>
        <td class="ps-4 fw-bold text-primary">{{ $referral->referral_code }}<br
            <span>{{ $referral->created_at->format('d/m/Y') }}</span>
        </td>
        <td>
            <div class="fw-bold">{{ $referral->patient->surname }} {{ $referral->patient->last_name }}, {{ $referral->patient->first_name }} {{ $referral->patient->other_names }}</div>
            <small class="text-muted">{{ $referral->patient->dni }}</small>
        </td>
        
        <td>
            <span class="badge bg-success">
                {{ $referral->patient->insurance_type }}
            </span>
        </td>
        <td><div class="small">{{ $referral->origin_facility }}</div></td>
        <td><div class="small">{{ $referral->destination_facility }}</div></td>
        <td class="text-end pe-4">
            <div class="btn-group">
                <a href="{{ route('referrals.show', $referral) }}" class="btn btn-sm btn-outline-secondary me-2">
                    <i class="bi bi-eye"></i>
                </a>

                @php
                    // Normalizamos el régimen para evitar fallas por mayúsculas/minúsculas
                    $regimen = strtoupper($referral->patient->insurance_type ?? '');
                @endphp

                @if($regimen == 'SIS')
                    {{-- BOTÓN PARA MINSA / SIS --}}
                    <a href="{{ route('referrals.pdf', $referral->id) }}" class="btn btn-success btn-sm" target="_blank">
                        <i class="fa fa-file-pdf"></i> Imprimir SIS
                    </a>
                @else
                    {{-- BOTÓN PARA ESSALUD / SALUDPOL (El nuevo formato) --}}
                    <a href="{{ route('referrals.pdf_essalud', $referral->id) }}" class="btn btn-primary btn-sm" target="_blank">
                        <i class="fa fa-file-pdf"></i> Imprimir {{ $regimen }}
                    </a>
                @endif
            </div>
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6" class="text-center py-4 text-muted">No se encontraron resultados.</td>
    </tr>
@endforelse