@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['buttons'=>[
	[
		'name'=>'Back',
		'url'=>route('user.apps.index'),
	]
]])
@endsection
@section('content')
<div>
    <div class="content-wrapper">
        <div class="row">
            <div class="col-lg-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0 font-weight-bolder">{{__('Create New Message')}}</h3>
                    </div>
                    <div class="card-body">
                        <div class="">
             
                            @php
                            $url=route('api.create.message');
                            @endphp
                            <ul class="nav nav-pills nav-fill" id="myTab" role="tablist">
                              <li class="nav-item" role="presentation">
                                <a class="nav-link active" id="home-tab" data-toggle="tab" data-target="#curl" type="button" role="tab" aria-controls="home" aria-selected="true">cUrl</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#php" type="button" role="tab" aria-controls="profile" aria-selected="false">Php</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="profile-tab" data-toggle="tab" data-target="#nodejs" type="button" role="tab" aria-controls="profile" aria-selected="false">NodeJs - Request</a>
                            </li>
                            <li class="nav-item" role="presentation">
                                <a class="nav-link" id="contact-tab" data-toggle="tab" data-target="#python" type="button" role="tab" aria-controls="contact" aria-selected="false">Python</a>
                            </li>
                        </ul>

<div class="tab-content mt-4 mb-4" id="myTabContent">
<div class="tab-pane fade show active" id="curl" role="tabpanel" aria-labelledby="home-tab">
    <div class="language-markup">
        
<pre class="language-markup" tabindex="0">
<h3>{{ __('Text Message Only') }}</h3>     
curl --location --request POST '{{ $url }}' \
--form 'appkey="{{ $key }}"' \
--form 'authkey="{{ Auth::user()->authkey }}"' \
--form 'to="RECEIVER_NUMBER"' \
--form 'message="Example message"' \
</pre>
</div>
<hr>
<div class="language-markup">
        
<pre class="language-markup" tabindex="0">
<h3>{{ __('Text Message with file') }}</h3>     
curl --location --request POST '{{ $url }}' \
--form 'appkey="{{ $key }}"' \
--form 'authkey="{{ Auth::user()->authkey }}"' \
--form 'to="RECEIVER_NUMBER"' \
--form 'message="Example message"' \
--form 'file="https://www.africau.edu/images/default/sample.pdf"'
</pre>
</div>
<hr>
<div class="language-markup">
    <pre class="language-markup" tabindex="2">
<code class="language-markup">
<h3>{{ __('Template Only') }}</h3>    
curl --location --request POST '{{ $url }}' \
--form 'appkey="{{ $key }}"' \
--form 'authkey="{{ Auth::user()->authkey }}"' \
--form 'to="RECEIVER_NUMBER"' \
--form 'template_id="TEMPLATE_ID"' \
--form 'variables[{variableKey1}]="jhone"' \
--form 'variables[{variableKey2}]="replaceable value"'

</code>
</pre>
</div>
</div>

  <div class="tab-pane fade" id="php" role="tabpanel" aria-labelledby="profile-tab">
      <pre class="language-markup" tabindex="1">
<h3>{{ __('Text Message Only') }}</h3>   
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => '{{ $url }}',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array(
  'appkey' => '{{ $key }}',
  'authkey' => '{{ Auth::user()->authkey }}',
  'to' => 'RECEIVER_NUMBER',
  'message' => 'Example message',
  'sandbox' => 'false'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
</pre>
<hr>
<pre class="language-markup" tabindex="1">
<h3>{{ __('Text Message with file') }}</h3>   
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => '{{ $url }}',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array(
  'appkey' => '{{ $key }}',
  'authkey' => '{{ Auth::user()->authkey }}',
  'to' => 'RECEIVER_NUMBER',
  'message' => 'Example message',
  'file' => 'https://www.africau.edu/images/default/sample.pdf',
  'sandbox' => 'false'
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
</pre>
<hr>
 <pre class="language-markup" tabindex="1">
<h3>{{ __('Template Message Only') }}</h3>   
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => '{{ $url }}',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS => array(
  'appkey' => '{{ $key }}',
  'authkey' => '{{ Auth::user()->authkey }}',
  'to' => 'RECEIVER_NUMBER',
  'template_id' => 'TEMPLATE_ID',
  'variables' => array(
    '{variableKey1}' => 'Jhone', 
    '{variableKey2}' => 'replaceable value'
   )
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
</pre>



  </div>
  <div class="tab-pane fade" id="nodejs" role="tabpanel" aria-labelledby="contact-tab">
<pre class="language-markup" tabindex="2">
<code class="language-markup">
<h3>{{ __('Text Message Only') }}</h3>    
var request = require('request');
var options = {
  'method': 'POST',
  'url': '{{ $url }}',
  'headers': {
  },
  formData: {
    'appkey': '{{ $key }}',
    'authkey': '{{ Auth::user()->authkey }}',
    'to': 'RECEIVER_NUMBER',
    'message': 'Example message'
  }
};
request(options, function (error, response) {
  if (error) throw new Error(error);
  console.log(response.body);
});

</code>
</pre>
<hr>
<pre class="language-markup" tabindex="2">
<code class="language-markup">
<h3>{{ __('Text Message With File') }}</h3>    
var request = require('request');
var options = {
  'method': 'POST',
  'url': '{{ $url }}',
  'headers': {
  },
  formData: {
    'appkey': '{{ $key }}',
    'authkey': '{{ Auth::user()->authkey }}',
    'to': 'RECEIVER_NUMBER',
    'message': 'Example message',
    'file': 'https://www.africau.edu/images/default/sample.pdf'
  }
};
request(options, function (error, response) {
  if (error) throw new Error(error);
  console.log(response.body);
});

</code>
</pre>
<hr>
<pre class="language-markup" tabindex="2">
<code class="language-markup">
<h3>{{ __('Template Only') }}</h3>    
var request = require('request');
var options = {
  'method': 'POST',
  'url': '{{ $url }}',
  'headers': {
  },
  formData: {
    'appkey': '{{ $key }}',
    'authkey': '{{ Auth::user()->authkey }}',
    'to': 'RECEIVER_NUMBER',
    'template_id': 'SELECTED_TEMPLATE_ID',
    'variables': {
        '{variableKey1}' : 'jhone',
        '{variableKey2}' : 'replaceable value'
    }
  }
};
request(options, function (error, response) {
  if (error) throw new Error(error);
  console.log(response.body);
});

</code>
</pre>

  </div>
  <div class="tab-pane fade" id="python" role="tabpanel" aria-labelledby="contact-tab">
       <pre class="language-markup" tabindex="3">
<code class="language-markup">
import requests

url = "{{ $url }}"

payload={
'appkey': '{{ $key }}',
'authkey': '{{ Auth::user()->authkey }}',
'to': 'RECEIVER_NUMBER',
'message': 'Example message',

}
files=[

]
headers = {}

response = requests.request("POST", url, headers=headers, data=payload, files=files)

print(response.text)



</code></pre>
  </div>


</div>
                        </div>

                        <h3 class="font-weight-bolder">{{__('Successful Json Callback')}}</h3>
                        <pre>
<code>
 {
    "message_status": "Success",
    "data": {
        "from": "SENDER_NUMBER",
        "to": "RECEIVER_NUMBER",
        "status_code": 200
    }
}
</code>
                    </pre>
                    </div>
                </div>

                

                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0 font-weight-bolder">{{__('Parameters')}}</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-flush">
                            <thead class="">
                            <tr>
                                <th>{{__('S/N')}}</th>
                                <th>{{__('Value')}}</th>
                                <th>{{__('Type')}}</th>
                                <th>{{__('Required')}}</th>
                                <th>{{__('Description')}}</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr>
                                <td>{{__('1.')}}</td>
                                <td>{{__('appkey')}}</td>
                                <td>{{__('string')}}</td>
                                <td>{{__('Yes')}}</td>
                                <td>{{ __("Used to authorize a transaction for the app") }}</td>
                            </tr>
                            <tr>
                                <td>{{__('2.')}}</td>
                                <td>{{__('authkey')}}</td>
                                <td>{{__('string')}}</td>
                                <td>{{__('Yes')}}</td>
                                <td>{{ __("Used to authorize a transaction for the is valid user") }}</td>
                            </tr>
                            <tr>
                                <td>{{__('3.')}}</td>
                                <td>{{__('to')}}</td>
                                <td>{{__('number')}}</td>
                                <td>{{__('Yes')}}</td>
                                <td>{{ __("Who will receive the message the Whatsapp number should be full number with country code") }}</td>
                            </tr>
                            <tr>
                                <td>{{__('4.')}}</td>
                                <td>{{__('template_id')}}</td>
                                <td>{{__('string')}}</td>
                                <td>{{__('No')}}</td>
                                <td>{{ __("Used to authorize a transaction for the template") }}</td>
                            </tr>
                           	<tr>
                                <td>{{__('5.')}}</td>
                                <td>{{__('message')}}</td>
                                <td>{{__('string')}}</td>
                                <td>{{__('No')}}</td>
                                <td>{{ __("The transactional message max:1000 words") }}</td>
                            </tr>
                            <tr>
                                <td>{{__('6.')}}</td>
                                <td>{{__('file')}}</td>
                                <td>{{__('string')}}</td>
                                <td>{{__('No')}}</td>
                                <td>{{ __("file extension type should be in jpg,jpeg,png,webp,pdf,docx,xlsx,csv,txt") }}</td>
                            </tr>
                            <tr>
                                <td>{{__('7.')}}</td>
                                <td>{{__('variables')}}</td>
                                <td>{{__('Array')}}</td>
                                <td>{{__('No')}}</td>
                                <td>{{ __("the first value you list replaces the {1} variable in the template message and the second value you list replaces the {2} variable") }}</td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Sección GPS ── --}}
        <div class="row mt-3">
            <div class="col-lg-12">
                <div class="card border-left-primary" style="border-left: 4px solid #1a4b8c;">
                    <div class="card-header d-flex align-items-center">
                        <i class="fas fa-broadcast-tower fa-lg mr-3" style="color:#1a4b8c;"></i>
                        <div>
                            <h3 class="mb-0 font-weight-bolder">{{ __('Integración con software GPS') }}</h3>
                            <small class="text-muted">{{ __('Recibe alertas de tu software GPS y envíalas por WhatsApp') }}</small>
                        </div>
                        <a href="{{ route('appogio.connect', $uuid) }}" target="_blank"
                           class="btn btn-primary btn-sm ml-auto">
                            <i class="fas fa-plug mr-1"></i>{{ __('Conectar software GPS') }}
                        </a>
                    </div>
                    <div class="card-body">

                        {{-- ── SMS Gateway para software GPS ── --}}
                        <div class="card border-0 mb-4" style="background:#f0f7ff; border-left:4px solid #0d6efd !important; border-left-style:solid !important;">
                            <div class="card-body py-3">
                                <h6 class="font-weight-bold mb-3" style="color:#0d6efd;">
                                    <i class="fas fa-cog mr-2"></i>{{ __('Configuración SMS Gateway — Copiar en el software GPS') }}
                                </h6>

                                @php
                                    $smsGwUrl = url('/api/create-message')
                                        . '?appkey=' . $key
                                        . '&authkey=' . Auth::user()->authkey
                                        . '&to=%NUMBER%&message=%MESSAGE%';
                                @endphp

                                <div class="row mb-2">
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <span class="text-muted small d-block">{{ __('Habilitar gateway SMS') }}</span>
                                        <span class="badge badge-success px-3 py-2" style="font-size:.85rem;">✔ SÍ</span>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <span class="text-muted small d-block">{{ __('Solicitud método') }}</span>
                                        <span class="badge badge-primary px-3 py-2" style="font-size:.85rem;">POST</span>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <span class="text-muted small d-block">{{ __('Codificación') }}</span>
                                        <span class="badge badge-secondary px-3 py-2" style="font-size:.85rem;">✖ NO</span>
                                    </div>
                                    <div class="col-md-3 col-sm-6 mb-2">
                                        <span class="text-muted small d-block">{{ __('Autenticación') }}</span>
                                        <span class="badge badge-secondary px-3 py-2" style="font-size:.85rem;">✖ NO</span>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <span class="text-muted small d-block mb-1">{{ __('Cabeceras de puerta de enlace de SMS') }} <em class="text-muted">({{ __('y también') }}: SMS gateway URL)</em></span>
                                    <div class="input-group">
                                        <input type="text" id="sms-gw-url" class="form-control form-control-sm" style="font-family:monospace;font-size:.78rem;" readonly value="{{ $smsGwUrl }}">
                                        <div class="input-group-append">
                                            <button class="btn btn-sm btn-primary" onclick="copySmsGwUrl()" type="button">
                                                <i class="fas fa-copy mr-1"></i>{{ __('Copiar') }}
                                            </button>
                                        </div>
                                    </div>
                                    <small class="text-muted">{{ __('Pega esta misma URL en los dos campos: Cabeceras y SMS gateway URL.') }}</small>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mb-3" role="alert">
                            <i class="fas fa-info-circle mr-2"></i>
                            {{ __('Para conectar WhatStar con tu software GPS: abre la página de configuración, escanea el QR con WhatsApp y genera el token. Las claves se sincronizan automáticamente.') }}
                        </div>

                        <h5 class="font-weight-bold mb-2">{{ __('URL Webhook de Alertas') }}</h5>
                        <p class="text-muted small mb-1">{{ __('Copia esta URL en la configuración de alertas de tu software GPS. Reemplaza los parámetros entre llaves {}.') }}</p>
                        <pre class="language-markup p-3 bg-light rounded" style="font-size:.78rem; word-break:break-all; white-space:pre-wrap;">{{ url('/appogio/alert') }}?appkey={{ $key }}&amp;outkey={OUT_KEY}&amp;to={TELEFONO}&amp;unit={UNIDAD}&amp;event={EVENTO}&amp;speed={VELOCIDAD}&amp;lat={LAT}&amp;lng={LNG}&amp;address={DIRECCION}</pre>

                        <h5 class="font-weight-bold mt-4 mb-2">{{ __('Parámetros del Webhook') }}</h5>
                        <div class="table-responsive">
                            <table class="table table-sm table-flush">
                                <thead>
                                    <tr>
                                        <th>{{ __('Parámetro') }}</th>
                                        <th>{{ __('Requerido') }}</th>
                                        <th>{{ __('Descripción') }}</th>
                                        <th>{{ __('Ejemplo') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td><code>appkey</code></td><td><span class="badge badge-danger">Sí</span></td><td>{{ __('Clave de la App') }}</td><td><code>{{ $key }}</code></td></tr>
                                    <tr><td><code>outkey</code></td><td><span class="badge badge-danger">Sí</span></td><td>{{ __('Token secreto (se genera en Conectar software GPS)') }}</td><td><code>abc123...</code></td></tr>
                                    <tr><td><code>to</code></td><td><span class="badge badge-danger">Sí</span></td><td>{{ __('Número(s) WhatsApp destino (con código de país, separados por coma)') }}</td><td><code>573001234567</code></td></tr>
                                    <tr><td><code>unit</code></td><td><span class="badge badge-warning">No</span></td><td>{{ __('Nombre de la unidad GPS') }}</td><td><code>Camión 01</code></td></tr>
                                    <tr><td><code>event</code></td><td><span class="badge badge-warning">No</span></td><td>{{ __('Tipo de evento: speeding, geofence_in, geofence_out, ignition_on, ignition_off, sos, stop, movement') }}</td><td><code>speeding</code></td></tr>
                                    <tr><td><code>speed</code></td><td><span class="badge badge-warning">No</span></td><td>{{ __('Velocidad en km/h') }}</td><td><code>120</code></td></tr>
                                    <tr><td><code>lat</code></td><td><span class="badge badge-warning">No</span></td><td>{{ __('Latitud') }}</td><td><code>4.6097</code></td></tr>
                                    <tr><td><code>lng</code></td><td><span class="badge badge-warning">No</span></td><td>{{ __('Longitud') }}</td><td><code>-74.0817</code></td></tr>
                                    <tr><td><code>address</code></td><td><span class="badge badge-warning">No</span></td><td>{{ __('Dirección legible') }}</td><td><code>Calle 80 #45-23, Bogotá</code></td></tr>
                                    <tr><td><code>message</code></td><td><span class="badge badge-warning">No</span></td><td>{{ __('Mensaje personalizado completo (reemplaza el formato automático)') }}</td><td><code>Alerta: vehículo en movimiento</code></td></tr>
                                </tbody>
                            </table>
                        </div>

                        <h5 class="font-weight-bold mt-4 mb-2">{{ __('Ejemplo de mensaje que recibirá el conductor/responsable') }}</h5>
                        <div class="bg-light p-3 rounded" style="font-family:monospace; font-size:.85rem; white-space:pre-line;">🚨 <strong>Alerta [Nombre de tu empresa]</strong>
📦 <strong>Unidad:</strong> Camión 01
⚡ <strong>Evento:</strong> Exceso de velocidad
🚗 <strong>Velocidad:</strong> 120 km/h
📍 <strong>Dirección:</strong> Calle 80 #45-23, Bogotá
🗺 <strong>Mapa:</strong> https://maps.google.com/?q=4.6097,-74.0817
🕐 <strong>Hora:</strong> 2026-04-23 14:35:00</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@push('scripts')
<script>
function copySmsGwUrl() {
    var input = document.getElementById('sms-gw-url');
    navigator.clipboard.writeText(input.value).then(function() {
        var btn = input.parentElement.nextElementSibling.querySelector('button');
        var orig = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check mr-1"></i>Copiado';
        btn.classList.replace('btn-primary', 'btn-success');
        setTimeout(function() { btn.innerHTML = orig; btn.classList.replace('btn-success', 'btn-primary'); }, 2000);
    });
}
</script>
@endpush
@endsection