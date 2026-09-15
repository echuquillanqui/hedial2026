@extends('layouts.app')

@section('content')
<div class="container profile-page" style="max-width: 1050px;">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
        <div>
            <span class="text-uppercase text-primary fw-bold small">Cuenta personal</span>
            <h1 class="h3 fw-bold mb-1">Mi perfil</h1>
            <p class="text-muted mb-0">Mantén actualizados tus datos personales y credenciales de acceso.</p>
        </div>
        <div class="rounded-circle bg-primary bg-opacity-10 text-primary d-flex align-items-center justify-content-center" style="width: 64px; height: 64px;">
            <i class="bi bi-person-gear fs-2"></i>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold"><i class="bi bi-person-vcard text-primary me-2"></i>Datos personales</h2>
                    <p class="text-muted small mb-4">Estos datos se utilizan para identificarte dentro del sistema.</p>

                    @if(session('profile_success'))
                        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('profile_success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label fw-semibold">Nombres y apellidos</label>
                                <input id="name" name="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required autofocus>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="username" class="form-label fw-semibold">Usuario</label>
                                <input id="username" name="username" value="{{ old('username', $user->username) }}" class="form-control @error('username') is-invalid @enderror" required>
                                @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="dni" class="form-label fw-semibold">DNI</label>
                                <input id="dni" name="dni" value="{{ old('dni', $user->dni) }}" class="form-control @error('dni') is-invalid @enderror" inputmode="numeric" maxlength="8">
                                @error('dni')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label fw-semibold">Correo electrónico</label>
                                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="license_number" class="form-label fw-semibold">N.º de colegiatura</label>
                                <input id="license_number" name="license_number" value="{{ old('license_number', $user->license_number) }}" class="form-control @error('license_number') is-invalid @enderror">
                                @error('license_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="specialty_number" class="form-label fw-semibold">RNE / especialidad</label>
                                <input id="specialty_number" name="specialty_number" value="{{ old('specialty_number', $user->specialty_number) }}" class="form-control @error('specialty_number') is-invalid @enderror">
                                @error('specialty_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-12">
                                <label for="digital_seal" class="form-label fw-semibold">Sello digital</label>
                                <input type="file" id="digital_seal" name="digital_seal" class="form-control @error('digital_seal') is-invalid @enderror" accept="image/png,image/jpeg,image/webp">
                                <div class="form-text">Imagen PNG, JPG o WEBP de hasta 2 MB.</div>
                                @error('digital_seal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                @if($user->digital_seal_path)
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" value="1" name="remove_digital_seal" id="remove_digital_seal">
                                        <label class="form-check-label text-danger" for="remove_digital_seal">Eliminar mi sello actual</label>
                                    </div>
                                @endif
                            </div>
                        </div>
                        <button class="btn btn-primary mt-4 px-4"><i class="bi bi-check2 me-2"></i>Guardar cambios</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body p-4">
                    <h2 class="h5 fw-bold"><i class="bi bi-shield-lock text-primary me-2"></i>Cambiar contraseña</h2>
                    <p class="text-muted small mb-4">Usa al menos 8 caracteres y no compartas tu contraseña.</p>

                    @if(session('password_success'))
                        <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>{{ session('password_success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('profile.password.update') }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Contraseña actual</label>
                            <input type="password" id="current_password" name="current_password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password" required>
                            @error('current_password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Nueva contraseña</label>
                            <input type="password" id="password" name="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password" required>
                            @error('password', 'updatePassword')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirmar nueva contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                        </div>
                        <button class="btn btn-outline-primary w-100"><i class="bi bi-key me-2"></i>Actualizar contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
