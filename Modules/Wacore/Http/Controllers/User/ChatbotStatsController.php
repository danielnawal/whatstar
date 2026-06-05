<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatbotHandoff;
use App\Models\ChatbotLead;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use App\Models\Device;
use App\Models\Reply;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

/**
 * Dashboard analytics multi-tenant del chatbot APPOGIO.
 * Solo muestra métricas de los devices del usuario logueado.
 */
class ChatbotStatsController extends Controller
{
    public function index(Request $request)
    {
        $userId = Auth::id();

        // Devices del usuario (multi-tenant: solo los suyos)
        $deviceIds = Device::where('user_id', $userId)->pluck('id')->toArray();

        if (empty($deviceIds)) {
            return view('wacore::user.chatbot.stats', [
                'empty'      => true,
                'data'       => null,
                'deviceIds'  => [],
            ]);
        }

        $data = $this->buildStats($deviceIds);

        return view('wacore::user.chatbot.stats', [
            'empty'     => false,
            'data'      => $data,
            'deviceIds' => $deviceIds,
        ]);
    }

    private function buildStats(array $deviceIds): array
    {
        $since30 = Carbon::now()->subDays(30);
        $since7  = Carbon::now()->subDays(7);

        // Mensajes totales por día (últimos 30d)
        $msgsByDay = ChatbotMessage::whereIn('device_id', $deviceIds)
            ->where('created_at', '>=', $since30)
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('count(*) as c'),
                     DB::raw("sum(case when role='user' then 1 else 0 end) as inbound"),
                     DB::raw("sum(case when role='assistant' then 1 else 0 end) as outbound"))
            ->groupBy('d')->orderBy('d')->get();

        $totalInbound  = ChatbotMessage::whereIn('device_id', $deviceIds)->where('role','user')->where('created_at','>=',$since30)->count();
        $totalOutbound = ChatbotMessage::whereIn('device_id', $deviceIds)->where('role','assistant')->where('created_at','>=',$since30)->count();

        // Distribución de intents (últimos 30d, solo mensajes outbound con intent)
        $intentDist = ChatbotMessage::whereIn('device_id', $deviceIds)
            ->where('role', 'assistant')
            ->whereNotNull('intent')
            ->where('created_at', '>=', $since30)
            ->select('intent', DB::raw('count(*) as c'))
            ->groupBy('intent')->orderByDesc('c')->limit(15)->get();

        // Top reglas más disparadas
        $topRules = ChatbotMessage::whereIn('device_id', $deviceIds)
            ->whereNotNull('matched_reply_id')
            ->where('created_at', '>=', $since30)
            ->select('matched_reply_id', DB::raw('count(*) as c'))
            ->groupBy('matched_reply_id')->orderByDesc('c')->limit(10)->get();

        // Hidratamos con keyword/snippet para que sea legible
        $ruleIds  = $topRules->pluck('matched_reply_id')->toArray();
        $rulesMap = Reply::whereIn('id', $ruleIds)->get()->keyBy('id');
        $topRules = $topRules->map(function($r) use ($rulesMap) {
            $rule = $rulesMap[$r->matched_reply_id] ?? null;
            return [
                'rule_id'  => $r->matched_reply_id,
                'count'    => (int) $r->c,
                'keyword'  => $rule ? mb_substr($rule->keyword ?? '(catch-all)', 0, 60) : '(eliminada)',
                'snippet'  => $rule ? mb_substr(strip_tags($rule->reply ?? ''), 0, 70) : '',
            ];
        });

        // Idiomas
        $langs = ChatbotSession::whereIn('device_id', $deviceIds)
            ->whereNotNull('locked_language')
            ->select('locked_language', DB::raw('count(*) as c'))
            ->groupBy('locked_language')->get();

        // Leads
        $leadsTotal     = ChatbotLead::whereIn('device_id', $deviceIds)->where('created_at','>=',$since30)->count();
        $leadsByStatus  = ChatbotLead::whereIn('device_id', $deviceIds)->where('created_at','>=',$since30)
            ->select('status', DB::raw('count(*) as c'))->groupBy('status')->get();
        $leadsLast7     = ChatbotLead::whereIn('device_id', $deviceIds)->where('created_at','>=',$since7)->count();

        // Handoffs
        $handoffsTotal  = ChatbotHandoff::whereIn('device_id', $deviceIds)->where('created_at','>=',$since30)->count();
        $handoffsPending= ChatbotHandoff::whereIn('device_id', $deviceIds)->where('status','pending')->count();
        $handoffsClosed = ChatbotHandoff::whereIn('device_id', $deviceIds)->where('status','closed')->where('resolved_at','>=',$since30)->count();

        // Tiempo promedio resolución handoff (horas)
        $avgResolution = ChatbotHandoff::whereIn('device_id', $deviceIds)
            ->where('status','closed')
            ->whereNotNull('resolved_at')
            ->where('resolved_at','>=',$since30)
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_min'))
            ->value('avg_min');

        // NPS
        $npsRows = ChatbotHandoff::whereIn('device_id', $deviceIds)
            ->whereNotNull('nps')
            ->where('updated_at', '>=', $since30)
            ->select('nps', DB::raw('count(*) as c'))
            ->groupBy('nps')->orderBy('nps')->get();
        $npsAvg = ChatbotHandoff::whereIn('device_id', $deviceIds)
            ->whereNotNull('nps')->where('updated_at','>=',$since30)
            ->avg('nps');
        $npsTotal = $npsRows->sum('c');

        // % de mensajes que pasaron por LLM (intent presente) vs keyword puro vs catch-all
        $totalAssistant = max(1, $totalOutbound);
        $byLLM = ChatbotMessage::whereIn('device_id', $deviceIds)->where('role','assistant')
            ->where('created_at','>=',$since30)->whereNotNull('intent')->count();
        $byKeyword = ChatbotMessage::whereIn('device_id', $deviceIds)->where('role','assistant')
            ->where('created_at','>=',$since30)->whereNull('intent')->whereNotNull('matched_reply_id')->count();

        // A/B testing: conversión por variante
        // "Conversión" = tras enviar la variante, en las próximas 24h hubo:
        //   intent=wants_advisor / checkout / NPS≥4
        $abReport = $this->computeAbReport($deviceIds, $since30);

        return [
            'period_days'      => 30,
            'msgs_by_day'      => $msgsByDay,
            'total_inbound'    => $totalInbound,
            'total_outbound'   => $totalOutbound,
            'intent_dist'      => $intentDist,
            'top_rules'        => $topRules,
            'langs'            => $langs,
            'leads_total'      => $leadsTotal,
            'leads_by_status'  => $leadsByStatus,
            'leads_last7'      => $leadsLast7,
            'handoffs_total'   => $handoffsTotal,
            'handoffs_pending' => $handoffsPending,
            'handoffs_closed'  => $handoffsClosed,
            'avg_resolution_min' => $avgResolution ? round($avgResolution, 1) : null,
            'nps_rows'         => $npsRows,
            'nps_avg'          => $npsAvg ? round($npsAvg, 2) : null,
            'nps_total'        => $npsTotal,
            'pct_by_llm'       => round($byLLM / $totalAssistant * 100, 1),
            'pct_by_keyword'   => round($byKeyword / $totalAssistant * 100, 1),
            'ab_report'        => $abReport,
        ];
    }

    /**
     * Reporte A/B: para cada regla con variantes, calcula tasa de conversión por variante.
     *
     * Conversión = en los 1440 min siguientes al envío de la variante, el cliente:
     *   - disparó intent in (wants_advisor, checkout, schedule_demo)
     *   - O dejó NPS ≥ 4
     *
     * @return array<int, array{rule_id:int, total_uses:int, variants:array}>
     */
    private function computeAbReport(array $deviceIds, $since): array
    {
        // Buscar mensajes con variant_index registrado (regla tuvo variantes)
        $rows = ChatbotMessage::whereIn('device_id', $deviceIds)
            ->whereNotNull('matched_reply_id')
            ->whereNotNull('variant_index')
            ->where('created_at', '>=', $since)
            ->select('id', 'session_id', 'matched_reply_id', 'variant_index', 'created_at')
            ->orderBy('id')
            ->limit(5000)
            ->get();

        if ($rows->isEmpty()) return [];

        $byRule = [];
        foreach ($rows as $r) {
            $rId = $r->matched_reply_id;
            $vIdx = $r->variant_index;
            if (!isset($byRule[$rId])) {
                $byRule[$rId] = ['total' => 0, 'variants' => []];
            }
            if (!isset($byRule[$rId]['variants'][$vIdx])) {
                $byRule[$rId]['variants'][$vIdx] = ['uses' => 0, 'conversions' => 0];
            }
            $byRule[$rId]['total']++;
            $byRule[$rId]['variants'][$vIdx]['uses']++;

            // Buscar conversión en las próximas 24h en la misma sesión
            $conversionEvents = ChatbotMessage::where('session_id', $r->session_id)
                ->where('id', '>', $r->id)
                ->where('created_at', '<=', \Carbon\Carbon::parse($r->created_at)->addHours(24))
                ->whereIn('intent', ['wants_advisor', 'checkout', 'schedule_demo'])
                ->exists();
            if ($conversionEvents) {
                $byRule[$rId]['variants'][$vIdx]['conversions']++;
            }
        }

        // Formatear para vista
        $result = [];
        foreach ($byRule as $ruleId => $data) {
            $variants = [];
            foreach ($data['variants'] as $idx => $v) {
                $variants[] = [
                    'index'         => $idx,
                    'uses'          => $v['uses'],
                    'conversions'   => $v['conversions'],
                    'conversion_pct'=> $v['uses'] > 0 ? round($v['conversions'] / $v['uses'] * 100, 1) : 0,
                ];
            }
            usort($variants, fn($a, $b) => $b['conversion_pct'] <=> $a['conversion_pct']);
            $result[] = [
                'rule_id'    => $ruleId,
                'total_uses' => $data['total'],
                'variants'   => $variants,
            ];
        }

        // Ordenar por uso descendente, top 10 reglas
        usort($result, fn($a, $b) => $b['total_uses'] <=> $a['total_uses']);
        return array_slice($result, 0, 10);
    }
}
