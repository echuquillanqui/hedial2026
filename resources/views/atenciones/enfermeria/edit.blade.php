@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    :root { --medical-blue: #0d6efd; --medical-red: #dc3545; --bg-light: #f4f7f6; }
    body { background-color: var(--bg-light); }
    .form-control-sm, .form-select-sm { border: 1px solid #ced4da !important; border-radius: 4px; transition: all 0.2s; }
    .form-control-sm:focus { border-color: var(--medical-blue) !important; box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15); }
    label { font-size: 0.68rem; font-weight: 800; color: #555; text-transform: uppercase; margin-bottom: 2px; }
    .card-medical { border: none; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
    .header-info { background: white; border-left: 5px solid var(--medical-blue); }
    .nav-tabs .nav-link { font-weight: 700; font-size: 0.85rem; color: #6c757d; border: none; padding: 10px 20px; }
    .nav-tabs .nav-link.active { color: var(--medical-blue); border-bottom: 3px solid var(--medical-blue); background: transparent; }
    .table-monitoreo thead th { background-color: #343a40; color: white; font-size: 0.7rem; text-transform: uppercase; text-align: center; padding: 10px; }
    .table-monitoreo input { border: none !important; width: 100%; text-align: center; background: transparent; font-size: 0.85rem; }
    .was-validated .form-control:invalid, .was-validated .form-select:invalid { border-color: var(--medical-red) !important; background-color: #fff8f8; }
</style>

<div class="container-fluid py-3">
    <form id="nurseForm" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="fw-bold text-dark mb-0"><i class="bi bi-file-earmark-medical-fill text-primary me-2"></i>Hoja de Hemodiálisis</h4>
                <small class="text-muted">
                    Paciente: <strong>{{ $order->patient->surname }} {{ $order->patient->first_name }}</strong> | 
                    DNI: <strong>{{ $order->patient->dni }}</strong> | 
                    Sesión: <strong>{{ $nurse->numero_hd }} de {{ $order->patient->secuencia }}</strong>
                </small>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm"><i class="bi bi-cloud-arrow-up-fill me-1"></i> ACTUALIZAR</button>
                <a href="{{ route('nurses.index') }}" class="btn btn-sm btn-outline-secondary px-3"><i class="bi bi-arrow-left-circle me-1"></i> Volver</a>
            </div>
        </div>

        <div class="card card-medical header-info mb-4 p-3">
            <div class="row g-3">
                <div class="col-md-1"><label>Sesión №</label><div class="h5 fw-bold text-primary mb-0">#{{ $nurse->numero_hd }}</div></div>
                <div class="col-md-2"><label data-label="Puesto">Puesto *</label><input type="text" name="puesto" id="puestoInput" class="form-control form-control-sm fw-bold" value="{{ $nurse->puesto }}" required></div>
                <div class="col-md-2"><label data-label="№ Máquina">№ Máquina *</label><input type="text" name="numero_maquina" id="maquinaInput" class="form-control form-control-sm" value="{{ $nurse->numero_maquina ?? $nurse->puesto }}" required></div>
                <div class="col-md-3"><label>Marca / Modelo</label><input type="text" name="marca_modelo" class="form-control form-control-sm" value="{{ $nurse->marca_modelo ?? 'FRESENIUS/4008S' }}"></div>
                <div class="col-md-2"><label>Frecuencia</label><input type="text" class="form-control form-control-sm bg-light border-0 fw-bold" value="{{ $nurse->frecuencia_hd ?? '3 VECES POR SEMANA' }}" readonly></div>
                <div class="col-md-2"><label>Filtro / Dializador</label><input type="text" name="filtro" class="form-control form-control-sm" value="{{ $nurse->filtro }}"></div>
            </div>
        </div>

        <ul class="nav nav-tabs mb-0" id="nurseTabs">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#t1"><i class="bi bi-play-circle me-1"></i> 1. INICIO</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t2"><i class="bi bi-activity me-1"></i> 2. MONITOREO</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#t3"><i class="bi bi-capsule me-1"></i> 3. MEDICACIÓN / CIERRE</a></li>
        </ul>

        <div class="tab-content card card-medical p-4 mb-4 border-top-0" style="background: white; border-radius: 0 0 8px 8px;">
            
            <div class="tab-pane fade show active" id="t1">
                <div class="row g-3">
                    <div class="col-md-3"><label>Acceso Venoso *</label>
                        <select name="acceso_venoso" class="form-select form-select-sm" required>
                            <option value="">-- Seleccione --</option>
                            @foreach(['CVCLP','FAV','INJ','CVCL','CVCT'] as $opt)<option value="{{ $opt }}" {{ $nurse->acceso_venoso == $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3"><label>Acceso Arterial *</label>
                        <select name="acceso_arterial" class="form-select form-select-sm" required>
                            <option value="">-- Seleccione --</option>
                            @foreach(['CVCLP','FAV','INJ','CVCL','CVCT'] as $opt)<option value="{{ $opt }}" {{ $nurse->acceso_arterial == $opt ? 'selected' : '' }}>{{ $opt }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-2"><label>PA Inicial</label><input type="text" name="pa_inicial" class="form-control form-control-sm" value="{{ $nurse->pa_inicial ?? $order->medical->pa_inicial }}"></div>
                    <div class="col-md-2"><label>Peso Inicial (kg)</label><input type="number" step="0.01" name="peso_inicial" class="form-control form-control-sm" value="{{ $nurse->peso_inicial ?? $order->medical->peso_inicial }}"></div>
                    <div class="col-md-2"><label>UF Prog (L)</label><input type="text" name="uf" class="form-control form-control-sm" value="{{ $nurse->uf ?? $order->medical->uf }}"></div>
                    <div class="col-md-4"><label>Aspecto Filtro</label><input type="text" name="aspecto_dializador" class="form-control form-control-sm" value="{{ $nurse->aspecto_dializador ?? '0' }}"></div>
                    <div class="col-md-8"><label>Enfermero que Inicia *</label>
                        <select name="enfermero_que_inicia_id" class="form-select form-select-sm" required>
                            <option value="">-- Seleccione Profesional --</option>
                            @foreach($enfermeros as $enf)<option value="{{ $enf->id }}" {{ $nurse->enfermero_que_inicia_id == $enf->id ? 'selected' : '' }}>{{ $enf->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="t2">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div><h6 class="fw-bold mb-0 text-primary">Seguimiento Horario</h6><small class="text-muted">Horas programadas: {{ $order->medical->hora_hd }} hrs</small></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3" onclick="autoCalcularHoras()"><i class="bi bi-magic me-1"></i> Autocalcular</button>
                        <button type="button" class="btn btn-dark btn-sm rounded-pill px-3" onclick="addRow()"><i class="bi bi-plus-lg me-1"></i> Fila Manual</button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-monitoreo" id="tableTreatments">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Hora</th><th style="width: 85px;">PA</th><th style="width: 65px;">FC</th><th style="width: 65px;">QB</th><th style="width: 65px;">CND</th>
                                <th style="width: 65px;">RA</th><th style="width: 65px;">RV</th><th style="width: 65px;">PTM</th><th>Observaciones</th><th style="width: 45px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($order->treatments as $t) @include('atenciones.enfermeria.partials.row', ['t' => $t])
                            @empty @include('atenciones.enfermeria.partials.row') @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="tab-pane fade" id="t3">
                <div class="bg-light p-3 rounded mb-4 border shadow-sm">
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2"><i class="bi bi-prescription2 me-2"></i>Medicación Administrada</h6>
                    <div class="row g-2">
                        @php $meds = ['epo2000'=>'EPO 2000','epo4000'=>'EPO 4000','hierro'=>'HIERRO SAC.','vitamina_b12'=>'VIT. B12','calcitriol'=>'CALCITRIOL']; @endphp
                        @foreach($meds as $key => $label)
                        <div class="col">
                            <label class="text-dark">{{ $label }}</label>
                            <input type="text" name="{{ $key }}" class="form-control form-control-sm" value="{{ $nurse->$key }}">
                        </div>
                        @endforeach
                        <div class="col-md-3"><label>Otros Medicamentos</label><input type="text" name="otros_medicamentos" class="form-control form-control-sm" value="{{ $nurse->otros_medicamentos }}"></div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3"><label class="text-danger fw-bold">S (Subjetivo)</label><textarea name="s" class="form-control form-control-sm" rows="3">{{ $nurse->s }}</textarea></div>
                    <div class="col-md-3"><label class="text-warning fw-bold">O (Objetivo)</label><textarea name="o" class="form-control form-control-sm" rows="3">{{ $nurse->o }}</textarea></div>
                    <div class="col-md-3"><label class="text-success fw-bold">A (Análisis)</label><textarea name="a" class="form-control form-control-sm" rows="3">{{ $nurse->a }}</textarea></div>
                    <div class="col-md-3"><label class="text-info fw-bold">P (Plan)</label><textarea name="p" class="form-control form-control-sm" rows="3">{{ $nurse->p }}</textarea></div>
                </div>

                <div class="row g-3 border-top pt-3 bg-light rounded">
                    <div class="col-md-2"><label data-label="PA Final">PA Final</label><input type="text" name="pa_final" class="form-control form-control-sm closure-field" value="{{ $nurse->pa_final }}"></div>
                    <div class="col-md-2"><label data-label="Peso Final">Peso Final</label><input type="number" step="0.01" name="peso_final" class="form-control form-control-sm closure-field" value="{{ $nurse->peso_final }}"></div>
                    <div class="col-md-5"><label data-label="Obs. Final">Observación Final</label><input type="text" name="observacion_final" class="form-control form-control-sm closure-field" value="{{ $nurse->observacion_final }}"></div>
                    <div class="col-md-3">
                        <label data-label="Enfermero Cierre">Enfermero Cierre</label>
                        <select name="enfermero_que_finaliza_id" class="form-select form-select-sm closure-field">
                            <option value="">-- Seleccione --</option>
                            @foreach($enfermeros as $enf)<option value="{{ $enf->id }}" {{ $nurse->enfermero_que_finaliza_id == $enf->id ? 'selected' : '' }}>{{ $enf->name }}</option>@endforeach
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    // Sincronizar Puesto -> Máquina
    document.getElementById('puestoInput').addEventListener('input', function() {
        document.getElementById('maquinaInput').value = this.value;
    });

    // Lógica Autocalcular (Suma 1 hora desde la primera fila)
    function autoCalcularHoras() {
        const tbody = document.querySelector('#tableTreatments tbody');
        const primeraFila = tbody.querySelector('tr');
        const horaBaseInput = primeraFila ? primeraFila.querySelector('.hora-input') : null;

        if (!horaBaseInput || !horaBaseInput.value) {
            Swal.fire({ icon: 'info', title: 'Hora de Inicio Vacía', text: 'Escriba la hora en la primera fila para generar la secuencia.' });
            return;
        }

        const duracion = parseFloat("{{ $order->medical->hora_hd ?? 0 }}");
        const numFilas = Math.floor(duracion) + 1;
        let [hBase, mBase] = horaBaseInput.value.split(':').map(Number);

        tbody.innerHTML = '';
        for (let i = 0; i < numFilas; i++) {
            let h = hBase + i;
            let m = mBase;
            // Ajuste fracción final (ej: 3.5 -> suma 30 min al final)
            if (i === numFilas - 1 && (duracion % 1) !== 0) {
                m += Math.round((duracion % 1) * 60);
                while(m >= 60) { h++; m -= 60; }
            }
            const timeStr = `${String(h % 24).padStart(2, '0')}:${String(m % 60).padStart(2, '0')}`;
            insertarFila(timeStr);
        }
    }

    function insertarFila(hora) {
        const row = `<tr>
            <td><input type="time" name="t_hora[]" class="hora-input" value="${hora}" required></td>
            <td style="width: 85px;"><input type="text" name="t_pa[]" placeholder="---/---"></td>
            <td style="width: 65px;"><input type="number" name="t_fc[]"></td>
            <td style="width: 65px;"><input type="text" name="t_qb[]"></td>
            <td style="width: 65px;"><input type="number" step="0.1" name="t_cnd[]"></td>
            <td style="width: 65px;"><input type="number" name="t_ra[]"></td>
            <td style="width: 65px;"><input type="number" name="t_rv[]"></td>
            <td style="width: 65px;"><input type="number" name="t_ptm[]"></td>
            <td><input type="text" name="t_obs[]" class="text-start ps-2" style="width: 100%"></td>
            <td class="text-center"><button type="button" class="btn btn-sm text-danger" onclick="confirmDeleteRow(this)"><i class="bi bi-trash3-fill"></i></button></td>
        </tr>`;
        document.querySelector('#tableTreatments tbody').insertAdjacentHTML('beforeend', row);
    }

    function addRow() { insertarFila(''); }

    function confirmDeleteRow(btn) {
        Swal.fire({
            title: '¿Eliminar fila?',
            text: "Se borrarán los signos registrados en esta hora.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => { if (result.isConfirmed) btn.closest('tr').remove(); });
    }

    // Submit con Validaciones
    document.getElementById('nurseForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        // Validación dinámica de cierre
        const closure = document.querySelectorAll('.closure-field');
        const isClosing = Array.from(closure).some(el => el.value.trim() !== "");
        closure.forEach(el => isClosing ? el.setAttribute('required','required') : el.removeAttribute('required'));

        if (!this.checkValidity()) {
            this.classList.add('was-validated');
            return Swal.fire({ icon: 'warning', title: 'Atención', text: 'Complete los campos obligatorios marcados en rojo.' });
        }

        Swal.fire({ title: 'Guardando...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
        
        fetch("{{ route('nurses.update', $nurse->id) }}", {
            method: 'POST',
            body: new FormData(this),
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if(data.status === 'success') Swal.fire({ icon: 'success', title: '¡Éxito!', text: data.message, timer: 1500, showConfirmButton: false });
            else Swal.fire({ icon: 'error', title: 'Error', text: data.message });
        });
    });
</script>
@endsection