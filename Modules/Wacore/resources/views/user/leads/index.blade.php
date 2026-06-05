@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title' => __('Leads CRM'),
'buttons' => [
    [
        'name' => '<i class="fi fi-rs-cloud-download"></i>&nbsp;&nbsp;' . __('Export CSV'),
        'url'  => route('user.leads.export'),
    ]
]])
@endsection
@section('content')

{{-- Totales --}}
<div class="row mb-4">
    <div class="col-6 col-md-3">
        <div class="card card-stats shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col"><span class="h2 font-weight-bold mb-0">{{ $totals['total'] }}</span></div>
                    <div class="col-auto">
                        <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
                            <i class="fas fa-users mt-2"></i>
                        </div>
                    </div>
                </div>
                <h5 class="card-title text-muted mb-0 mt-2">{{ __('Total Leads') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stats shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col"><span class="h2 font-weight-bold mb-0">{{ $totals['new'] }}</span></div>
                    <div class="col-auto">
                        <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow">
                            <i class="fas fa-star mt-2"></i>
                        </div>
                    </div>
                </div>
                <h5 class="card-title text-muted mb-0 mt-2">{{ __('New') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stats shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col"><span class="h2 font-weight-bold mb-0">{{ $totals['qualified'] }}</span></div>
                    <div class="col-auto">
                        <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow">
                            <i class="fas fa-check-circle mt-2"></i>
                        </div>
                    </div>
                </div>
                <h5 class="card-title text-muted mb-0 mt-2">{{ __('Qualified') }}</h5>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card card-stats shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col"><span class="h2 font-weight-bold mb-0">{{ $totals['synced'] }}</span></div>
                    <div class="col-auto">
                        <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow">
                            <i class="fas fa-sync mt-2"></i>
                        </div>
                    </div>
                </div>
                <h5 class="card-title text-muted mb-0 mt-2">{{ __('Synced to CRM') }}</h5>
            </div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('Search name, phone, email…') }}"
                class="form-control form-control-sm" style="max-width:220px;">
            <select name="status" class="form-control form-control-sm" style="max-width:160px;">
                <option value="">{{ __('All statuses') }}</option>
                @foreach(['new','contacted','qualified','lost','won'] as $s)
                <option value="{{ $s }}" {{ request('status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <button class="btn btn-sm btn-primary">{{ __('Filter') }}</button>
            @if(request('search') || request('status'))
            <a href="{{ url('/user/leads') }}" class="btn btn-sm btn-secondary">{{ __('Clear') }}</a>
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
                        <th>{{ __('Contact') }}</th>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Email') }}</th>
                        <th>{{ __('Interest') }}</th>
                        <th>{{ __('Score') }}</th>
                        <th>{{ __('Channel') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    @php
                        $data    = json_decode($lead->full_data ?? '{}', true);
                        $channel = $data['channel'] ?? 'whatsapp';
                        $statusColors = [
                            'new'       => 'info',
                            'contacted' => 'primary',
                            'qualified' => 'success',
                            'lost'      => 'danger',
                            'won'       => 'warning',
                        ];
                        $color = $statusColors[$lead->status] ?? 'secondary';
                    @endphp
                    <tr>
                        <td class="text-muted small">{{ $lead->id }}</td>
                        <td><code class="small">{{ $lead->contact }}</code></td>
                        <td>{{ $lead->contact_name ?: '—' }}</td>
                        <td class="small">{{ $lead->contact_email ?: '—' }}</td>
                        <td class="small">{{ $lead->interest ?: '—' }}</td>
                        <td>
                            @if($lead->score !== null)
                            <span class="badge badge-{{ $lead->score >= 70 ? 'success' : ($lead->score >= 40 ? 'warning' : 'secondary') }}">
                                {{ $lead->score }}
                            </span>
                            @else —
                            @endif
                        </td>
                        <td>
                            @if($channel === 'telegram')
                            <i class="fab fa-telegram text-info"></i> Telegram
                            @else
                            <i class="fab fa-whatsapp text-success"></i> WhatsApp
                            @endif
                        </td>
                        <td>
                            <select class="form-control form-control-sm lead-status-select border-{{ $color }}"
                                data-id="{{ $lead->id }}" style="min-width:110px;font-size:.78rem;">
                                @foreach(['new','contacted','qualified','lost','won'] as $s)
                                <option value="{{ $s }}" {{ $lead->status === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="small text-muted">{{ $lead->created_at->format('d/m/Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">{{ __('No leads found.') }}</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($leads->hasPages())
    <div class="card-footer">{{ $leads->links() }}</div>
    @endif
</div>

@push('js')
<script>
document.querySelectorAll('.lead-status-select').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var id = this.dataset.id;
        var status = this.value;
        fetch('/user/leads/' + id + '/status', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({ status: status }),
        }).then(function(r) {
            if (!r.ok) alert('{{ __("Error updating status") }}');
        });
    });
});
</script>
@endpush
@endsection
