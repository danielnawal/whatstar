@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> __('Dashboard'),'buttons'=>[
  [
    'name'=>'<i class="fa fa-plus"></i>&nbsp'.__('Create Device'),
    'url'=> route('user.device.create'),
  ],
  [
    'name'=>'<i class="fi fi-rs-paper-plane"></i>&nbsp'.__('Sent a message'),
    'url'=> url('/user/sent-text-message'),
  ],
]])
@endsection
@section('content')

@php
    $willExpire = Auth::user()->will_expire ? \Carbon\Carbon::parse(Auth::user()->will_expire) : null;
    $isExpired  = $willExpire && $willExpire->lte(now());
    $expiringSoon = $willExpire && !$isExpired && $willExpire->lte(now()->addDays(5));
    $renewUrl = Auth::user()->plan_id ? '/user/subscription/'.Auth::user()->plan_id : '/user/subscription';
@endphp

@if($isExpired)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-danger d-flex align-items-center justify-content-between mb-0" role="alert">
            <div>
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <strong>{{ __('Plan expirado') }}</strong> —
                {{ __('Tu suscripción venció el') }} {{ $willExpire->format('d/m/Y') }}.
                {{ __('Renueva ahora para seguir usando el sistema.') }}
            </div>
            <a href="{{ $renewUrl }}" class="btn btn-white btn-sm ml-3 text-danger font-weight-bold">
                <i class="fas fa-sync-alt mr-1"></i> {{ __('Renovar Plan') }}
            </a>
        </div>
    </div>
</div>
@elseif($expiringSoon)
<div class="row mb-3">
    <div class="col-12">
        <div class="alert alert-warning d-flex align-items-center justify-content-between mb-0" role="alert">
            <div>
                <i class="fas fa-clock mr-2"></i>
                <strong>{{ __('Plan por vencer') }}</strong> —
                {{ __('Tu suscripción vence el') }} {{ $willExpire->format('d/m/Y') }}
                ({{ $willExpire->diffForHumans() }}).
            </div>
            <a href="{{ $renewUrl }}" class="btn btn-warning btn-sm ml-3 font-weight-bold">
                <i class="fas fa-sync-alt mr-1"></i> {{ __('Renovar Plan') }}
            </a>
        </div>
    </div>
</div>
@endif

<div class="row">
  <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
      <!-- Card body -->
      <div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Devices') }}</h5>
            <span class="h2 font-weight-bold mb-0" id="total-device"><img src="{{ asset('uploads/loader.gif') }}"></span>
          </div>
          <div class="col-auto">
            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
             <i class="fas fa-server"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
      <!-- Card body -->
      <div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Messages') }}</h5>
            <span class="h2 font-weight-bold mb-0 mt-1" id="total-messages"><img src="{{ asset('uploads/loader.gif') }}"></span>
          </div>
          <div class="col-auto">
            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
              <i class="ni ni-spaceship"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
      <!-- Card body -->
      <div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Pending Schedules') }}</h5>
            <span class="h2 font-weight-bold mb-0" id="total-schedule"><img src="{{ asset('uploads/loader.gif') }}"></span>
          </div>
          <div class="col-auto">
            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
              <i class="ni ni-calendar-grid-58"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
   <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
      <!-- Card body -->
      <div class="card-body">
        <div class="row">
          <div class="col">
            <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total Contacts') }}</h5>
            <span class="h2 font-weight-bold mb-0" id="total-contacts"><img src="{{ asset('uploads/loader.gif') }}"></span>
          </div>
          <div class="col-auto">
            <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow">
              <i class="ni ni-collection"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>


<div class="row">
 @if(Session::has('success'))
 <div class="col-sm-12">
   <div class="alert bg-gradient-success text-white alert-dismissible fade show success-alert" role="alert">
     <span class="alert-icon"><img src="{{ asset('uploads/firework.png') }}" alt=""></span>
     <span class="alert-text"><strong>{{ __('Congratulations ') }}</strong> {{ Session::get('success') }}</span>
     <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">×</span>
    </button>
  </div>
</div>
@endif
 @if(Session::has('saas_error'))
 <div class="col-sm-12">
   <div class="alert bg-gradient-primary text-white alert-dismissible fade show" role="alert">
     <a href="{{ url(Auth::user()->plan_id == null ? '/user/subscription' : '/user/subscription/'.Auth::user()->plan_id) }}">
      <span class="alert-icon"><i class="fi  fi-rs-info text-white"></i></span>
    </a>
    <span class="alert-text">
      <strong>{{ __('!Opps ') }}</strong> 
      <a class="text-white" href="{{ url(Auth::user()->plan_id == null ? '/user/subscription' : '/user/subscription/'.Auth::user()->plan_id) }}">
        {{ Session::get('saas_error') }}
      </a>
    </span>
  </div>
</div>
@endif
  <div class="col-sm-6">
    <div class="card">
       <div class="card-header bg-transparent">
        <h4 class="card-header-title">{{ __('Messages Transaction') }}</h4>
        <div class="card-header-action">
          <select class="form-control" id="period" >
            <option value="7">{{ __('Last 7 Days') }}</option>
            <option value="1">{{ __('Today') }}</option>
            <option value="30">{{ __('Last 30 Days') }}</option>
          </select>
        </div>
      </div>
      <div class="card-body">
        <!-- Chart -->
        <div class="chart">
          <!-- Chart wrapper -->
          <canvas id="chart-sales" class="chart-canvas"></canvas>
        </div>
      </div>
    </div>
  </div>
  <div class="col-sm-6">
    <!--* Card header *-->
    <!--* Card body *-->
    <!--* Card init *-->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <!-- Surtitle -->
        <h4 class="h3 mb-0 card-header-title">{{ __('Automatic Replies') }}</h4>
        <div class="card-header-action">
          <select class="form-control" id="automaticReply" >
            <option value="7">{{ __('Last 7 Days') }}</option>
            <option value="1">{{ __('Today') }}</option>
            <option value="30">{{ __('Last 30 Days') }}</option>
          </select>
        </div>
      </div>
      <!-- Card body -->
      <div class="card-body">
        <div class="chart">
          <!-- Chart wrapper -->
          <canvas id="chart-bars" class="chart-canvas"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6">
    <!--* Card header *-->
    <!--* Card body *-->
    <!--* Card init *-->
    <div class="card">
      <!-- Card header -->
      <div class="card-header">
        <h4 class="h3 mb-0 card-header-title">{{ __('Messages') }}</h4>
        <div class="card-header-action">
          <select class="form-control" id="messagesTypes" >
            <option value="7">{{ __('Last 7 Days') }}</option>
            <option value="1">{{ __('Today') }}</option>
            <option value="30">{{ __('Last 30 Days') }}</option>
          </select>
        </div>
      </div>
      <!-- Card body -->
      <div class="card-body">
        <div class="chart">
          <!-- Chart wrapper -->
          <canvas id="chart-doughnut" class="chart-canvas"></canvas>
        </div>
      </div>
    </div>
  </div>

  <div class="col-sm-6">
    <div class="card">
      <!-- Card header -->
      <div class="card-header bg-transparent">
        <!-- Title -->
        <h4 class="card-header-title">{{ __('Devices Statistics') }}</h4>
      </div>
      <!-- Card body -->
      <div class="card-body">
        <!-- List group -->
        <ul class="list-group list-group-flush list my--3" id="device-list">

        </ul>
      </div>
    </div>
  </div>
 </div>

{{-- ============================================================ --}}
{{-- CRM mini: leads y handoffs pendientes (atención inmediata)    --}}
{{-- ============================================================ --}}
<div class="row mt-2">
  <div class="col-12">
    <h4 class="mb-3"><i class="fas fa-user-clock mr-2 text-danger"></i>{{ __('Atención al cliente') }}</h4>
  </div>
</div>
<div class="row">
  <div class="col-xl-4 col-md-6">
    <div class="card card-stats border-left-danger">
      <div class="card-body">
        <div class="row"><div class="col">
          <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Leads nuevos') }}</h5>
          <span class="h2 font-weight-bold mb-0 text-danger" id="kpi-leads-new">—</span>
          <small class="d-block mt-1 text-muted">{{ __('Pendientes de atender') }}</small>
        </div><div class="col-auto">
          <div class="icon icon-shape bg-gradient-danger text-white rounded-circle shadow"><i class="fas fa-user-plus"></i></div>
        </div></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="card card-stats">
      <div class="card-body">
        <div class="row"><div class="col">
          <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Handoffs activos') }}</h5>
          <span class="h2 font-weight-bold mb-0" id="kpi-handoffs-pending">—</span>
          <small class="d-block mt-1 text-muted">{{ __('Bot pausado, esperando agente') }}</small>
        </div><div class="col-auto">
          <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-headset"></i></div>
        </div></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4 col-md-6">
    <div class="card card-stats">
      <div class="card-body">
        <div class="row"><div class="col">
          <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Leads 30 días') }}</h5>
          <span class="h2 font-weight-bold mb-0" id="kpi-leads-30">—</span>
          <small class="d-block mt-1 text-muted">{{ __('Captados en último mes') }}</small>
        </div><div class="col-auto">
          <div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-funnel-dollar"></i></div>
        </div></div>
      </div>
    </div>
  </div>
</div>
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header bg-transparent">
        <h4 class="card-header-title"><i class="fas fa-list-ul mr-2"></i>{{ __('Últimos leads') }}</h4>
        <small class="text-muted">{{ __('Contactos recientes que pidieron asesor o cotización') }}</small>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0" id="recent-leads-table">
          <thead class="thead-light">
            <tr>
              <th>{{ __('Fecha') }}</th>
              <th>{{ __('Contacto') }}</th>
              <th>{{ __('Nombre') }}</th>
              <th>{{ __('Interés') }}</th>
              <th>{{ __('Estado') }}</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

{{-- ============================================================ --}}
{{-- Premium analytics: 4 KPIs + 3 charts. Solo visible si hay data. --}}
{{-- ============================================================ --}}
<div class="row mt-2">
  <div class="col-12">
    <h4 class="mb-3"><i class="fas fa-chart-line mr-2 text-primary"></i>{{ __('Análisis avanzado') }}</h4>
  </div>
</div>

<div class="row">
  <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
      <div class="card-body">
        <div class="row"><div class="col">
          <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Mes actual') }}</h5>
          <span class="h2 font-weight-bold mb-0" id="kpi-this-month">—</span>
          <small class="d-block mt-1" id="kpi-month-delta">—</small>
        </div><div class="col-auto">
          <div class="icon icon-shape bg-gradient-info text-white rounded-circle shadow"><i class="fas fa-calendar-alt"></i></div>
        </div></div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
      <div class="card-body">
        <div class="row"><div class="col">
          <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Mes anterior') }}</h5>
          <span class="h2 font-weight-bold mb-0" id="kpi-prev-month">—</span>
          <small class="d-block mt-1 text-muted">{{ __('mensajes totales') }}</small>
        </div><div class="col-auto">
          <div class="icon icon-shape bg-gradient-default text-white rounded-circle shadow"><i class="fas fa-history"></i></div>
        </div></div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
      <div class="card-body">
        <div class="row"><div class="col">
          <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Tasa bot') }}</h5>
          <span class="h2 font-weight-bold mb-0" id="kpi-bot-rate">—</span>
          <small class="d-block mt-1 text-muted">{{ __('respondidos por chatbot (30d)') }}</small>
        </div><div class="col-auto">
          <div class="icon icon-shape bg-gradient-success text-white rounded-circle shadow"><i class="fas fa-robot"></i></div>
        </div></div>
      </div>
    </div>
  </div>
  <div class="col-xl-3 col-md-6">
    <div class="card card-stats">
      <div class="card-body">
        <div class="row"><div class="col">
          <h5 class="card-title text-uppercase text-muted mb-0">{{ __('Total 30 días') }}</h5>
          <span class="h2 font-weight-bold mb-0" id="kpi-total-30">—</span>
          <small class="d-block mt-1 text-muted">{{ __('mensajes enviados') }}</small>
        </div><div class="col-auto">
          <div class="icon icon-shape bg-gradient-warning text-white rounded-circle shadow"><i class="fas fa-paper-plane"></i></div>
        </div></div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-sm-6">
    <div class="card">
      <div class="card-header bg-transparent">
        <h4 class="card-header-title">{{ __('Distribución horaria (30d)') }}</h4>
        <small class="text-muted">{{ __('A qué horas tu audiencia responde más') }}</small>
      </div>
      <div class="card-body">
        <div class="chart"><canvas id="chart-byhour" class="chart-canvas"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-sm-6">
    <div class="card">
      <div class="card-header bg-transparent">
        <h4 class="card-header-title">{{ __('Top respuestas del chatbot (30d)') }}</h4>
        <small class="text-muted">{{ __('Los flujos más activados por tus clientes') }}</small>
      </div>
      <div class="card-body">
        <div class="chart"><canvas id="chart-toptemplates" class="chart-canvas"></canvas></div>
      </div>
    </div>
  </div>
  <div class="col-sm-12">
    <div class="card">
      <div class="card-header bg-transparent">
        <h4 class="card-header-title">{{ __('Nuevos contactos (30d)') }}</h4>
        <small class="text-muted">{{ __('Captación diaria de leads') }}</small>
      </div>
      <div class="card-body">
        <div class="chart"><canvas id="chart-newcontacts" class="chart-canvas"></canvas></div>
      </div>
    </div>
  </div>
</div>
<input type="hidden" id="static-data" value="{{ route('user.dashboard.static') }}"> 
<input type="hidden" id="base_url" value="{{ url('/') }}"> 

@endsection
@push('js')
<script src="{{ asset('assets/vendor/chart.js/dist/chart.min.js') }}"></script>
<script src="{{ asset('assets/plugins/canvas-confetti/confetti.browser.min.js') }}"></script>
@endpush
@push('bottomjs')
<script src="{{ asset('assets/js/pages/user/dashboard.js') }}"></script>
@endpush
