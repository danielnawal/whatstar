<?php

use Illuminate\Support\Facades\Route;
use Modules\Wacore\Http\Controllers\WacoreController;

use Modules\Wacore\Http\Controllers\User as USER;

Route::group(['prefix' => 'user', 'as' => 'user.', 'middleware' => ['auth','user','saas']], function (){
   
   //all dashboard routes
   Route::get('dashboard',                       [USER\DashboardController::class, 'index'])->name('dashboard.index');
   Route::get('dashboard-static-data',           [USER\DashboardController::class, 'dashboardData'])->name('dashboard.static');
   Route::get('messages-transaction/{days}',     [USER\DashboardController::class, 'getMessagesTransaction'])->name('messages.static');
   Route::get('chatbot-transaction/{days}',      [USER\DashboardController::class, 'getChatbotTransaction'])->name('chatbot.static');
   Route::get('messages-types-transaction/{days}', [USER\DashboardController::class, 'messagesStatics'])->name('types.static');

   //device routes
   Route::resource('device',                    USER\DeviceController::class);
   Route::get('device/{id}/qr',                 [USER\DeviceController::class,'scanQr'])->name('device.scan');
   Route::post('create-session/{id}',           [USER\DeviceController::class,'getQr']);
   Route::post('check-session/{id}',            [USER\DeviceController::class,'checkSession']);
   Route::post('/logout-session/{id}',          [USER\DeviceController::class,'logoutSession']);
   Route::post('/device-statics',               [USER\DeviceController::class,'deviceStatics']);
  
   Route::get('/device/chats/{uuid}',           [USER\ChatController::class,'chats']);
   Route::post('/get-chats/{uuid}',             [USER\ChatController::class,'chatHistory']);
   Route::post('/send-message/{uuid}',          [USER\ChatController::class,'sendMessage'])->name('chat.send-message');

   Route::get('/device/groups/{uuid}',          [USER\ChatController::class,'groups']);
   Route::post('/get-groups/{uuid}',            [USER\ChatController::class,'groupHistory']);
   Route::post('/send-group-message/{uuid}',    [USER\ChatController::class,'sendGroupMessage'])->name('group.send-message');
   Route::post('/send-group-bulk-message/{uuid}',    [USER\ChatController::class,'sendGroupBulkMessage'])->name('group.bulk.send-message');
   
   Route::post('/get-group-metadata',      [USER\ChatController::class,'getGroupMetaData'])->name('group.matadata');

   //app routes
   Route::resource('apps',                      USER\AppsController::class);
   Route::get('/app/integration/{uuid}',        [USER\AppsController::class,'integration'])->name('app.integration');
   Route::get('/app/messages-logs/{uuid}',      [USER\AppsController::class,'logs'])->name('app.logs');

   //template routes
   Route::resource('template',                  USER\TemplateController::class);
   Route::post('/template/store/{type}',        [USER\TemplateController::class,'store'])->name('template.store-now');

   //single send or custom text routes
   Route::get('/sent-text-message',                [USER\CustomTextController::class,'index']);
   Route::post('/sent-whatsapp-custom-text/{type}',[USER\CustomTextController::class,'sentCustomText'])->name('sent.customtext');

   //bulk sender routes
   Route::post('/bulk-messages',                          [USER\BulkController::class,'store'])->name('bulk-message.store');
   Route::resource('/bulk-message',                       USER\BulkController::class);
   Route::get('bulk-message/template-with-message/create',[USER\BulkController::class,'templateWithMessage']);
   Route::get('/sent-bulk-with-template/{id}/{groupid}/{deviceid}', [USER\BulkController::class,'sendBulkToContacts']);
   Route::post('/sent-message-with-template',             [USER\BulkController::class,'sendMessageToContact']);
   //schedule message routes
   Route::resource('schedule-message',                    USER\ScheduleController::class);
   //schedule message routes
   Route::get('contact/export',                           [USER\ContactController::class,'export'])->name('contact.export');
   Route::resource('contact',                             USER\ContactController::class);
   Route::post('contact',                                 [USER\ContactController::class,'sendtemplateBulk'])->name('contact.send-template-bulk');
   Route::post('contact/store',                           [USER\ContactController::class,'store'])->name('contact.store');
   Route::post('contact-import',                          [USER\ContactController::class,'import'])->name('contact.import');

   
   //chatbot route
   Route::resource('chatbot',                             USER\ChatbotController::class);
   //chatbot agents (multi-agente)
   Route::get('chatbot-agents',                          [USER\ChatbotAgentController::class, 'index'])->name('chatbot.agents.index');
   Route::post('chatbot-agents',                         [USER\ChatbotAgentController::class, 'store'])->name('chatbot.agents.store');
   Route::put('chatbot-agents/{id}',                     [USER\ChatbotAgentController::class, 'update'])->name('chatbot.agents.update');
   Route::delete('chatbot-agents/{id}',                  [USER\ChatbotAgentController::class, 'destroy'])->name('chatbot.agents.destroy');
   //chatbot analytics
   Route::get('chatbot-stats',                           [USER\ChatbotStatsController::class, 'index'])->name('chatbot.stats.index');
   //chatbot widget
   Route::get('chatbot-widget',                          [USER\ChatbotWidgetController::class, 'index'])->name('chatbot.widget.index');
   //chatbot industry templates (presets pre-armados)
   Route::get('chatbot-industries',                      [USER\ChatbotIndustryController::class, 'index'])->name('chatbot.industries.index');
   Route::post('chatbot-industries/install',             [USER\ChatbotIndustryController::class, 'install'])->name('chatbot.industries.install');
   //device health + reconexión guiada
   Route::get('health',                                  [USER\DeviceHealthController::class, 'index'])->name('health.index');
   Route::get('health/statuses',                         [USER\DeviceHealthController::class, 'statuses'])->name('health.statuses');
   Route::post('health/reconnect/{id}',                  [USER\DeviceHealthController::class, 'reconnect'])->name('health.reconnect');
   Route::get('health/qr/{id}',                          [USER\DeviceHealthController::class, 'qr'])->name('health.qr');
   Route::post('health/alert-number/{id}',               [USER\DeviceHealthController::class, 'saveAlertNumber'])->name('health.alert');
   //AI config por device (desacoplar prompt LLM)
   Route::get('ai-config',                               [USER\DeviceAiConfigController::class, 'index'])->name('ai-config.index');
   Route::get('ai-config/{deviceId}',                    [USER\DeviceAiConfigController::class, 'edit'])->name('ai-config.edit');
   Route::post('ai-config/{deviceId}',                   [USER\DeviceAiConfigController::class, 'save'])->name('ai-config.save');
   //constructor visual de flujos chatbot
   Route::get('chatbot-flow',                            [USER\ChatbotFlowController::class, 'index'])->name('chatbot.flow.index');
   Route::post('chatbot-flow',                           [USER\ChatbotFlowController::class, 'save'])->name('chatbot.flow.save');
   //alert templates + rules (multi-canal)
   Route::get('alerts',                                  [USER\AlertTemplateController::class, 'index'])->name('alerts.index');
   Route::post('alerts/templates',                       [USER\AlertTemplateController::class, 'storeTemplate'])->name('alerts.templates.store');
   Route::put('alerts/templates/{id}',                   [USER\AlertTemplateController::class, 'updateTemplate'])->name('alerts.templates.update');
   Route::delete('alerts/templates/{id}',                [USER\AlertTemplateController::class, 'deleteTemplate'])->name('alerts.templates.delete');
   Route::post('alerts/rules',                           [USER\AlertTemplateController::class, 'storeRule'])->name('alerts.rules.store');
   Route::delete('alerts/rules/{id}',                    [USER\AlertTemplateController::class, 'deleteRule'])->name('alerts.rules.delete');
   Route::post('alerts/preview',                         [USER\AlertTemplateController::class, 'preview'])->name('alerts.preview');
   //leads crm
   Route::get('leads',                                   [USER\LeadsController::class, 'index'])->name('leads.index');
   Route::get('leads/export',                            [USER\LeadsController::class, 'export'])->name('leads.export');
   Route::post('leads/{id}/status',                      [USER\LeadsController::class, 'updateStatus'])->name('leads.update-status');
   //handoffs (bandeja de asesores)
   Route::get('handoffs',                                [USER\HandoffsController::class, 'index'])->name('handoffs.index');
   Route::get('handoffs/{id}',                           [USER\HandoffsController::class, 'show'])->name('handoffs.show');
   Route::post('handoffs/{id}/status',                   [USER\HandoffsController::class, 'updateStatus'])->name('handoffs.status');
   Route::post('handoffs/{id}/nps',                      [USER\HandoffsController::class, 'saveNps'])->name('handoffs.nps');
   //log report route
   Route::resource('logs',                                USER\LogController::class);
   //profile settings
   Route::get('profile',                                 [USER\ProfileController::class,'settings']);
   Route::put('profile/update/{type}',                   [USER\ProfileController::class,'update'])->name('profile.update');
   Route::get('auth-key',                                [USER\ProfileController::class,'authKey']);
   //help and support routes
   Route::resource('support',                            USER\SupportController::class);
   //manual / guide route
   Route::get('manual',                                  [USER\SupportController::class,'manual'])->name('manual.index');
   //subscription / plan route
   Route::resource('subscription',                       USER\SubscriptionController::class);
   Route::post('make-subscribe/{gateway_id}/{plan_id}',  [USER\SubscriptionController::class,'subscribe'])->name('make-payment');
   Route::get('/subscription/plan/{status}',             [USER\SubscriptionController::class,'status']);
   Route::get('/subscriptions/log',                      [USER\SubscriptionController::class,'log']);
   Route::get('/subscription-history',                   [USER\SubscriptionController::class,'log']);
   Route::resource('notifications',                      USER\NotificationController::class);
   Route::resource('group',                             USER\GroupController::class);

   Route::get('webhooks', [USER\WebhookController::class, 'index']);

});