<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/



Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// ===== INTEGRACIÓN APPOGIO ===================================================
// Rutas públicas (sin auth ni CSRF) para conectar WhatsApp y recibir alertas
Route::prefix('appogio')->group(function () {
    // Página de conexión que el cliente abre desde Appogio
    Route::get('connect/{uuid}', [App\Http\Controllers\AppogioController::class, 'connect'])->name('appogio.connect');
    // AJAX: solicitar código QR
    Route::post('get-qr/{uuid}', [App\Http\Controllers\AppogioController::class, 'publicGetQr'])->name('appogio.getqr');
    // AJAX: verificar si el dispositivo ya conectó
    Route::post('check-session/{uuid}', [App\Http\Controllers\AppogioController::class, 'publicCheckSession'])->name('appogio.check');
    // AJAX: generar / regenerar outkey
    Route::post('generate-token/{uuid}', [App\Http\Controllers\AppogioController::class, 'generateToken'])->name('appogio.token');
    // Webhook receptor de alertas (GET y POST para máxima compatibilidad con Appogio)
    Route::match(['get', 'post'], 'alert', [App\Http\Controllers\AppogioController::class, 'alert'])->name('appogio.alert');

    // SSO: login automático desde el launcher de Appogio (sin usuario/contraseña)
    Route::match(['get', 'post'], 'sso', [App\Http\Controllers\AppogioController::class, 'sso'])->name('appogio.sso');
});
// ===========================================================================

// Rutas autenticadas para gestión del token SSO
Route::middleware(['auth', 'verified'])->group(function () {
    // Usuario genera su propio SSO token desde su perfil
    Route::post('appogio/sso/generate', [App\Http\Controllers\AppogioController::class, 'generateSsoToken'])->name('appogio.sso.generate');
});

// ===========================================================================

Route::group(['prefix' => 'cron', 'as' => 'cron.', 'middleware' => ['cron.auth']], function (){

    Route::get('/execute-schedule', [App\Http\Controllers\CronController::class, 'ExecuteSchedule']);

    Route::get('/execute-webhook', [App\Http\Controllers\CronController::class, 'ExecuteWebhook']);

    Route::get('/notify-to-user', [App\Http\Controllers\CronController::class, 'notifyToUser']);
    Route::get('/remove-junk-device', [App\Http\Controllers\CronController::class, 'removeJunkDevice']);

});

Route::get('/local/{lang}',function($lang){
     \Session::put('locale', $lang);
     \App::setlocale($lang);
     
     return back();
});

//**======================== Payment Gateway Route Group for merchant ====================**//
Route::group(['middleware' => ['auth', 'web']], function () {
    Route::get('/payment/paypal', '\App\Gateway\Paypal@status');
    Route::post('/stripe/payment', '\App\Gateway\Stripe@status')->name('stripe.payment');
    Route::get('/stripe', '\App\Gateway\Stripe@view')->name('stripe.view');
    Route::get('/stripe-pay/success', '\App\Gateway\Stripe@status')->name('stripe.success');
    Route::get('/stripe-pay/fail', '\App\Gateway\Stripe@fail')->name('stripe.fail');


    Route::get('/payment/mollie', '\App\Gateway\Mollie@status');
    Route::post('/payment/paystack', '\App\Gateway\Paystack@status')->name('paystack.status');
    Route::get('/paystack', '\App\Gateway\Paystack@view')->name('paystack.view');
    Route::get('/payment/mercado', '\App\Gateway\Mercado@status')->name('mercadopago.status');
    Route::get('/razorpay/payment', '\App\Gateway\Razorpay@view')->name('razorpay.view');
    Route::post('/razorpay/status', '\App\Gateway\Razorpay@status');
    Route::get('/payment/flutterwave', '\App\Gateway\Flutterwave@status');
    Route::get('/payment/thawani', '\App\Gateway\Thawani@status');
    Route::get('/payment/instamojo', '\App\Gateway\Instamojo@status');
    Route::get('/payment/toyyibpay', '\App\Gateway\Toyyibpay@status');
    Route::get('/manual/payment', '\App\Gateway\CustomGateway@status');
    Route::get('payu/payment', '\App\Gateway\Payu@view')->name('payu.view');
    Route::post('payu/status', '\App\Gateway\Payu@status')->name('payu.status');
});
