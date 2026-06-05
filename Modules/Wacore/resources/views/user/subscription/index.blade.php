@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',[
'title'   => __('Subscription Plan'),
'buttons' => [
	 [
      'name'=>'<i class="fi  fi-rs-calendar-clock"></i>&nbsp&nbsp'.__('Subscriptions History'),
      'url'=> url('/user/subscriptions/log'),
   ]
]

])
@endsection
@section('content')
@php
    $userExpiry   = Auth::user()->will_expire ? \Carbon\Carbon::parse(Auth::user()->will_expire) : null;
    $planExpired  = $userExpiry && $userExpiry->lte(now());
    $planExpiring = $userExpiry && !$planExpired && $userExpiry->lte(now()->addDays(5));
    $needsRenewal = $planExpired || $planExpiring;
@endphp
<div class="row">
@if(Session::has('saas_error'))
 <div class="col-sm-12">
   <div class="alert bg-gradient-danger text-white alert-dismissible fade show" role="alert">
     <span class="alert-icon"><i class="fi  fi-rs-info"></i></span>
     <span class="alert-text"><strong>{{ __('!Opps ') }}</strong> {{ Session::get('saas_error') }}</span>
     <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">×</span>
    </button>
  </div>
</div>
@endif

@if($planExpired)
<div class="col-sm-12 mb-3">
    <div class="alert alert-danger d-flex align-items-center" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        <strong>{{ __('Tu plan ha expirado') }}</strong>&nbsp;—&nbsp;
        {{ __('Selecciona un plan abajo para renovar tu suscripción.') }}
        @if($userExpiry) <span class="ml-2 text-muted small">({{ __('Venció:') }} {{ $userExpiry->format('d/m/Y') }})</span> @endif
    </div>
</div>
@elseif($planExpiring)
<div class="col-sm-12 mb-3">
    <div class="alert alert-warning d-flex align-items-center" role="alert">
        <i class="fas fa-clock mr-2"></i>
        <strong>{{ __('Tu plan vence pronto') }}</strong>&nbsp;—&nbsp;
        {{ __('Vence el') }} {{ $userExpiry->format('d/m/Y') }} ({{ $userExpiry->diffForHumans() }}).
        {{ __('Renueva ahora para no perder el acceso.') }}
    </div>
</div>
@endif

	@foreach($plans as $plan)
	@php
        $isCurrentPlan = Auth::user()->plan_id == $plan->id;
        $showRenew     = $isCurrentPlan && $needsRenewal;
        $showActive    = $isCurrentPlan && !$needsRenewal;
	@endphp
	<div class="col-sm-4 text-center">
		<div class="card {{ $showRenew ? 'border border-danger' : '' }}">
			<div class="card-body">
				<h2 class="pricing-green">{{ $plan->title }}</h2>
				<h1>{{ amount_format($plan->price) }}</h1>

				{{ __($plan->days == 30 ? 'Per month' : 'Per year') }}

				<hr>
				@foreach($plan->data ?? [] as $key => $data)
				<div class="mt-2 text-left">
					@if(planData($key,$data)['is_bool'] == true)
					@if(planData($key,$data)['value'] == true)
					<i class="{{ planData($key,$data)['value'] == true ? 'far text-success fa-check-circle' : 'fas text-danger fa-times-circle' }}"></i>
					@else
					<i class="fas text-danger fa-times-circle"></i>
					@endif

					@else
					<i class="far text-success fa-check-circle"></i>
					@endif
					{{ str_replace('_',' ',planData($key,$data)['title']) }}
				</div>
				@endforeach
				<hr>
				@if($showActive)
				<a class="btn btn-block btn-neutral" href="{{ route('user.subscription.show',$plan->id) }}">
					<i class="fa fa-check"></i> {{ __('Activated') }}
				</a>
				@elseif($showRenew)
				<a class="btn btn-block btn-danger" href="{{ route('user.subscription.show',$plan->id) }}">
					<i class="fas fa-sync-alt"></i> {{ __('Renovar Plan') }}
				</a>
				@else
				<a class="btn btn-block btn-neutral" href="{{ route('user.subscription.show',$plan->id) }}">
					<i class="fa fa-plus-circle"></i> {{ __('Subscribe') }}
				</a>
				@endif
			</div>
		</div>
	</div>
	@endforeach
</div>
@endsection