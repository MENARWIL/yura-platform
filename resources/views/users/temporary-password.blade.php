@extends('layouts.app')

@section('title', 'Contraseña temporal')

@section('content')
<div class="row justify-content-center animate-fade-in">
    <div class="col-lg-7 col-md-9">
        <div class="card card-quechua">
            <div class="card-header card-header-quechua">
                <h3 class="card-title"><i class="fas fa-key mr-2" aria-hidden="true"></i> Contraseña temporal</h3>
            </div>
            <div class="card-body p-4">
                <p class="mb-1 text-muted small">Para</p>
                <p class="h5 font-weight-bold mb-1">{{ $user->name }}</p>
                <p class="text-muted mb-4">{{ $user->roleLabel() }} · {{ $user->email }} · WhatsApp +{{ $phone }}</p>

                @if($sent)
                    {{-- Enviada por la API desde el número del colegio: nadie más necesita ver la clave. --}}
                    <div class="alert alert-success mb-4">
                        <i class="fab fa-whatsapp mr-1" aria-hidden="true"></i>
                        <strong>Enviada por WhatsApp</strong> desde el número del colegio.
                        Vence el {{ $expires_at->format('d/m/Y') }} a las {{ $expires_at->format('H:i') }};
                        al entrar, el sistema le pedirá cambiarla. Sus sesiones abiertas se cerraron.
                    </div>
                    <p class="text-muted small mb-0">
                        Por seguridad la contraseña no se muestra aquí. Si no le llega, genera otra desde la lista de usuarios.
                    </p>
                @else
                    @if($send_error)
                        <div class="alert alert-danger small mb-4">
                            <i class="fas fa-exclamation-triangle mr-1" aria-hidden="true"></i>
                            No se pudo enviar automáticamente. {{ $send_error }} Envíala con el botón de abajo.
                        </div>
                    @endif

                    <p class="mb-1 text-muted small">Contraseña temporal</p>
                    <p class="display-4 font-weight-bold mb-1" style="letter-spacing: .08em; font-family: monospace;">{{ $password }}</p>
                    <p class="text-muted mb-4">
                        Vence el <strong>{{ $expires_at->format('d/m/Y') }}</strong> a las <strong>{{ $expires_at->format('H:i') }}</strong>.
                        Al entrar, el sistema le pedirá cambiarla. Sus sesiones abiertas se cerraron.
                    </p>

                    <a href="{{ $whatsapp_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-success btn-lg btn-block mb-3">
                        <i class="fab fa-whatsapp mr-2" aria-hidden="true"></i> Enviar por WhatsApp
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-block mb-3" id="copy-message" data-message="{{ $message }}">
                        <i class="fas fa-copy mr-2" aria-hidden="true"></i> <span>Copiar el mensaje</span>
                    </button>

                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-eye-slash mr-1" aria-hidden="true"></i>
                        Esta contraseña se muestra <strong>solo una vez</strong> y no queda guardada. Si cierras esta página sin enviarla,
                        genera otra desde la lista de usuarios.
                    </div>
                @endif
            </div>
            <div class="card-footer bg-white">
                <a href="{{ route('users.index') }}" class="btn btn-link px-0"><i class="fas fa-arrow-left mr-1" aria-hidden="true"></i> Volver a usuarios</a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function () {
        var button = document.getElementById('copy-message');
        if (!button) return;
        if (!navigator.clipboard) { button.hidden = true; return; }
        button.addEventListener('click', function () {
            navigator.clipboard.writeText(button.dataset.message).then(function () {
                button.querySelector('span').textContent = 'Mensaje copiado';
            });
        });
    })();
</script>
@endpush
