@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title' => __('Bandeja de Asesores'), 'buttons' => []])
@endsection
@section('content')

{{-- Totales --}}
<div class="row mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-stats shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col"><span class="h2 font-weight-bold mb-0">{{ $totals['total'] }}</span></div>
                    <div class="col-auto"><div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-headset mt-2"></i></div></div>
                </div>
                <h5 class="card-title text-muted mb-0 mt-2">{{ __('Total') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stats shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col"><span class="h2 font-weight-bold mb-0 text-warning">{{ $totals['pending'] }}</span></div>
                    <div class="col-auto"><div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-clock mt-2"></i></div></div>
                </div>
                <h5 class="card-title text-muted mb-0 mt-2">{{ __('Pendientes') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stats shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col"><span class="h2 font-weight-bold mb-0 text-info">{{ $totals['active'] }}</span></div>
                    <div class="col-auto"><div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-user-check mt-2"></i></div></div>
                </div>
                <h5 class="card-title text-muted mb-0 mt-2">{{ __('En atención') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stats shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col"><span class="h2 font-weight-bold mb-0 text-success">{{ $totals['closed'] }}</span></div>
                    <div class="col-auto"><div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-check-circle mt-2"></i></div></div>
                </div>
                <h5 class="card-title text-muted mb-0 mt-2">{{ __('Cerrados') }}</h5>
            </div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="{{ __('Buscar contacto, nombre, mensaje…') }}"
                class="form-control form-control-sm" style="max-width:240px;">
            <select name="status" class="form-control form-control-sm" style="max-width:140px;">
                <option value="">{{ __('Todos') }}</option>
                <option value="pending"  {{ request('status') == 'pending'  ? 'selected' : '' }}>Pendientes</option>
                <option value="active"   {{ request('status') == 'active'   ? 'selected' : '' }}>En atención</option>
                <option value="closed"   {{ request('status') == 'closed'   ? 'selected' : '' }}>Cerrados</option>
            </select>
            <button class="btn btn-sm btn-primary">{{ __('Filtrar') }}</button>
            @if(request('search') || request('status'))
            <a href="{{ url('/user/handoffs') }}" class="btn btn-sm btn-secondary">{{ __('Limpiar') }}</a>
            @endif
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>{{ __('Contacto') }}</th>
                        <th>{{ __('Último mensaje') }}</th>
                        <th>{{ __('Estado') }}</th>
                        <th>{{ __('NPS') }}</th>
                        <th>{{ __('Fecha') }}</th>
                        <th>{{ __('Acciones') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($handoffs as $h)
                    @php
                        $statusColors = ['pending'=>'warning','active'=>'info','closed'=>'success'];
                        $color = $statusColors[$h->status] ?? 'secondary';
                        $statusLabels = ['pending'=>'Pendiente','active'=>'En atención','closed'=>'Cerrado'];
                    @endphp
                    <tr>
                        <td class="text-muted small">{{ $h->id }}</td>
                        <td>
                            <code class="small">{{ $h->contact }}</code>
                            @if($h->contact_name)
                            <div class="text-muted small">{{ $h->contact_name }}</div>
                            @endif
                        </td>
                        <td class="small" style="max-width:220px;">
                            <span class="text-truncate d-block" style="max-width:220px;" title="{{ $h->last_message }}">
                                {{ $h->last_message ?: '—' }}
                            </span>
                        </td>
                        <td>
                            <select class="form-control form-control-sm handoff-status-select border-{{ $color }}"
                                data-id="{{ $h->id }}" style="min-width:120px;font-size:.78rem;">
                                @foreach(['pending'=>'Pendiente','active'=>'En atención','closed'=>'Cerrado'] as $val => $lbl)
                                <option value="{{ $val }}" {{ $h->status === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td>
                            @if($h->nps !== null)
                            <span class="badge badge-{{ $h->nps >= 9 ? 'success' : ($h->nps >= 7 ? 'warning' : 'danger') }}">
                                {{ $h->nps }}/10
                            </span>
                            @else
                            <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $h->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <a href="{{ route('user.handoffs.show', $h->id) }}" class="btn btn-xs btn-outline-primary">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">{{ __('No hay handoffs.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($handoffs->hasPages())
    <div class="card-footer">{{ $handoffs->links() }}</div>
    @endif
</div>

@push('js')
<script>
document.querySelectorAll('.handoff-status-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var id = this.dataset.id;
        fetch('/user/handoffs/' + id + '/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ status: this.value }),
        }).then(function(r) {
            if (!r.ok) alert('{{ __("Error al actualizar") }}');
            else location.reload();
        });
    });
});
</script>
@endpush
@endsection
