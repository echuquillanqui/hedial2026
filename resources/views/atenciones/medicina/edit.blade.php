@extends('layouts.app')

@section('content')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
    .form-control-sm, .form-select-sm { font-size: 0.75rem; padding: 0.2rem 0.4rem; }
    label { font-size: 0.7rem; font-weight: 700; color: #444; text-transform: uppercase; margin-bottom: 1px; display: block; }
    .card-header { padding: 0.3rem 0.8rem; font-size: 0.8rem; background-color: #f8f9fa; font-weight: bold; }
    .row-compact { margin-bottom: 0.4rem; }
    .bg-machine { background-color: #f0f7f4; border-left: 4px solid #198754; }
    
    /* Efecto visual rojo para campos vacíos (excepto indicaciones) */
    .was-validated .form-control:placeholder-shown:not([required]):not([name="indicaciones"]),
    .was-validated .form-select:has(option[value=""]:checked):not([required]),
    .was-validated textarea:placeholder-shown:not([required]):not([name="indicaciones"]) {
        border-color: #dc3545 !important;
        background-color: #fff8f8;
    }

    .was-validated .form-control:invalid, 
    .was-validated .form-select:invalid {
        border-color: #dc3545 !important;
        background-image: none;
    }
</style>

<div class="container-fluid px-0 py-0">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold mb-0 text-uppercase">
            <i class="bi bi-file-medical-fill text-success me-1"></i> 
            EDITAR FICHA MÉDICA: {{ $order->patient->surname }} {{ $order->patient->first_name }}
        </h6>
        <div class="d-print-none">
            <a href="{{ route('medicals.index') }}" class="btn btn-sm btn-outline-danger">VOLVER</a>
        </div>
    </div>

    <form action="{{ route('medicals.update', $medical->id) }}" method="POST" id="medicalForm" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <div class="card shadow-sm mb-2">
            <div class="card-body p-2">
                <div class="row g-2 row-compact">
                    <div class="col-md-2">
                        <label>Hora Inicial</label>
                        <input type="time" name="hora_inicial" id="hora_inicial" class="form-control form-control-sm" value="{{ old('hora_inicial', $medical->hora_inicial) }}">
                    </div>
                    <div class="col-md-2">
                        <label>Peso Inicial (kg)</label>
                        <input type="number" step="0.1" name="peso_inicial" class="form-control form-control-sm" value="{{ old('peso_inicial', $medical->peso_inicial) }}" required placeholder=" ">
                    </div>
                    <div class="col-md-2">
                        <label>PA Inicial</label>
                        <input type="text" name="pa_inicial" class="form-control form-control-sm fw-bold" value="{{ old('pa_inicial', $medical->pa_inicial) }}" placeholder="120/80" required>
                    </div>
                    <div class="col-md-2">
                        <label>Frec. Cardíaca</label>
                        <input type="number" name="frecuencia_cardiaca" class="form-control form-control-sm" value="{{ old('frecuencia_cardiaca', $medical->frecuencia_cardiaca) }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>SO2 (%)</label>
                        <input type="number" name="so2" class="form-control form-control-sm" value="{{ old('so2', $medical->so2) }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>FiO2</label>
                        <input type="number" step="0.01" name="fio2" class="form-control form-control-sm" value="{{ old('fio2', $medical->fio2 ?? 0.21) }}" placeholder=" ">
                    </div>
                    <div class="col-md-2">
                        <label>Temp (°C)</label>
                        <input type="number" step="0.1" name="temperatura" class="form-control form-control-sm" value="{{ old('temperatura', $medical->temperatura) }}" placeholder=" ">
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-md-3">
                        <label>Problemas Clínicos</label>
                        <textarea name="problemas_clinicos" class="form-control form-control-sm" rows="3" placeholder=" ">{{ old('problemas_clinicos', $medical->problemas_clinicos ?? "INSUFICIENCIA RENAL TERMINAL (N18.0), \nANEMIA CRONICA (D63.8)") }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label>Evaluación</label>
                        <textarea name="evaluacion" class="form-control form-control-sm" rows="3" placeholder=" ">{{ old('evaluacion', $medical->evaluacion) }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label>Indicaciones (Opcional)</label>
                        <textarea name="indicaciones" class="form-control form-control-sm" rows="3">{{ old('indicaciones', $medical->indicaciones) }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label>Signos y Síntomas</label>
                        <textarea name="signos_sintomas" class="form-control form-control-sm" rows="3" placeholder=" ">{{ old('signos_sintomas', $medical->signos_sintomas ?? "Niega") }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-2">
            <div class="card-header py-1">MEDICACIÓN</div>
            <div class="card-body p-2">
                <div class="row g-2">
                    @foreach(['epo2000' => 'EPO 2k', 'epo4000' => 'EPO 4k', 'hierro' => 'Hierro', 'vitamina_b12' => 'Vit B12', 'calcitriol' => 'Calcitriol', 'heparina' => 'Heparina'] as $name => $label)
                    <div class="col">
                        <label>{{ $label }}</label>
                        <input type="text" name="{{ $name }}" class="form-control form-control-sm" value="{{ old($name, $medical->$name) }}" placeholder=" ">
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-2 bg-machine">
            <div class="card-header py-1 bg-transparent border-bottom">MONITOREO TÉCNICO HD</div>
            <div class="card-body p-2">
                <div class="row g-2 row-compact">
                    <div class="col-md-1">
                        <label class="text-success">Horas HD</label>
                        <input type="number" step="0.1" name="hora_hd" class="form-control form-control-sm border-success" value="{{ old('hora_hd', $medical->hora_hd) }}" required placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>Peso Seco</label>
                        <input type="number" step="0.1" name="peso_seco" class="form-control form-control-sm" value="{{ old('peso_seco', $medical->peso_seco) }}" placeholder=" ">
                    </div>
                    <div class="col-md-2">
                        <label>UF (L)</label>
                        <input type="text" name="uf" class="form-control form-control-sm fw-bold" value="{{ old('uf', $medical->uf) }}" placeholder="4000 AT" required>
                    </div>
                    <div class="col-md-1">
                        <label>QB</label>
                        <input type="number" name="qb" class="form-control form-control-sm" value="{{ old('qb', $medical->qb) }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>QD</label>
                        <input type="number" name="qd" class="form-control form-control-sm" value="{{ old('qd', $medical->qd ?? 500) }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>Bicarb.</label>
                        <input type="number" name="bicarbonato" class="form-control form-control-sm" value="{{ old('bicarbonato', $medical->bicarbonato ?? 35) }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>Na Inicial</label>
                        <input type="number" name="na_inicial" class="form-control form-control-sm" value="{{ old('na_inicial', $medical->na_inicial) }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>CND</label>
                        <input type="number" step="0.1" name="cnd" class="form-control form-control-sm" value="{{ old('cnd', $medical->cnd) }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>Na Final</label>
                        <input type="number" name="na_final" class="form-control form-control-sm" value="{{ old('na_final', $medical->na_final) }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>Perf. Na</label>
                        <input type="text" name="perfil_na" class="form-control form-control-sm" value="{{ old('perfil_na', $medical->perfil_na ?? "PERFIL L") }}" placeholder=" ">
                    </div>
                    <div class="col-md-1">
                        <label>Perfil UF</label>
                        <input type="text" name="perfil_uf" class="form-control form-control-sm" value="{{ old('perfil_uf', $medical->perfil_uf ?? "PERFIL L") }}" placeholder=" ">
                    </div>
                </div>

                <div class="row g-2">
                    <div class="col-md-2">
                        <label>Área Filtro / Membrana</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="area_filtro" class="form-control" value="{{ old('area_filtro', $medical->area_filtro) }}" placeholder=" ">
                            <input type="text" name="membrana" class="form-control" value="{{ old('membrana', $medical->membrana ?? "PSF") }}" placeholder=" ">
                        </div>
                    </div>
                    <div class="col-md-7">
                        <label>Evaluación Final</label>
                        <textarea name="evaluacion_final" class="form-control form-control-sm" rows="1" placeholder=" ">{{ old('evaluacion_final', $medical->evaluacion_final ?? "SIN COMPLICACIONES") }}</textarea>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-bold">HORA FINAL (Terminar sesión)</label>
                        <input type="time" name="hora_final" id="hora_final" class="form-control form-control-sm" value="{{ old('hora_final', $medical->hora_final) }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-dark">
            <div class="card-body p-2 bg-light">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="text-primary"><i class="bi bi-person-fill"></i> MÉDICO INICIA HD</label>
                        <select name="usuario_que_inicia_hd" class="form-select form-select-sm" required>
                            <option value="">-- Seleccionar --</option>
                            @foreach($medicos as $medico)
                                <option value="{{ $medico->id }}" 
                                    @if(old('usuario_que_inicia_hd', is_numeric($medical->usuario_que_inicia_hd) ? $medical->usuario_que_inicia_hd : auth()->id()) == $medico->id) selected @endif>
                                    {{ $medico->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="text-primary"><i class="bi bi-person-check-fill"></i> MÉDICO FINALIZA HD</label>
                        <select name="usuario_que_finaliza_hd" id="usuario_que_finaliza_hd" class="form-select form-select-sm">
                            <option value="">-- Seleccionar --</option>
                            @foreach($medicos as $medico)
                                <option value="{{ $medico->id }}" 
                                    @if(old('usuario_que_finaliza_hd', is_numeric($medical->usuario_que_finaliza_hd) ? $medical->usuario_que_finaliza_hd : auth()->id()) == $medico->id) selected @endif>
                                    {{ $medico->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-success btn-sm w-100 fw-bold shadow-sm">
                            <i class="bi bi-check-circle-fill me-1"></i> ACTUALIZAR FICHA MÉDICA
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
    (function () {
        'use strict'
        var form = document.getElementById('medicalForm');

        form.addEventListener('submit', function (event) {
            var hFinal = document.getElementById('hora_final').value;
            
            // 1. VALIDACIÓN AL PONER HORA FINAL
            if (hFinal !== "") {
                // He añadido 'hora_inicial' a esta lista crítica
                var camposCierre = [
                    'hora_inicial', 'peso_seco', 'qb', 'qd', 'bicarbonato', 
                    'na_final', 'evaluacion_final', 'usuario_que_finaliza_hd', 'heparina'
                ];
                
                var faltanDatos = false;
                camposCierre.forEach(function(name) {
                    var input = document.getElementsByName(name)[0];
                    if (input && !input.value) {
                        input.setAttribute('required', 'required'); // Forzar atributo HTML5
                        input.classList.add('is-invalid'); // Forzar estilo visual
                        faltanDatos = true;
                    } else if (input) {
                        input.classList.remove('is-invalid');
                    }
                });

                if (faltanDatos || !form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    Swal.fire({
                        icon: 'warning',
                        title: 'Faltan datos de cierre',
                        text: 'Al finalizar la sesión, la HORA INICIAL y los parámetros técnicos son obligatorios.',
                        confirmButtonColor: '#198754'
                    });
                }
            } else {
                // 2. VALIDACIÓN NORMAL (SIN HORA FINAL)
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Campos Obligatorios',
                        text: 'Complete Peso Inicial, PA, UF, QB y Médico para guardar el avance.',
                        timer: 3000
                    });
                }
            }

            form.classList.add('was-validated');
        }, false);
    })()
</script>
@endsection