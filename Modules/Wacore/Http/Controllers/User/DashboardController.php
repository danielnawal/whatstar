<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Smstransaction;
use App\Models\Schedulemessage;
use App\Models\Contact;
use App\Models\Template;
use App\Models\ChatbotLead;
use App\Models\ChatbotHandoff;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Auth;
use Session;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->will_expire != null) {
            $nextDate= Carbon::now()->addDays(7)->format('Y-m-d');
            if (Auth::user()->will_expire <= now()) {
                Session::flash('saas_error', __('Your subscription was expired at '.Carbon::parse(Auth::user()->will_expire)->diffForHumans().' please renew the subscription'));
            }

            elseif(Auth::user()->will_expire <= $nextDate){
                Session::flash('saas_error', __('Your subscription is ending in '.Carbon::parse(Auth::user()->will_expire)->diffForHumans()));
            }
        }
       
        return view('wacore::user.dashboard');
    }

    public function dashboardData()
    {
        $data['devicesCount'] = Device::where('user_id',Auth::id())->count();
        $data['messagesCount'] = Smstransaction::where('user_id',Auth::id())->count();
        $data['contactCount'] = Contact::where('user_id',Auth::id())->count();
        $data['scheduleCount'] = Schedulemessage::where('status','pending')->where('user_id',Auth::id())->count();
        
        $data['devices'] = Device::where('user_id',Auth::id())->withCount('smstransaction')->orderBy('status','DESC')->latest()->get()->map(function($rq){
                $map['uuid']= $rq->uuid;
                $map['name']= $rq->name;
                $map['status']= $rq->status;
                $map['phone']= $rq->phone;
                $map['smstransaction_count']= $rq->smstransaction_count;
                return $map;
        });
        $data['messagesStatics'] = $this->getMessagesTransaction(7);
        $data['typeStatics'] = $this->messagesStatics(7);
        $data['chatbotStatics'] = $this->getChatbotTransaction(7);

        $data['premium'] = $this->premiumAnalytics();

        return response()->json($data);

    }

    /**
     * Métricas premium: comparativa mes vs mes, distribución horaria,
     * top templates del bot, nuevos contactos por día y tasa de respuesta del bot.
     * Todo agregado en una sola llamada para no abrir N queries desde el front.
     */
    public function premiumAnalytics()
    {
        $userId = Auth::id();
        $thisMonthStart = Carbon::now()->startOfMonth();
        $prevMonthStart = Carbon::now()->subMonthNoOverflow()->startOfMonth();
        $prevMonthEnd   = Carbon::now()->subMonthNoOverflow()->endOfMonth();
        $last30Start    = Carbon::now()->subDays(30)->startOfDay();

        // 1. Mes actual vs mes anterior (mismo nº de días transcurridos para comparativa justa)
        $thisMonthMsgs = Smstransaction::where('user_id', $userId)
            ->where('created_at', '>=', $thisMonthStart)
            ->count();
        $prevMonthMsgs = Smstransaction::where('user_id', $userId)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->count();
        $delta = $prevMonthMsgs > 0
            ? round((($thisMonthMsgs - $prevMonthMsgs) / $prevMonthMsgs) * 100, 1)
            : ($thisMonthMsgs > 0 ? 100 : 0);

        // 2. Distribución horaria (24 buckets) últimos 30 días
        $hourly = Smstransaction::where('user_id', $userId)
            ->where('created_at', '>=', $last30Start)
            ->selectRaw('HOUR(created_at) AS h, COUNT(*) AS c')
            ->groupBy('h')
            ->pluck('c', 'h');
        $byHour = [];
        for ($h = 0; $h < 24; $h++) {
            $byHour[] = ['hour' => $h, 'count' => (int) ($hourly[$h] ?? 0)];
        }

        // 3. Top 5 templates más usadas por el chatbot últimos 30 días
        $topTemplates = Smstransaction::where('smstransactions.user_id', $userId)
            ->where('smstransactions.type', 'chatbot')
            ->whereNotNull('smstransactions.template_id')
            ->where('smstransactions.created_at', '>=', $last30Start)
            ->join('templates', 'templates.id', '=', 'smstransactions.template_id')
            ->selectRaw('templates.title AS title, COUNT(*) AS uses')
            ->groupBy('templates.id', 'templates.title')
            ->orderByDesc('uses')
            ->limit(5)
            ->get();

        // 4. Nuevos contactos por día últimos 30 días
        $contactsByDay = Contact::where('user_id', $userId)
            ->where('created_at', '>=', $last30Start)
            ->selectRaw('DATE(created_at) AS date, COUNT(*) AS c')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 5. Tasa de respuesta del bot (chatbot msgs / total msgs, últimos 30 días)
        $totalLast30  = Smstransaction::where('user_id', $userId)
            ->where('created_at', '>=', $last30Start)
            ->count();
        $botLast30    = Smstransaction::where('user_id', $userId)
            ->where('type', 'chatbot')
            ->where('created_at', '>=', $last30Start)
            ->count();
        $botRate = $totalLast30 > 0 ? round(($botLast30 / $totalLast30) * 100, 1) : 0;

        // 6. Leads y handoffs pendientes (CRM mini)
        $leadsNew = ChatbotLead::where('user_id', $userId)->where('status', 'new')->count();
        $leadsTotal30 = ChatbotLead::where('user_id', $userId)
            ->where('created_at', '>=', $last30Start)->count();
        $handoffsPending = ChatbotHandoff::where('user_id', $userId)
            ->where('status', 'pending')->count();
        $recentLeads = ChatbotLead::where('user_id', $userId)
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id','contact','contact_name','interest','status','created_at']);

        return [
            'thisMonthMsgs'   => $thisMonthMsgs,
            'prevMonthMsgs'   => $prevMonthMsgs,
            'monthDeltaPct'   => $delta,
            'byHour'          => $byHour,
            'topTemplates'    => $topTemplates,
            'contactsByDay'   => $contactsByDay,
            'botResponseRate' => $botRate,
            'totalLast30'     => $totalLast30,
            'botLast30'       => $botLast30,
            'leadsNew'        => $leadsNew,
            'leadsTotal30'    => $leadsTotal30,
            'handoffsPending' => $handoffsPending,
            'recentLeads'     => $recentLeads,
        ];
    }

    public function getMessagesTransaction($days)
    {
       $statics= Smstransaction::query()->where('user_id',Auth::id())
                ->whereDate('created_at', '>', Carbon::now()->subDays($days))
                ->orderBy('id', 'asc')
                ->selectRaw('date(created_at) date, count(*) smstransactions')
                ->groupBy('date')
                ->get();

        return $statics;
                
    }

    public function getChatbotTransaction($days)
    {
        $statics= Smstransaction::query()
                ->where('user_id',Auth::id())
                ->where('type','chatbot')
                ->whereDate('created_at', '>', Carbon::now()->subDays($days))
                ->orderBy('id', 'asc')
                ->selectRaw('date(created_at) date, count(*) smstransactions')
                ->groupBy('date')
                ->get();

        return $statics;
    }

    public function messagesStatics($days)
    {
        $statics= Smstransaction::query()->where('user_id',Auth::id())
                ->whereDate('created_at', '>', Carbon::now()->subDays($days))
                ->orderBy('id', 'asc')
                ->selectRaw('type type, count(*) smstransactions')
                ->groupBy('type')
                ->get();

        return $statics;
    }
}
