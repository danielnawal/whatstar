@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title'   => __('Edit Profile'),
'buttons' =>[
	[
		'name'=> __('Back to dashboard'),
		'url'=> url('user/dashboard'),
	]
]])
@endsection
@section('content')

{{-- ── Auth API Key ── --}}
<div class="row">
	<div class="col-lg-5 mt-5">
        <strong>{{ __('Authentication Key') }}</strong>
        <p>{{ __('Use auth key for authenticate your api request') }}</p>
    </div>
    <div class="col-lg-7 mt-5">
        <form class="ajaxform_instant_reload" action="{{ route('user.profile.update','auth-key') }}">
        	@csrf
        	@method('PUT')
        	<div class="card">
            <div class="card-body">
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Auth API Key') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="authket" required="" class="form-control" value="{{ Auth::user()->authkey }}" disabled="">
                    </div>
                </div>
                <div class="from-group row mt-3">
                    <div class="col-lg-12">
                       <button class="btn btn-neutral submit-button btn-sm float-left">{{ __('Regenerate Auth Key') }}</button>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>

{{-- ── SSO — Login sin contraseña ── --}}
<div class="row mt-2">
    <div class="col-lg-5 mt-5">
        <strong><i class="fas fa-broadcast-tower mr-1" style="color:#1a4b8c;"></i>{{ __('Acceso directo desde software GPS') }}</strong>
        <p class="text-muted small mt-1">
            {{ __('Genera una URL única para que tus clientes entren a WhatStar con un solo clic, sin escribir usuario ni contraseña.') }}
        </p>
        <div class="alert alert-info p-2 small">
            <i class="fas fa-info-circle mr-1"></i>
            {{ __('Copia la URL generada y configúrala en tu software GPS como enlace del ícono de WhatStar.') }}
        </div>
    </div>
    <div class="col-lg-7 mt-5">
        <div class="card">
            <div class="card-body">

                {{-- Token actual --}}
                <div class="from-group row mt-2">
                    <label class="col-lg-12 font-weight-bold">{{ __('SSO Token') }}</label>
                    <div class="col-lg-12">
                        <div class="input-group">
                            <input type="text" id="sso-token-field" class="form-control font-monospace"
                                   value="{{ Auth::user()->sso_token ?? '' }}"
                                   placeholder="{{ __('Sin token — genera uno abajo') }}"
                                   readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                        onclick="copyField('sso-token-field', this)"
                                        {{ !Auth::user()->sso_token ? 'disabled' : '' }}>
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- URL SSO --}}
                <div class="from-group row mt-3">
                    <label class="col-lg-12 font-weight-bold">{{ __('URL de acceso directo') }}</label>
                    <div class="col-lg-12">
                        <div class="input-group">
                            <input type="text" id="sso-url-field" class="form-control font-monospace small"
                                   value="{{ Auth::user()->sso_token ? url('/appogio/sso').'?token='.Auth::user()->sso_token : '' }}"
                                   placeholder="{{ __('Genera el token primero') }}"
                                   readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                        onclick="copyField('sso-url-field', this)"
                                        {{ !Auth::user()->sso_token ? 'disabled' : '' }}>
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">{{ __('Pega esta URL en tu software GPS → Configuración → WhatStar') }}</small>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="from-group row mt-4">
                    <div class="col-lg-12">
                        <button id="btn-gen-sso" class="btn btn-primary btn-sm">
                            <i class="fas fa-key mr-1"></i>
                            {{ Auth::user()->sso_token ? __('Regenerar Token SSO') : __('Generar Token SSO') }}
                        </button>
                        @if(Auth::user()->sso_token)
                        <small class="text-muted ml-3">
                            <i class="fas fa-exclamation-triangle text-warning mr-1"></i>
                            {{ __('Regenerar invalida la URL anterior.') }}
                        </small>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection
@push('bottomjs')
<script>
document.getElementById('btn-gen-sso').addEventListener('click', function () {
    const btn = this;
    const hasToken = {{ Auth::user()->sso_token ? 'true' : 'false' }};

    if (hasToken && !confirm('{{ __('¿Regenerar el token? La URL anterior dejará de funcionar.') }}')) {
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>{{ __('Generando...') }}';

    fetch('{{ route('appogio.sso.generate') }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
        },
    })
    .then(r => r.json())
    .then(data => {
        if (data.token) {
            document.getElementById('sso-token-field').value = data.token;
            document.getElementById('sso-url-field').value   = data.sso_url;

            // Habilitar botones de copiar
            document.querySelectorAll('[onclick^="copyField"]').forEach(b => b.disabled = false);

            btn.innerHTML = '<i class="fas fa-check mr-1"></i>{{ __('Token generado') }}';
            btn.classList.replace('btn-primary', 'btn-success');

            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-sync-alt mr-1"></i>{{ __('Regenerar Token SSO') }}';
                btn.classList.replace('btn-success', 'btn-primary');
                btn.disabled = false;
            }, 3000);
        } else {
            alert('Error al generar el token.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-key mr-1"></i>{{ __('Generar Token SSO') }}';
        }
    })
    .catch(() => {
        alert('Error de conexión.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-key mr-1"></i>{{ __('Generar Token SSO') }}';
    });
});

function copyField(id, btn) {
    const val = document.getElementById(id).value;
    if (!val) return;
    navigator.clipboard.writeText(val).then(() => {
        const prev = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('btn-success');
        setTimeout(() => {
            btn.innerHTML = prev;
            btn.classList.remove('btn-success');
        }, 2000);
    });
}
</script>
@endpush