 "use strict";

var successExist = $('.success-alert').length;

successExist > 0 ? congratulations() : '';
successExist > 0 ? congratulationsPride() : '';


loadStaticData();
var messagesTransactionDays=[];
var messagesTransactionValues=[];


function loadStaticData() {
  const url = $('#static-data').val();
  const base_url = $('#base_url').val();

  $.ajax({
    type: 'get',
    url: url,
    dataType: 'json',
    contentType: false,
    cache: false,
    processData:false,

    success: function(response){ 
      $('#total-device').html(response.devicesCount);
      $('#total-messages').html(response.messagesCount);
      $('#total-contacts').html(response.contactCount);
      $('#total-schedule').html(response.scheduleCount);
      
      
      $.each(response.devices, function(index, value){

        const device = `<li class="list-group-item px-0">
                          <div class="row align-items-center">
                            <div class="col ml--2">
                              <h4 class="mb-0">
                                <a href="${base_url}/user/device/${value.uuid}/qr">${value.name} ${value.phone != null ? '('+value.phone+')' : ''}</a>
                              </h4>
                              <span class="text-${value.status == 1 ? 'success' : 'danger'}">●</span>
                              <small>${value.status == 1 ? 'Online' : 'offline'}</small>
                            </div>
                            <div class="col-auto">
                              (${value.smstransaction_count} Messages)
                            </div>
                          </div>
                        </li>`;                
      $('#device-list').append(device)

      });

      $.each(response.messagesStatics, function(index, value){
        var dat=value.date;
        var transaction=value.smstransactions;

        messagesTransactionDays.push(dat);
        messagesTransactionValues.push(transaction);
      });
      initMessage(); 


      var chatbotReplyDate=[];
      var chatbotReplyCount=[];

      $.each(response.chatbotStatics,function(index, value){
          chatbotReplyDate.push(value.date);
          chatbotReplyCount.push(value.smstransactions);
      });

     initChatbotChart(chatbotReplyDate,chatbotReplyCount);

      var types=[];
      var typeCount=[];

      $.each(response.typeStatics,function(index, value){
          types.push(value.type);
          typeCount.push(value.smstransactions);
      });

     initMessagesTypes(types,typeCount)

     // Render premium analytics (KPIs + 3 charts). Wrapped in try/catch
     // para que un error aquí nunca rompa los charts viejos.
     try { if (response.premium) initPremiumAnalytics(response.premium); }
     catch(e) { console.error('Premium analytics render failed:', e); }

    }
  });


}

$('#period').on('change',function(){
  var days= $(this).val();
  const base_url = $('#base_url').val();
  const url = base_url+'/user/messages-transaction/'+days;
  $.ajax({
    type: 'get',
    url: url,
    dataType: 'json',
    contentType: false,
    cache: false,
    processData:false,

    success: function(response){ 
      messagesTransactionDays=[];
      messagesTransactionValues=[];

      $.each(response.messagesStatics, function(index, value){
        var dat=value.date;
        var transaction=value.smstransactions;

        messagesTransactionDays.push(dat);
        messagesTransactionValues.push(transaction);
      });
      initMessage(); 


      
    }
  });

});


$('#automaticReply').on('change',function(){
  var   days     = $(this).val();
  const base_url = $('#base_url').val();
  const url      = base_url+'/user/chatbot-transaction/'+days;
 
  $.ajax({
    type: 'get',
    url: url,
    dataType: 'json',
    contentType: false,
    cache: false,
    processData:false,

    success: function(response){ 
      var chatbotReplyDate=[];
      var chatbotReplyCount=[];

      $.each(response,function(index, value){
          chatbotReplyDate.push(value.date);
          chatbotReplyCount.push(value.smstransactions);
      });

     initChatbotChart(chatbotReplyDate,chatbotReplyCount);
    }
  });
});

$('#messagesTypes').on('change',function(){
  var   days     = $(this).val();
  const base_url = $('#base_url').val();
  const url      = base_url+'/user/messages-types-transaction/'+days;
 
  $.ajax({
    type: 'get',
    url: url,
    dataType: 'json',
    contentType: false,
    cache: false,
    processData:false,

    success: function(response){ 
      var types=[];
      var typeCount=[];

      $.each(response.typeStatics,function(index, value){
          types.push(value.type);
          typeCount.push(value.smstransactions);
      });

     initMessagesTypes(types,typeCount)
    }
  });

});

function initMessage() {
  var $chart = $('#chart-sales');
  var salesChart = new Chart($chart, {
    type: 'line',
    options: {
      scales: {
        yAxes: [{
          gridLines: {
            color: Charts.colors.gray[200],
            zeroLineColor: Charts.colors.gray[200]
          },
          ticks: {

          }
        }]
      }
    },
    data: {
      labels: messagesTransactionDays,
      datasets: [{
        label: 'Messages',
        data: messagesTransactionValues
      }]
    }
  });
  $chart.data('chart', salesChart);
}





  


  function initChatbotChart(days, values) {
    var $chart = $('#chart-bars');

    // Create chart
    var ordersChart = new Chart($chart, {
      type: 'bar',
      data: {
        labels: days,
        datasets: [{
          label: 'Replies',
          data: values
        }]
      }
    });

    // Save to jQuery object
    $chart.data('chart', ordersChart);
  }





  // Methods

  function initMessagesTypes(types, values) {

    var $chart = $('#chart-doughnut');

    var doughnutChart = new Chart($chart, {
      type: 'doughnut',
      data: {
        labels: types,
        datasets: [{
          data: values,
          backgroundColor: [
            Charts.colors.theme['danger'],
            Charts.colors.theme['warning'],
            Charts.colors.theme['success'],
            Charts.colors.theme['primary'],
            Charts.colors.theme['info'],
          ],
          label: 'Dataset 1'
        }],
      },
      options: {
        responsive: true,
        legend: {
          position: 'top',
        },
        animation: {
          animateScale: true,
          animateRotate: true
        }
      }
    });

    // Save to jQuery object

    $chart.data('chart', doughnutChart);

  };

  // ============================================================
  // Premium analytics: 4 KPIs + 3 charts adicionales.
  // Aditivo: NO modifica los charts existentes.
  // ============================================================
  function initPremiumAnalytics(p) {
    if (!p) return;

    // ---- CRM mini KPIs ----
    $('#kpi-leads-new').text(numberFmt(p.leadsNew ?? 0));
    $('#kpi-leads-30').text(numberFmt(p.leadsTotal30 ?? 0));
    $('#kpi-handoffs-pending').text(numberFmt(p.handoffsPending ?? 0));

    // ---- Recent leads table ----
    var $tbody = $('#recent-leads-table tbody');
    $tbody.empty();
    if (p.recentLeads && p.recentLeads.length) {
      $.each(p.recentLeads, function(_, l) {
        var statusBadge = l.status === 'new' ? 'badge-danger' : (l.status === 'in_progress' ? 'badge-warning' : 'badge-success');
        var date = l.created_at ? new Date(l.created_at).toLocaleString('es-ES', {day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}) : '';
        var contact = (l.contact || '').replace(/@.*$/, '');
        $tbody.append(
          '<tr>' +
          '<td>' + date + '</td>' +
          '<td>' + contact + '</td>' +
          '<td>' + (l.contact_name || '—') + '</td>' +
          '<td><span class="badge badge-light">' + (l.interest || '—') + '</span></td>' +
          '<td><span class="badge ' + statusBadge + '">' + (l.status || 'new') + '</span></td>' +
          '</tr>'
        );
      });
    } else {
      $tbody.append('<tr><td colspan="5" class="text-center text-muted py-3">No hay leads recientes</td></tr>');
    }

    // ---- KPIs ----
    $('#kpi-this-month').text(numberFmt(p.thisMonthMsgs));
    $('#kpi-prev-month').text(numberFmt(p.prevMonthMsgs));
    $('#kpi-bot-rate').text((p.botResponseRate ?? 0) + '%');
    $('#kpi-total-30').text(numberFmt(p.totalLast30));

    var delta = p.monthDeltaPct ?? 0;
    var arrow = delta > 0 ? '▲' : (delta < 0 ? '▼' : '—');
    var color = delta > 0 ? 'text-success' : (delta < 0 ? 'text-danger' : 'text-muted');
    $('#kpi-month-delta')
      .removeClass('text-success text-danger text-muted')
      .addClass(color)
      .html(arrow + ' ' + Math.abs(delta) + '% vs mes anterior');

    // ---- Chart: distribución horaria (line + área) ----
    if (p.byHour && p.byHour.length) {
      var hourLabels = p.byHour.map(function(x){ return ('0'+x.hour).slice(-2)+'h'; });
      var hourValues = p.byHour.map(function(x){ return x.count; });
      var $h = $('#chart-byhour');
      if ($h.length) {
        new Chart($h, {
          type: 'line',
          data: {
            labels: hourLabels,
            datasets: [{
              label: 'Mensajes',
              data: hourValues,
              borderColor: '#5e72e4',
              backgroundColor: 'rgba(94,114,228,0.15)',
              fill: true,
              tension: 0.35,
              pointRadius: 2
            }]
          },
          options: {
            responsive: true,
            legend: { display: false },
            scales: {
              yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
            }
          }
        });
      }
    }

    // ---- Chart: top templates del bot (horizontal bar) ----
    if (p.topTemplates && p.topTemplates.length) {
      var tplLabels = p.topTemplates.map(function(x){ return truncate(x.title || '(sin título)', 30); });
      var tplValues = p.topTemplates.map(function(x){ return x.uses; });
      var $t = $('#chart-toptemplates');
      if ($t.length) {
        new Chart($t, {
          type: 'horizontalBar',
          data: {
            labels: tplLabels,
            datasets: [{
              label: 'Usos',
              data: tplValues,
              backgroundColor: '#2dce89',
              borderColor: '#2dce89'
            }]
          },
          options: {
            responsive: true,
            legend: { display: false },
            scales: {
              xAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
            }
          }
        });
      }
    } else {
      $('#chart-toptemplates').replaceWith('<p class="text-muted text-center py-4">Aún no hay actividad de chatbot en los últimos 30 días.</p>');
    }

    // ---- Chart: nuevos contactos por día ----
    if (p.contactsByDay && p.contactsByDay.length) {
      var cLabels = p.contactsByDay.map(function(x){ return x.date; });
      var cValues = p.contactsByDay.map(function(x){ return x.c; });
      var $c = $('#chart-newcontacts');
      if ($c.length) {
        new Chart($c, {
          type: 'line',
          data: {
            labels: cLabels,
            datasets: [{
              label: 'Nuevos contactos',
              data: cValues,
              borderColor: '#fb6340',
              backgroundColor: 'rgba(251,99,64,0.15)',
              fill: true,
              tension: 0.3,
              pointRadius: 3
            }]
          },
          options: {
            responsive: true,
            legend: { display: false },
            scales: {
              yAxes: [{ ticks: { beginAtZero: true, precision: 0 } }]
            }
          }
        });
      }
    } else {
      $('#chart-newcontacts').replaceWith('<p class="text-muted text-center py-4">Aún no hay nuevos contactos registrados en 30 días.</p>');
    }
  }

  function numberFmt(n) {
    if (n === null || n === undefined) return '0';
    return Number(n).toLocaleString('es-ES');
  }
  function truncate(s, n) {
    s = String(s || '');
    return s.length > n ? s.substring(0, n-1) + '…' : s;
  }