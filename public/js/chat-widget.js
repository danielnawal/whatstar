/**
 * APPOGIO WhatsApp Chat Widget
 * Embed: <script src="/js/chat-widget.js" data-phone="573052292372" data-msg="Hola, quiero info sobre APPOGIO GPS"></script>
 */
(function () {
  var s = document.currentScript || (function () {
    var scripts = document.getElementsByTagName('script');
    return scripts[scripts.length - 1];
  })();

  var phone   = s.getAttribute('data-phone')   || '';
  var msg     = s.getAttribute('data-msg')      || 'Hola, quiero información sobre APPOGIO GPS';
  var color   = s.getAttribute('data-color')    || '#25d366';
  var pos     = s.getAttribute('data-position') || 'right';
  var label   = s.getAttribute('data-label')    || '¿Hablamos?';
  var delay   = parseInt(s.getAttribute('data-delay') || '3', 10) * 1000;

  if (!phone) return;

  // Inyectar CSS
  var style = document.createElement('style');
  style.textContent = [
    '#wa-widget-btn{position:fixed;bottom:24px;' + pos + ':24px;z-index:9999;display:flex;align-items:center;gap:10px;cursor:pointer;animation:waBounce .6s ease;}',
    '@keyframes waBounce{0%,100%{transform:scale(1)}50%{transform:scale(1.08)}}',
    '#wa-widget-bubble{background:' + color + ';width:58px;height:58px;border-radius:50%;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 16px rgba(0,0,0,.25);transition:transform .2s;}',
    '#wa-widget-bubble:hover{transform:scale(1.1)}',
    '#wa-widget-bubble svg{width:32px;height:32px;fill:#fff}',
    '#wa-widget-label{background:#fff;border-radius:20px;padding:8px 14px;font-family:sans-serif;font-size:14px;font-weight:600;color:#111;box-shadow:0 2px 8px rgba(0,0,0,.15);white-space:nowrap;display:none}',
    '#wa-widget-btn:hover #wa-widget-label{display:block}',
    '#wa-widget-popup{position:fixed;bottom:95px;' + pos + ':24px;z-index:9998;width:300px;background:#fff;border-radius:16px;box-shadow:0 8px 32px rgba(0,0,0,.2);overflow:hidden;display:none;font-family:sans-serif}',
    '#wa-widget-popup.open{display:block;animation:waSlide .3s ease}',
    '@keyframes waSlide{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}',
    '#wa-popup-header{background:' + color + ';padding:16px;color:#fff;display:flex;align-items:center;gap:10px}',
    '#wa-popup-avatar{width:40px;height:40px;border-radius:50%;background:rgba(255,255,255,.3);display:flex;align-items:center;justify-content:center}',
    '#wa-popup-body{padding:16px}',
    '#wa-popup-body p{color:#555;font-size:13px;line-height:1.5;margin:0 0 12px}',
    '#wa-popup-cta{display:block;background:' + color + ';color:#fff;text-align:center;padding:11px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px}',
    '#wa-popup-cta:hover{opacity:.9}',
    '#wa-popup-close{margin-left:auto;cursor:pointer;opacity:.8;font-size:18px;line-height:1}',
  ].join('');
  document.head.appendChild(style);

  // Construir HTML
  var waUrl = 'https://wa.me/' + phone.replace(/[^0-9]/g, '') + '?text=' + encodeURIComponent(msg);

  var btn = document.createElement('div');
  btn.id = 'wa-widget-btn';
  btn.innerHTML = [
    '<div id="wa-widget-label">' + label + '</div>',
    '<div id="wa-widget-bubble">',
      '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">',
        '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>',
        '<path d="M12 0C5.373 0 0 5.373 0 12c0 2.099.54 4.07 1.485 5.786L.057 23.93l6.304-1.654A11.94 11.94 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22c-1.876 0-3.638-.491-5.164-1.354l-.37-.22-3.824 1.003 1.02-3.726-.242-.383A9.96 9.96 0 012 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>',
      '</svg>',
    '</div>',
  ].join('');
  document.body.appendChild(btn);

  var popup = document.createElement('div');
  popup.id = 'wa-widget-popup';
  popup.innerHTML = [
    '<div id="wa-popup-header">',
      '<div id="wa-popup-avatar"><svg viewBox="0 0 24 24" width="22" height="22" fill="#fff"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413z"/></svg></div>',
      '<div><div style="font-weight:700;font-size:14px">APPOGIO GPS</div><div style="font-size:11px;opacity:.85">Normalmente responde en minutos</div></div>',
      '<span id="wa-popup-close">✕</span>',
    '</div>',
    '<div id="wa-popup-body">',
      '<p>¡Hola! 👋 Estamos disponibles para ayudarte con tu plataforma GPS. Escríbenos directamente por WhatsApp.</p>',
      '<a id="wa-popup-cta" href="' + waUrl + '" target="_blank" rel="noopener">Iniciar conversación →</a>',
    '</div>',
  ].join('');
  document.body.appendChild(popup);

  // Interacción
  function togglePopup(open) {
    popup.classList.toggle('open', open);
  }

  btn.addEventListener('click', function () {
    togglePopup(!popup.classList.contains('open'));
  });

  document.getElementById('wa-popup-close').addEventListener('click', function (e) {
    e.stopPropagation();
    togglePopup(false);
  });

  // Abrir popup automáticamente después del delay
  if (delay > 0) {
    setTimeout(function () {
      var already = sessionStorage.getItem('wa_widget_shown');
      if (!already) {
        togglePopup(true);
        sessionStorage.setItem('wa_widget_shown', '1');
      }
    }, delay);
  }
})();
