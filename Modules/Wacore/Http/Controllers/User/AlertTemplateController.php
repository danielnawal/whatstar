<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\AlertLog;
use App\Models\AlertRule;
use App\Models\AlertTemplate;
use App\Models\App as WhatsappApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertTemplateController extends Controller
{
    public function index()
    {
        $userId    = Auth::id();
        $templates = AlertTemplate::where('user_id', $userId)->orderBy('event_type')->get();
        $rules     = AlertRule::where('user_id', $userId)->orderByDesc('priority')->get();
        $apps      = WhatsappApp::where('user_id', $userId)->get(['id', 'title']);
        $recent    = AlertLog::where('user_id', $userId)->orderByDesc('id')->limit(50)->get();

        return view('wacore::user.alerts.index', compact('templates', 'rules', 'apps', 'recent'));
    }

    public function storeTemplate(Request $request)
    {
        $data = $request->validate([
            'app_id'        => 'nullable|exists:apps,id',
            'event_type'    => 'required|string|max:50',
            'channel'       => 'required|in:whatsapp,telegram,sms,email',
            'language'      => 'required|string|max:5',
            'template_text' => 'required|string|max:4000',
        ]);
        $data['user_id']   = Auth::id();
        $data['is_active'] = true;

        AlertTemplate::create($data);
        return back()->with('success', 'Plantilla guardada.');
    }

    public function updateTemplate(Request $request, $id)
    {
        $tpl = AlertTemplate::where('user_id', Auth::id())->findOrFail($id);
        $tpl->update($request->only(['event_type', 'channel', 'language', 'template_text', 'is_active', 'app_id']));
        return back()->with('success', 'Plantilla actualizada.');
    }

    public function deleteTemplate($id)
    {
        AlertTemplate::where('user_id', Auth::id())->where('id', $id)->delete();
        return back()->with('success', 'Plantilla eliminada.');
    }

    public function storeRule(Request $request)
    {
        $data = $request->validate([
            'app_id'         => 'nullable|exists:apps,id',
            'event_type'     => 'nullable|string|max:50',
            'unit_pattern'   => 'nullable|string|max:100',
            'mute_from'      => 'nullable|date_format:H:i',
            'mute_to'        => 'nullable|date_format:H:i',
            'dedupe_seconds' => 'nullable|integer|min:0|max:3600',
            'min_speed'      => 'nullable|integer|min:0|max:500',
            'block'          => 'nullable|boolean',
            'priority'       => 'nullable|integer|min:0|max:100',
        ]);
        $data['user_id']   = Auth::id();
        $data['is_active'] = true;
        $data['block']     = (bool) ($data['block'] ?? false);

        AlertRule::create($data);
        return back()->with('success', 'Regla guardada.');
    }

    public function deleteRule($id)
    {
        AlertRule::where('user_id', Auth::id())->where('id', $id)->delete();
        return back()->with('success', 'Regla eliminada.');
    }

    public function preview(Request $request)
    {
        $tpl = $request->input('template', AlertTemplate::DEFAULT_TEMPLATE);
        $payload = [
            'unit'    => 'Camión 01',
            'event'   => 'speeding',
            'speed'   => 120,
            'lat'     => 4.6097,
            'lng'     => -74.0817,
            'address' => 'Calle 80 #45-23, Bogotá',
            'time'    => now()->format('Y-m-d H:i:s'),
        ];
        $rendered = (new \App\Services\AlertDispatcherService())->renderTemplate($tpl, $payload);
        return response()->json(['preview' => $rendered]);
    }
}
