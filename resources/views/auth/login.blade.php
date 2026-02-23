@extends('layouts.app')

@section('content')
{{-- bg-light le da un contraste suave para que el card blanco resalte --}}
<div class="container-fluid bg-light">
    <div class="row justify-content-center align-items-center" style="min-height: 100vh;">
        <div class="col-11 col-sm-10 col-md-8 col-lg-7 col-xl-6">
            
            {{-- Card con Sombra Profunda --}}
            <div class="card border-0 shadow-lg overflow-hidden">
                <div class="row g-0">
                    
                    <div class="col-md-5 d-none d-md-flex flex-column align-items-center justify-content-center text-white p-4" 
                         style="background: linear-gradient(135deg, #1a2a6c, #2a4858) !important;">
                        <div class="text-center">
                            <i class="bi bi-shield-plus" style="font-size: 5rem;"></i>
                            <h2 class="fw-bold mt-3">HEMODIAL</h2>
                            <p class="px-3 opacity-75">Sistema de Referencias Médicas</p>
                        </div>
                    </div>

                    <div class="col-md-7 bg-white p-4 p-lg-5">
                        <div class="text-center d-md-none mb-4">
                             <i class="bi bi-heart-pulse-fill fs-1 text-primary"></i>
                             <h3 class="fw-bold">HEMODIAL</h3>
                        </div>

                        <div class="mb-4 text-center text-md-start">
                            <h4 class="fw-bold text-dark mb-1">Bienvenido</h4>
                            <p class="text-muted small">Por favor, identifícate para acceder</p>
                        </div>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted text-uppercase">Email o Usuario</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
                                    <input type="text" name="login" 
                                           class="form-control bg-light border-start-0 @error('email') is-invalid @enderror @error('username') is-invalid @enderror" 
                                           value="{{ old('login') }}" required autofocus placeholder="Nombre de usuario">
                                </div>
                                @error('email') <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span> @enderror
                                @error('username') <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span> @enderror
                            </div>

                            <div class="mb-4">
                                <div class="d-flex justify-content-between">
                                    <label class="form-label small fw-bold text-muted text-uppercase">Contraseña</label>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-key"></i></span>
                                    <input type="password" name="password" 
                                           class="form-control bg-light border-start-0 @error('password') is-invalid @enderror" 
                                           required placeholder="••••••••">
                                </div>
                                @error('password') <span class="invalid-feedback d-block"><strong>{{ $message }}</strong></span> @enderror
                            </div>

                            <div class="mb-4 d-flex justify-content-between align-items-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                    <label class="form-check-label small text-muted" for="remember">Recordarme</label>
                                </div>
                                @if (Route::has('password.request'))
                                    <a href="{{ route('password.request') }}" class="small text-decoration-none">¿Olvidaste tu clave?</a>
                                @endif
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold shadow-sm" 
                                    style="background-color: #1a2a6c; border: none;">
                                ACCEDER <i class="bi bi-arrow-right-short ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            
            {{-- Footer del Login --}}
            <div class="text-center mt-4">
                <p class="small text-muted text-uppercase" style="letter-spacing: 1px;">
                    &copy; {{ date('Y') }} Hospital Regional - Área de Referencias
                </p>
            </div>
        </div>
    </div>
</div>
@endsection