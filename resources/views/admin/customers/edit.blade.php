@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title'=> __('Edit User'),
'buttons'=>[
    [
        'name'=>__('Back'),
        'url'=>route('admin.customer.index'),
    ]
]

])
@endsection
@section('content')
<div class="row ">
	<div class="col-lg-5 mt-5">
        <strong>{{ __('Edit User') }}</strong>
        <p>{{ __('Edit user profile information') }}</p>
    </div>
    <div class="col-lg-7 mt-5">
        <form class="ajaxform_instant_reload" action="{{ route('admin.customer.update',$customer->id) }}">
        	@csrf
        	@method('PUT')
        	<div class="card">
            <div class="card-body">
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Name') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="name" required="" class="form-control" value="{{ $customer->name }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Email') }}</label>
                    <div class="col-lg-12">
                        <input type="email" name="email" required="" class="form-control" value="{{ $customer->email }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Phone') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="phone"  class="form-control" value="{{ $customer->phone }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Address') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="address"  class="form-control" value="{{ $customer->address }}">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('New Password') }}</label>
                    <div class="col-lg-12">
                        <input type="text" name="password"  class="form-control" value="">
                    </div>
                </div>
                <div class="from-group row mt-2">
                    <label class="col-lg-12">{{ __('Status') }}</label>
                    <div class="col-lg-12">
                       <select class="form-control" name="status">
                       	 <option value="1" {{ $customer->status == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                       	 <option value="0" {{ $customer->status == 0 ? 'selected' : '' }}>{{ __('Deactive') }}</option>
                       </select>
                    </div>
                </div>

                 <div class="from-group row mt-3">
                    <div class="col-lg-12">
                       <button class="btn btn-neutral submit-button btn-sm float-left"> {{ __('Update') }}</button>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>
</div>

{{-- ── SSO software GPS ── --}}
<div class="row mt-2">
    <div class="col-lg-5 mt-5">
        <strong><i class="fas fa-broadcast-tower mr-1" style="color:#1a4b8c;"></i>{{ __('SSO software GPS') }}</strong>
        <p class="text-muted small mt-1">
            {{ __('Genera la URL de acceso directo para que este cliente entre a WhatStar desde su software GPS sin contraseña.') }}
        </p>
    </div>
    <div class="col-lg-7 mt-5">
        <div class="card">
            <div class="card-body">
                <div class="from-group row mt-2">
                    <label class="col-lg-12 font-weight-bold">{{ __('SSO Token') }}</label>
                    <div class="col-lg-12">
                        <div class="input-group">
                            <input type="text" id="admin-sso-token" class="form-control" style="font-family:monospace;"
                                   value="{{ $customer->sso_token ?? '' }}"
                                   placeholder="{{ __('Sin token generado') }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                        onclick="adminCopy('admin-sso-token',this)"
                                        {{ !$customer->sso_token ? 'disabled' : '' }}>
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="from-group row mt-3">
                    <label class="col-lg-12 font-weight-bold">{{ __('URL de acceso directo') }}</label>
                    <div class="col-lg-12">
                        <div class="input-group">
                            <input type="text" id="admin-sso-url" class="form-control" style="font-family:monospace; font-size:.8rem;"
                                   value="{{ $customer->sso_token ? url('/appogio/sso').'?token='.$customer->sso_token : '' }}"
                                   placeholder="{{ __('Genera el token primero') }}" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary btn-sm" type="button"
                                        onclick="adminCopy('admin-sso-url',this)"
                                        {{ !$customer->sso_token ? 'disabled' : '' }}>
                                    <i class="fas fa-copy"></i>
                                </button>
                            </div>
                        </div>
                        <small class="text-muted">{{ __('Pega esta URL en el software GPS → Launcher → WhatStar para el cliente') }} <strong>{{ $customer->name }}</strong></small>
                    </div>
                </div>
                <div class="from-group row mt-4">
                    <div class="col-lg-12">
                        <button id="btn-admin-sso" class="btn btn-primary btn-sm">
                            <i class="fas fa-key mr-1"></i>
                            {{ $customer->sso_token ? __('Regenerar Token SSO') : __('Generar Token SSO') }}
                        </button>
                        @if($customer->sso_token)
                        <small class="text-warning ml-2">
                            <i class="fas fa-exclamation-triangle mr-1"></i>{{ __('Regenerar invalida la URL anterior.') }}
                        </small>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('bottomjs')
<script>
document.getElementById('btn-admin-sso').addEventListener('click', function () {
    const btn  = this;
    const has  = {{ $customer->sso_token ? 'true' : 'false' }};
    if (has && !confirm('¿Regenerar el token SSO del cliente {{ addslashes($customer->name) }}? La URL anterior dejará de funcionar.')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Generando...';

    fetch('{{ route('admin.appogio.sso.generate', $customer->id) }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept':       'application/json',
        },
    })
    .then(r => r.json())
    .then(d => {
        if (d.token) {
            document.getElementById('admin-sso-token').value = d.token;
            document.getElementById('admin-sso-url').value   = d.sso_url;
            document.querySelectorAll('[onclick^="adminCopy"]').forEach(b => b.disabled = false);
            btn.innerHTML = '<i class="fas fa-check mr-1"></i>Token generado';
            btn.classList.replace('btn-primary', 'btn-success');
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-sync-alt mr-1"></i>Regenerar Token SSO';
                btn.classList.replace('btn-success', 'btn-primary');
                btn.disabled = false;
            }, 3000);
        } else {
            alert('Error al generar el token.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-key mr-1"></i>Generar Token SSO';
        }
    })
    .catch(() => {
        alert('Error de conexión.');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-key mr-1"></i>Generar Token SSO';
    });
});

function adminCopy(id, btn) {
    const v = document.getElementById(id).value;
    if (!v) return;
    navigator.clipboard.writeText(v).then(() => {
        const p = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('btn-success');
        setTimeout(() => { btn.innerHTML = p; btn.classList.remove('btn-success'); }, 2000);
    });
}
</script>
@endpush
@endsection