@extends('layouts.main.app')
@section('head')
@include('layouts.main.headersection',['title'=> 'Chatbot Inteligente','buttons'=>[
    [
        'name'        => '<i class="fas fa-plus"></i> &nbsp;Nueva Regla',
        'url'         => '#',
        'components'  => 'data-toggle="modal" data-target="#createModal"',
        'is_button'   => true,
    ]
]])
@endsection

@push('css')
<style>
.badge-match { font-size:.72rem; padding:3px 8px; border-radius:10px; }
.badge-equal       { background:#d1e7dd; color:#0f5132; }
.badge-starts_with { background:#cff4fc; color:#055160; }
.badge-contains    { background:#fff3cd; color:#664d03; }
.badge-regex       { background:#f8d7da; color:#842029; }
.badge-any         { background:#e2e3e5; color:#383d41; }
.badge-like          { background:#fff3cd; color:#664d03; }
.badge-first_contact { background:#d0f0c0; color:#155724; }
.schedule-section  { border:1px solid #dee2e6; border-radius:8px; padding:12px; margin-top:8px; background:#f8f9fa; }
.flow-indent       { border-left:3px solid #0d6efd; padding-left:12px; background:#f0f4ff; }
.priority-badge    { background:#6f42c1; color:#fff; border-radius:10px; padding:2px 8px; font-size:.7rem; }
</style>
@endpush

@section('content')
<div class="row justify-content-center">
    <div class="col-12">

        {{-- Stats --}}
        <div class="row d-flex justify-content-between flex-wrap">
            <div class="col">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col"><span class="h2 font-weight-bold mb-0">{{ number_format($total_replies) }}</span></div>
                            <div class="col-auto"><div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-robot"></i></div></div>
                        </div>
                        <h5 class="card-title text-muted mb-0 mt-3">Total de Reglas</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col"><span class="h2 font-weight-bold mb-0">{{ number_format($template_replies) }}</span></div>
                            <div class="col-auto"><div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fi fi-rs-test mt-2"></i></div></div>
                        </div>
                        <h5 class="card-title text-muted mb-0 mt-3">Respuestas con Plantilla</h5>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col"><span class="h2 font-weight-bold mb-0">{{ number_format($text_replies) }}</span></div>
                            <div class="col-auto"><div class="icon icon-shape bg-gradient-primary text-white rounded-circle shadow"><i class="fas fa-comment-alt mt-2"></i></div></div>
                        </div>
                        <h5 class="card-title text-muted mb-0 mt-3">Respuestas de Texto</h5>
                    </div>
                </div>
            </div>
        </div>

        @if(getUserPlanData('chatbot') == false)
        <div class="alert bg-gradient-primary text-white" role="alert">
            <strong>Chatbot no disponible</strong> en tu plan actual.
        </div>
        @endif

        {{-- Tabla de reglas --}}
        @if(count($replies ?? []) == 0)
        <div class="card"><div class="card-body text-center">
            <img src="{{ asset('assets/img/404.jpg') }}" height="300">
            <h3>Aún no tienes reglas de chatbot</h3>
            <a href="#" data-toggle="modal" data-target="#createModal" class="btn btn-neutral">
                <i class="fas fa-plus"></i> Crear primera regla
            </a>
        </div></div>
        @else
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Palabra clave</th>
                                <th>Dispositivo</th>
                                <th>Tipo</th>
                                <th>Coincidencia</th>
                                <th>Extras</th>
                                <th class="text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($replies as $reply)
                            <tr class="{{ $reply->parent_reply_id ? 'flow-indent' : '' }}">
                                <td>
                                    @if($reply->parent_reply_id)
                                        <small class="text-muted">↳ sub-opción de #{{ $reply->parent_reply_id }}</small><br>
                                    @endif
                                    <strong>{{ $reply->match_type === 'any' ? '⭐ Cualquier mensaje' : ($reply->match_type === 'first_contact' ? '👋 Primer contacto' : $reply->keyword) }}</strong>
                                </td>
                                <td>{{ $reply->device->phone ?? '—' }}</td>
                                <td>{{ $reply->reply_type }}</td>
                                <td>
                                    <span class="badge-match badge-{{ $reply->match_type }}">
                                        {{ [
                                            'equal'         => 'Exacta',
                                            'starts_with'   => 'Empieza con',
                                            'contains'      => 'Contiene',
                                            'like'          => 'Similar',
                                            'regex'         => 'Regex',
                                            'any'           => 'Cualquiera',
                                            'first_contact' => 'Primer contacto',
                                        ][$reply->match_type] ?? $reply->match_type }}
                                    </span>
                                </td>
                                <td>
                                    @if($reply->priority > 0)
                                        <span class="priority-badge">P{{ $reply->priority }}</span>
                                    @endif
                                    @if($reply->cooldown_minutes > 0)
                                        <small class="text-muted ml-1"><i class="fas fa-clock"></i> {{ $reply->cooldown_minutes }}min</small>
                                    @endif
                                    @if($reply->only_once)
                                        <small class="text-warning ml-1"><i class="fas fa-ban"></i> 1 vez</small>
                                    @endif
                                    @if($reply->schedule_enabled)
                                        <small class="text-info ml-1"><i class="fas fa-calendar-check"></i> Horario</small>
                                    @endif
                                    @if($reply->interactive_type === 'list')
                                        <small class="text-primary ml-1"><i class="fas fa-list-ul"></i> Lista</small>
                                    @elseif($reply->interactive_type === 'buttons')
                                        <small class="text-primary ml-1"><i class="fas fa-grip-horizontal"></i> Botones</small>
                                    @endif
                                    @if($reply->capture_field)
                                        <small class="text-success ml-1"><i class="fas fa-user-plus"></i> Captura</small>
                                    @endif
                                    @if($reply->trigger_handoff)
                                        <small class="text-danger ml-1"><i class="fas fa-headset"></i> Handoff</small>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group float-right">
                                        <button class="btn btn-neutral btn-sm dropdown-toggle" data-toggle="dropdown">Acción</button>
                                        <div class="dropdown-menu">
                                            <a class="dropdown-item edit-reply" href="#"
                                               data-id="{{ $reply->id }}"
                                               data-action="{{ route('user.chatbot.update', $reply->id) }}"
                                               data-keyword="{{ $reply->keyword }}"
                                               data-reply="{{ $reply->reply }}"
                                               data-replyen="{{ $reply->reply_en }}"
                                               data-replypt="{{ $reply->reply_pt }}"
                                               data-mediapath="{{ $reply->media_path }}"
                                               data-mediafilename="{{ $reply->media_filename }}"
                                               data-matchtype="{{ $reply->match_type }}"
                                               data-replytype="{{ $reply->reply_type }}"
                                               data-device="{{ $reply->device_id }}"
                                               data-templateid="{{ $reply->template_id }}"
                                               data-priority="{{ $reply->priority }}"
                                               data-cooldown="{{ $reply->cooldown_minutes }}"
                                               data-onlyonce="{{ $reply->only_once }}"
                                               data-scheduleenabled="{{ $reply->schedule_enabled }}"
                                               data-schedulestart="{{ $reply->schedule_start }}"
                                               data-scheduleend="{{ $reply->schedule_end }}"
                                               data-scheduledays="{{ $reply->schedule_days }}"
                                               data-outofhours="{{ $reply->out_of_hours_reply }}"
                                               data-parentid="{{ $reply->parent_reply_id }}"
                                               data-interactivetype="{{ $reply->interactive_type }}"
                                               data-interactiveoptions="{{ htmlspecialchars($reply->interactive_options ?? '[]') }}"
                                               data-capturefield="{{ $reply->capture_field }}"
                                               data-triggerhandoff="{{ $reply->trigger_handoff }}"
                                               data-toggle="modal" data-target="#editModal">
                                                <i class="ni ni-align-left-2"></i> Editar
                                            </a>
                                            <a class="dropdown-item delete-confirm" href="javascript:void(0)"
                                               data-action="{{ route('user.chatbot.destroy', $reply->id) }}">
                                                <i class="fas fa-trash"></i> Eliminar
                                            </a>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-center">{{ $replies->links('vendor.pagination.bootstrap-4') }}</div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ==================== MODAL CREAR ==================== --}}
<div class="modal fade" id="createModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('user.chatbot.store') }}" class="ajaxform_instant_reload" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-robot mr-2"></i>Nueva Regla de Chatbot</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    @include('wacore::user.chatbot._form', ['prefix' => 'c', 'reply' => null, 'allReplies' => $replies])
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary submit-btn">
                        <i class="fas fa-save mr-1"></i> Guardar regla
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL EDITAR ==================== --}}
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="" class="ajaxform_instant_reload edit-reply-form" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit mr-2"></i>Editar Regla</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    @include('wacore::user.chatbot._form', ['prefix' => 'e', 'reply' => null, 'allReplies' => $replies])
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary submit-btn">
                        <i class="fas fa-save mr-1"></i> Actualizar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('js')
<script src="{{ asset('assets/js/pages/user/chatbot.js') }}"></script>
<script>
// ── Rellenar modal de edición ──────────────────────────────────────────────
$(document).on('click', '.edit-reply', function () {
    const d = $(this).data();
    const form = $('.edit-reply-form');

    form.attr('action', d.action);
    form.find('[name="keyword"]').val(d.keyword);
    form.find('[name="reply"]').val(d.reply);
    form.find('[name="reply_en"]').val(d.replyen || '');
    form.find('[name="reply_pt"]').val(d.replypt || '');

    // Mostrar info del archivo adjunto actual (si existe) en el modal de editar
    if (d.mediapath) {
        form.find('.current-media-info').show();
        form.find('.current-media-name').text(d.mediafilename || 'archivo adjunto');
        form.find('.current-media-link').attr('href', d.mediapath);
        form.find('[name="media_remove"]').prop('checked', false);
    } else {
        form.find('.current-media-info').hide();
    }
    form.find('[name="media_file"]').val(''); // limpiar selección anterior

    form.find('[name="match_type"]').val(d.matchtype).trigger('change');
    form.find('[name="reply_type"]').val(d.replytype).trigger('change');
    form.find('[name="device"]').val(d.device);
    form.find('[name="template"]').val(d.templateid);
    form.find('[name="priority"]').val(d.priority || 0);
    form.find('[name="cooldown_minutes"]').val(d.cooldown || 0);
    form.find('[name="only_once"]').prop('checked', d.onlyonce == 1);
    form.find('[name="schedule_enabled"]').prop('checked', d.scheduleenabled == 1).trigger('change');
    form.find('[name="schedule_start"]').val(d.schedulestart);
    form.find('[name="schedule_end"]').val(d.scheduleend);
    form.find('[name="out_of_hours_reply"]').val(d.outofhours);
    form.find('[name="parent_reply_id"]').val(d.parentid || '');

    // Días
    const days = (d.scheduledays || '').split(',');
    form.find('[name="schedule_days[]"]').each(function () {
        $(this).prop('checked', days.includes($(this).val()));
    });

    // Campos de captura y handoff
    form.find('[name="capture_field"]').val(d.capturefield || '');
    form.find('[name="trigger_handoff"]').prop('checked', d.triggerhandoff == 1);

    // Mensajes interactivos: cargar opciones en el builder
    if (typeof window.loadInteractiveOptions === 'function') {
        const interactiveContainer = form.find('[name="interactive_type"]').closest('.col-12');
        window.loadInteractiveOptions(
            interactiveContainer,
            d.interactivetype || '',
            d.interactiveoptions || '[]'
        );
    }
});
</script>
@endpush
