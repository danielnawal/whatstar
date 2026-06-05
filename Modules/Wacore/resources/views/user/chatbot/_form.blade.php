{{-- Partial reutilizado en modal crear y editar --}}
<div class="row">

    {{-- Columna izquierda --}}
    <div class="col-md-6">

        {{-- Dispositivo --}}
        <div class="form-group">
            <label><i class="fas fa-mobile-alt mr-1"></i>Dispositivo WhatsApp</label>
            <select class="form-control" name="device" required>
                @foreach($devices as $dev)
                    <option value="{{ $dev->id }}">{{ $dev->name }} – {{ $dev->phone }}</option>
                @endforeach
            </select>
        </div>

        {{-- Tipo de coincidencia --}}
        <div class="form-group">
            <label><i class="fas fa-search mr-1"></i>Tipo de coincidencia</label>
            <select class="form-control match-type-sel" name="match_type" required>
                <option value="equal">🎯 Palabra exacta</option>
                <option value="starts_with">▶ Empieza con…</option>
                <option value="contains">🔍 Contiene la palabra</option>
                <option value="like">～ Similar (coincidencia parcial)</option>
                <option value="fuzzy">🧠 Fuzzy IA (tolera typos y errores)</option>
                <option value="regex">⚙ Expresión regular</option>
                <option value="any">⭐ Cualquier mensaje (respuesta por defecto)</option>
                <option value="first_contact">👋 Primer contacto (bienvenida)</option>
            </select>
            <small class="form-text text-muted keyword-hint">
                Escribe la palabra que debe recibir el bot para responder.
            </small>
        </div>

        {{-- Palabra clave --}}
        <div class="form-group keyword-field">
            <label><i class="fas fa-key mr-1"></i>Palabra clave</label>
            <textarea name="keyword" class="form-control" rows="2"
                      placeholder="ej: hola, info, 1"></textarea>
            <small class="form-text text-muted">
                Puedes escribir varias separadas por coma: <code>hola, buenos días, hi</code>
            </small>
        </div>

        {{-- Prioridad --}}
        <div class="form-group">
            <label><i class="fas fa-sort-amount-up mr-1"></i>Prioridad
                <small class="text-muted">(mayor número = se revisa primero)</small>
            </label>
            <input type="number" name="priority" class="form-control" value="0" min="0" max="100">
        </div>

        {{-- Regla padre (flujo) --}}
        <div class="form-group">
            <label><i class="fas fa-sitemap mr-1"></i>Es sub-opción de (flujo)
                <small class="text-muted">– Opcional</small>
            </label>
            <select class="form-control" name="parent_reply_id">
                <option value="">— Regla de nivel superior —</option>
                @foreach($allReplies as $pr)
                    @if($pr->parent_reply_id === null)
                        <option value="{{ $pr->id }}">#{{ $pr->id }} – {{ $pr->keyword }}</option>
                    @endif
                @endforeach
            </select>
            <small class="form-text text-muted">
                Si seleccionas una regla padre, esta opción solo se evalúa cuando el usuario
                está dentro del flujo iniciado por esa regla (ej: menú → opción 1, 2, 3).
            </small>
        </div>
    </div>

    {{-- Columna derecha --}}
    <div class="col-md-6">

        {{-- Tipo de respuesta --}}
        <div class="form-group">
            <label><i class="fas fa-reply mr-1"></i>Tipo de respuesta</label>
            <select class="form-control reply_type" name="reply_type" required>
                <option value="text">Texto libre</option>
                <option value="template">Plantilla</option>
            </select>
        </div>

        <div class="form-group reply-text-area">
            <label>🇪🇸 Texto de respuesta (Español) <small class="text-muted">– principal</small></label>
            <textarea class="form-control" name="reply" rows="4" maxlength="2000"
                      placeholder="Ej: Hola! Selecciona una opción:&#10;1. Soporte&#10;2. Ventas&#10;3. Alertas"></textarea>
            <small class="form-text text-muted"><span class="char-count">0</span>/2000 caracteres</small>
        </div>

        {{-- Variantes multilenguaje (opcionales). El bot detecta el idioma del cliente
             y usa la variante correspondiente automáticamente. Si no hay variante,
             se usa el texto principal en español. --}}
        <div class="form-group reply-text-area">
            <label>
                🇺🇸 Texto en inglés <small class="text-muted">– opcional, auto-detección</small>
            </label>
            <textarea class="form-control" name="reply_en" rows="3" maxlength="2000"
                      placeholder="Ex: Hi! Choose an option:&#10;1. Support&#10;2. Sales&#10;3. Alerts"></textarea>
            <small class="form-text text-muted">
                Si el cliente escribe en inglés (ej: "hello", "what is the price?"), el bot responderá con este texto.
            </small>
        </div>

        <div class="form-group reply-text-area">
            <label>
                🇧🇷 Texto en portugués (Brasil) <small class="text-muted">– opcional, auto-detección</small>
            </label>
            <textarea class="form-control" name="reply_pt" rows="3" maxlength="2000"
                      placeholder="Ex: Olá! Escolha uma opção:&#10;1. Suporte&#10;2. Vendas&#10;3. Alertas"></textarea>
            <small class="form-text text-muted">
                Se o cliente escrever em português (ex: "olá", "quanto custa?"), o bot responderá com este texto.
            </small>
        </div>

        {{-- Archivo adjunto opcional (PDF, imagen, doc, audio).
             Se envía junto con el texto al matchear esta regla. --}}
        <div class="form-group reply-text-area">
            <label>
                📎 Archivo adjunto <small class="text-muted">– opcional</small>
            </label>
            <div class="current-media-info" style="display:none;">
                <div class="alert alert-info py-2 mb-2">
                    <i class="fas fa-paperclip"></i>
                    <span class="current-media-name"></span>
                    <a href="#" target="_blank" class="current-media-link ml-2">Ver</a>
                    <label class="float-right mb-0">
                        <input type="checkbox" name="media_remove" value="1"> Eliminar
                    </label>
                </div>
            </div>
            <input type="file" name="media_file" class="form-control-file"
                   accept=".pdf,.jpg,.jpeg,.png,.webp,.docx,.xlsx,.csv,.txt,.mp3,.ogg,.mp4">
            <small class="form-text text-muted">
                PDF, imagen, audio o video (máx 20 MB). Se envía como adjunto junto con el texto de la respuesta.
            </small>
        </div>

        <div class="form-group reply-template-area" style="display:none;">
            <label>Plantilla</label>
            <select class="form-control" name="template">
                @foreach($templates ?? [] as $tpl)
                    <option value="{{ $tpl->id }}">{{ $tpl->title }}</option>
                @endforeach
            </select>
        </div>

        {{-- Cooldown --}}
        <div class="form-group">
            <label><i class="fas fa-clock mr-1"></i>Cooldown entre respuestas (minutos)
                <small class="text-muted">– 0 = responde siempre</small>
            </label>
            <input type="number" name="cooldown_minutes" class="form-control" value="0" min="0">
            <small class="form-text text-muted">
                Ej: 60 → no vuelve a responder al mismo número hasta que pase 1 hora.
            </small>
        </div>

        {{-- Solo una vez --}}
        <div class="form-group">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" name="only_once"
                       id="only_once_{{ $prefix }}" value="1">
                <label class="custom-control-label" for="only_once_{{ $prefix }}">
                    <i class="fas fa-ban mr-1"></i>Responder solo <strong>una vez por contacto</strong>
                    (nunca vuelve a responderle)
                </label>
            </div>
        </div>
    </div>
</div>

{{-- ── Sección: Mensajes Interactivos ──────────────────────────────────── --}}
<div class="row mt-3">
    <div class="col-12">
        <hr class="my-2">
        <h6 class="text-primary mb-3"><i class="fas fa-list-ul mr-1"></i>Mensajes Interactivos</h6>
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label>Tipo de mensaje interactivo</label>
                    <select class="form-control interactive-type-sel" name="interactive_type">
                        <option value="">— Texto normal —</option>
                        <option value="list">📋 Lista (hasta 10 opciones)</option>
                        <option value="buttons">🔘 Botones (hasta 3)</option>
                    </select>
                    <small class="form-text text-muted">
                        Las opciones se muestran como menú seleccionable en WhatsApp.
                    </small>
                </div>
            </div>
            <div class="col-md-8 interactive-options-area" style="display:none;">
                <div class="form-group">
                    <label>Opciones
                        <small class="text-muted interactive-limit-hint">(máx 10 para lista, máx 3 para botones)</small>
                    </label>
                    <div class="interactive-options-list mb-2">
                        {{-- Las opciones se renderizan dinámicamente con JS --}}
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary add-interactive-opt">
                        <i class="fas fa-plus mr-1"></i>Agregar opción
                    </button>
                    <input type="hidden" name="interactive_options" class="interactive-options-json" value="[]">
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Sección: Captura de Datos (Lead Capture) ───────────────────────────── --}}
<div class="row mt-1">
    <div class="col-12">
        <hr class="my-2">
        <h6 class="text-primary mb-3"><i class="fas fa-user-plus mr-1"></i>Captura de Datos del Lead</h6>
        <div class="row">
            <div class="col-md-5">
                <div class="form-group">
                    <label>Campo a capturar</label>
                    <select class="form-control" name="capture_field">
                        <option value="">— No capturar —</option>
                        <option value="nombre">Nombre</option>
                        <option value="email">Email</option>
                        <option value="telefono">Teléfono</option>
                        <option value="interes">Interés / Producto</option>
                        <option value="empresa">Empresa</option>
                        <option value="extra">Otro (campo extra)</option>
                    </select>
                    <small class="form-text text-muted">
                        La respuesta del usuario a <strong>este</strong> mensaje se guardará en el lead.
                        Usa sub-reglas encadenadas para capturar varios campos.
                    </small>
                </div>
            </div>
            <div class="col-md-7">
                <div class="alert alert-light border py-2 px-3 mt-4" style="font-size:0.82rem;">
                    <i class="fas fa-info-circle text-info mr-1"></i>
                    <strong>¿Cómo funciona?</strong> El bot hace la pregunta configurada arriba.
                    El siguiente mensaje del usuario se guarda como el campo elegido.
                    Crea un sub-flujo con varias reglas de captura para un formulario completo.
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ── Sección: Transferencia a Agente ────────────────────────────────────── --}}
<div class="row mt-1">
    <div class="col-12">
        <hr class="my-2">
        <h6 class="text-primary mb-3"><i class="fas fa-headset mr-1"></i>Transferencia a Agente Humano</h6>
        <div class="custom-control custom-checkbox mb-2">
            <input type="checkbox" class="custom-control-input" name="trigger_handoff"
                   id="handoff_{{ $prefix }}" value="1">
            <label class="custom-control-label" for="handoff_{{ $prefix }}">
                <strong>Transferir a agente</strong> al activarse esta regla
            </label>
        </div>
        <small class="form-text text-muted">
            Cuando se active esta regla, el bot se pausará y se notificará al agente configurado en el dispositivo.
            El bot se reactiva con el comando <code>#bot</code>.
            El texto de respuesta de arriba se enviará como mensaje de despedida antes de pausar.
        </small>
    </div>
</div>

{{-- Horario de atención (fila completa) --}}
<div class="row mt-2">
    <div class="col-12">
        <div class="schedule-section">
            <div class="d-flex align-items-center mb-2">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input schedule-toggle"
                           name="schedule_enabled" id="sched_{{ $prefix }}" value="1">
                    <label class="custom-control-label" for="sched_{{ $prefix }}">
                        <i class="fas fa-calendar-alt mr-1"></i>
                        <strong>Activar horario de atención</strong>
                        <small class="text-muted ml-2">– Solo responde dentro del horario configurado</small>
                    </label>
                </div>
            </div>

            <div class="schedule-body" style="display:none;">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Hora inicio</label>
                            <input type="time" name="schedule_start" class="form-control" value="08:00">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Hora fin</label>
                            <input type="time" name="schedule_end" class="form-control" value="18:00">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Días activos</label><br>
                            @foreach(['1'=>'Lun','2'=>'Mar','3'=>'Mié','4'=>'Jue','5'=>'Vie','6'=>'Sáb','7'=>'Dom'] as $num => $name)
                            <div class="custom-control custom-checkbox custom-control-inline">
                                <input type="checkbox" class="custom-control-input"
                                       name="schedule_days[]" value="{{ $num }}"
                                       id="day_{{ $prefix }}_{{ $num }}"
                                       {{ in_array($num, [1,2,3,4,5]) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="day_{{ $prefix }}_{{ $num }}">
                                    {{ $name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label>Mensaje fuera de horario
                        <small class="text-muted">(opcional – si está vacío, no responde fuera de horario)</small>
                    </label>
                    <textarea class="form-control" name="out_of_hours_reply" rows="2"
                              placeholder="Ej: Nuestro horario es de 8am a 6pm. Te responderemos pronto 🙏"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    // ── Activar horario ────────────────────────────────────────────────
    $(document).on('change', '.schedule-toggle', function(){
        $(this).closest('.schedule-section').find('.schedule-body')
               .toggle(this.checked);
    });

    // ── Tipo de coincidencia ────────────────────────────────────────────
    $(document).on('change', '.match-type-sel', function(){
        const val    = $(this).val();
        const parent = $(this).closest('.modal-body, form');
        const hints  = {
            equal:         'El mensaje debe ser exactamente igual a la palabra clave.',
            starts_with:   'El mensaje debe EMPEZAR con la palabra clave.',
            contains:      'La palabra clave debe aparecer como palabra completa en el mensaje.',
            like:          'El mensaje contiene algo parecido a la palabra clave (coincidencia parcial).',
            fuzzy:         'IA inteligente: tolera typos y errores ortográficos (ej: "informasion", "preeio", "asezor"). Recomendado.',
            regex:         'Expresión regular (avanzado). Ej: ^hola|^hi para capturar saludos.',
            any:           'Responde a CUALQUIER mensaje que llegue (úsalo como respuesta por defecto).',
            first_contact: 'Solo se activa la PRIMERA vez que un número escribe al bot (bienvenida).',
        };
        parent.find('.keyword-hint').text(hints[val] || '');
        parent.find('.keyword-field').toggle(val !== 'any' && val !== 'first_contact');
    });

    // ── Tipo de respuesta ───────────────────────────────────────────────
    $(document).on('change', '.reply_type', function(){
        const parent = $(this).closest('.modal-body, form');
        const isTemplate = $(this).val() === 'template';
        parent.find('.reply-text-area').toggle(!isTemplate);
        parent.find('.reply-template-area').toggle(isTemplate);
    });

    // ── Contador de caracteres ──────────────────────────────────────────
    $(document).on('input', '[name="reply"]', function(){
        $(this).siblings('small').find('.char-count').text($(this).val().length);
    });

    // ── Mensajes interactivos: mostrar/ocultar builder ─────────────────
    $(document).on('change', '.interactive-type-sel', function(){
        const val    = $(this).val();
        const parent = $(this).closest('.col-12');
        const area   = parent.find('.interactive-options-area');
        area.toggle(val === 'list' || val === 'buttons');
        parent.find('.interactive-limit-hint').text(
            val === 'buttons' ? '(máx 3 botones)' : '(máx 10 opciones)'
        );
        syncInteractiveJson(parent);
    });

    // ── Agregar opción ─────────────────────────────────────────────────
    $(document).on('click', '.add-interactive-opt', function(){
        const parent   = $(this).closest('.col-12');
        const type     = parent.find('.interactive-type-sel').val();
        const list     = parent.find('.interactive-options-list');
        const idx      = list.find('.interactive-opt-row').length;
        const max      = type === 'buttons' ? 3 : 10;
        if (idx >= max) {
            alert('Máximo ' + max + ' opciones para este tipo.');
            return;
        }
        const withDesc = type === 'list';
        list.append(buildOptRow(idx, '', '', withDesc));
    });

    // ── Eliminar opción ────────────────────────────────────────────────
    $(document).on('click', '.remove-interactive-opt', function(){
        const parent = $(this).closest('.col-12');
        $(this).closest('.interactive-opt-row').remove();
        renumberOptRows(parent);
        syncInteractiveJson(parent);
    });

    // ── Actualizar JSON cuando cambian los inputs ──────────────────────
    $(document).on('input change', '.interactive-opt-text, .interactive-opt-desc', function(){
        const parent = $(this).closest('.col-12');
        syncInteractiveJson(parent);
    });

    function buildOptRow(idx, text, desc, withDesc){
        const descField = withDesc
            ? `<input type="text" class="form-control form-control-sm ml-1 interactive-opt-desc"
                      placeholder="Descripción (opcional)" style="flex:1.2;" value="${desc}">`
            : '';
        return `<div class="d-flex align-items-center mb-1 interactive-opt-row" data-idx="${idx}">
            <span class="badge badge-secondary mr-1" style="min-width:22px;">${idx+1}</span>
            <input type="text" class="form-control form-control-sm interactive-opt-text"
                   placeholder="Texto opción ${idx+1}" style="flex:1;" value="${text}">
            ${descField}
            <button type="button" class="btn btn-sm btn-link text-danger ml-1 remove-interactive-opt"
                    title="Eliminar"><i class="fas fa-times"></i></button>
        </div>`;
    }

    function renumberOptRows(container){
        container.find('.interactive-opt-row').each(function(i){
            $(this).attr('data-idx', i);
            $(this).find('.badge').text(i+1);
            $(this).find('.interactive-opt-text').attr('placeholder', 'Texto opción '+(i+1));
        });
    }

    function syncInteractiveJson(container){
        const type = container.find('.interactive-type-sel').val();
        const opts = [];
        container.find('.interactive-opt-row').each(function(i){
            const text = $(this).find('.interactive-opt-text').val().trim();
            if (!text) return;
            const opt = {
                id:   'opt_' + (i+1),
                text: text,
            };
            if (type === 'list') {
                opt.description = $(this).find('.interactive-opt-desc').val().trim();
            }
            opts.push(opt);
        });
        container.find('.interactive-options-json').val(JSON.stringify(opts));
    }

    // ── Cargar opciones existentes al editar ───────────────────────────
    // (llamar después de poblar el modal con datos existentes)
    window.loadInteractiveOptions = function(container, type, optionsJson) {
        if (!type || type === '') return;
        container.find('.interactive-type-sel').val(type).trigger('change');
        let opts = [];
        try { opts = JSON.parse(optionsJson || '[]'); } catch(e) {}
        const list = container.find('.interactive-options-list').empty();
        opts.forEach(function(opt, i) {
            list.append(buildOptRow(i, opt.text || '', opt.description || '', type === 'list'));
        });
        syncInteractiveJson(container);
    };
})();
</script>
