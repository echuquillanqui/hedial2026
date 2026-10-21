<div class="table-responsive">
    <table class="table table-hover table-bordered align-middle bg-white mb-0" style="min-width:1250px">
        <thead class="table-light"><tr><th style="width:85px">Puesto</th><th style="width:135px">Módulo</th><th style="min-width:260px">Nombre completo</th><th style="width:120px">DNI</th><th style="width:120px">Máquina</th><th style="width:155px">Acceso venoso</th><th style="width:155px">Acceso arterial</th></tr></thead>
        <tbody>
        @forelse($patients as $patient)
            @php($latestNurse = $patient->orders->first()?->nurse)
            @php($moduleColor = ['1' => 'danger', '2' => 'warning', '3' => 'success'][$patient->modulo] ?? 'secondary')
            <tr>
                <td><input class="form-control form-control-sm text-center sectorization-field" value="{{ $latestNurse?->puesto }}" data-patient="{{ $patient->id }}" data-field="puesto" aria-label="Puesto de {{ $patient->full_name }}" placeholder="#" @disabled(!$latestNurse)></td>
                <td><select class="form-select form-select-sm sectorization-field module-color-{{ $moduleColor }}" data-patient="{{ $patient->id }}" data-field="modulo" aria-label="Módulo de {{ $patient->full_name }}"><option value="">Sin asignar</option>@foreach(['1' => 'Módulo 1','2' => 'Módulo 2','3' => 'Módulo 3','4' => 'Módulo 4','AISLADO' => 'Aislado'] as $moduleValue => $moduleLabel)<option value="{{ $moduleValue }}" @selected((string)$patient->modulo === (string)$moduleValue)>{{ $moduleLabel }}</option>@endforeach</select></td>
                <td class="fw-semibold">{{ $patient->full_name }}</td><td>{{ $patient->dni }}</td>
                <td><input class="form-control form-control-sm sectorization-field" value="{{ $latestNurse?->numero_maquina }}" data-patient="{{ $patient->id }}" data-field="numero_maquina" aria-label="Máquina de {{ $patient->full_name }}" @disabled(!$latestNurse)></td>
                @foreach(['acceso_venoso' => $patient->acceso_venoso, 'acceso_arterial' => $patient->acceso_arterial] as $field => $value)
                    <td><select class="form-select form-select-sm sectorization-field" data-patient="{{ $patient->id }}" data-field="{{ $field }}" aria-label="{{ str_replace('_', ' ', ucfirst($field)) }} de {{ $patient->full_name }}"><option value="">Sin especificar</option>@foreach(['CVCLP','FAV','INJ','CVCL','CVCT'] as $access)<option value="{{ $access }}" @selected($value === $access)>{{ $access }}</option>@endforeach</select></td>
                @endforeach
            </tr>
        @empty
            <tr><td colspan="7" class="text-center text-muted py-4">No hay pacientes activos en este turno y secuencia.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
