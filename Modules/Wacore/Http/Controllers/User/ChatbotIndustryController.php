<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatbotIndustryController extends Controller
{
    public function index()
    {
        $industries = DB::table('chatbot_industry_templates')
            ->where('language', 'es')->where('is_active', true)
            ->orderBy('industry_name')->get();

        $devices = Device::where('user_id', Auth::id())->where('status', 1)->get();

        return view('wacore::user.chatbot.industries', compact('industries', 'devices'));
    }

    public function install(Request $request)
    {
        $data = $request->validate([
            'industry_key'   => 'required|string|max:50',
            'device_id'      => 'required|exists:devices,id',
            'business_name'  => 'required|string|max:100',
            'business_phone' => 'nullable|string|max:30',
            'business_addr'  => 'nullable|string|max:200',
            'business_demo'  => 'nullable|string|max:200',
            'overwrite'      => 'nullable|boolean',
        ]);

        $device = Device::where('id', $data['device_id'])->where('user_id', Auth::id())->firstOrFail();

        $industry = DB::table('chatbot_industry_templates')
            ->where('industry_key', $data['industry_key'])
            ->where('language', 'es')->first();

        abort_if(!$industry, 404, 'Plantilla no encontrada.');

        $rules = json_decode($industry->rules_json, true);
        if (!is_array($rules)) {
            return back()->with('error', 'Plantilla con datos inválidos.');
        }

        if (!empty($data['overwrite'])) {
            Reply::where('device_id', $device->id)->where('user_id', Auth::id())->delete();
        }

        $businessDemo = $data['business_demo'] ?? null;
        $businessAddr = $data['business_addr'] ?? null;
        $businessPhone = $data['business_phone'] ?? null;

        $vars = [
            '{NEGOCIO}'     => $data['business_name'],
            '{TELEFONO}'    => $businessPhone ?: '(número)',
            '{DIRECCION}'   => $businessAddr ?: '(dirección)',
            '{DEMO_URL}'    => $businessDemo ?: 'https://ejemplo.com',
            '{MAPA_URL}'    => $businessAddr
                ? 'https://maps.google.com/?q=' . urlencode($businessAddr)
                : 'https://maps.google.com',
            '{PROX_INICIO}' => now()->addWeek()->format('d/m/Y'),
        ];

        $count = 0;
        foreach ($rules as $r) {
            $reply = strtr($r['reply'] ?? '', $vars);
            $row = new Reply();
            $row->user_id          = Auth::id();
            $row->device_id        = $device->id;
            $row->keyword          = $r['keyword'] ?? '';
            $row->match_type       = $r['match_type'] ?? 'like';
            $row->reply            = $reply;
            $row->priority         = $r['priority'] ?? 5;
            $row->reply_type       = 'plain-text';
            $row->cooldown_minutes = $r['cooldown_minutes'] ?? 0;
            $row->only_once        = $r['only_once'] ?? 0;
            $row->trigger_handoff  = $r['trigger_handoff'] ?? 0;
            $row->schedule_enabled = 0;
            $row->save();
            $count++;
        }

        return back()->with('success', "Plantilla '$industry->industry_name' instalada: $count reglas.");
    }
}
