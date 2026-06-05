<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatbotHandoff;
use App\Models\ChatbotMessage;
use App\Models\ChatbotSession;
use App\Models\Device;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class HandoffsController extends Controller
{
    public function index(Request $request)
    {
        $deviceIds = Device::where('user_id', Auth::id())->pluck('id');

        $query = ChatbotHandoff::whereIn('device_id', $deviceIds)->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $q = '%' . $request->search . '%';
            $query->where(function ($w) use ($q) {
                $w->where('contact', 'like', $q)
                  ->orWhere('contact_name', 'like', $q)
                  ->orWhere('last_message', 'like', $q);
            });
        }

        $handoffs = $query->paginate(25)->withQueryString();

        $totals = [
            'total'    => ChatbotHandoff::whereIn('device_id', $deviceIds)->count(),
            'pending'  => ChatbotHandoff::whereIn('device_id', $deviceIds)->where('status', 'pending')->count(),
            'active'   => ChatbotHandoff::whereIn('device_id', $deviceIds)->where('status', 'active')->count(),
            'closed'   => ChatbotHandoff::whereIn('device_id', $deviceIds)->where('status', 'closed')->count(),
        ];

        return view('wacore::user.handoffs.index', compact('handoffs', 'totals'));
    }

    public function show($id)
    {
        $deviceIds = Device::where('user_id', Auth::id())->pluck('id');
        $handoff = ChatbotHandoff::whereIn('device_id', $deviceIds)->findOrFail($id);

        $messages = ChatbotMessage::where('device_id', $handoff->device_id)
            ->where('contact', $handoff->contact)
            ->orderBy('created_at', 'asc')
            ->limit(50)
            ->get();

        return view('wacore::user.handoffs.show', compact('handoff', 'messages'));
    }

    public function updateStatus(Request $request, $id)
    {
        $deviceIds = Device::where('user_id', Auth::id())->pluck('id');
        $handoff = ChatbotHandoff::whereIn('device_id', $deviceIds)->findOrFail($id);

        $request->validate(['status' => 'required|in:pending,active,closed']);

        $wasOpen = $handoff->status !== 'closed';
        $handoff->status = $request->status;
        if ($request->status === 'closed' && !$handoff->resolved_at) {
            $handoff->resolved_at = Carbon::now();
        }
        $handoff->save();

        // Disparar encuesta NPS al cliente cuando se cierra por primera vez
        if ($request->status === 'closed' && $wasOpen) {
            $this->triggerNpsQuestion($handoff);
        }

        return response()->json(['ok' => true, 'status' => $handoff->status]);
    }

    public function saveNps(Request $request, $id)
    {
        $deviceIds = Device::where('user_id', Auth::id())->pluck('id');
        $handoff = ChatbotHandoff::whereIn('device_id', $deviceIds)->findOrFail($id);

        $request->validate([
            'nps'         => 'required|integer|min:1|max:10',
            'nps_comment' => 'nullable|string|max:500',
        ]);

        $handoff->nps         = $request->nps;
        $handoff->nps_comment = $request->nps_comment;
        $handoff->save();

        return response()->json(['ok' => true]);
    }

    private function triggerNpsQuestion(ChatbotHandoff $handoff): void
    {
        $session = ChatbotSession::where('device_id', $handoff->device_id)
            ->where('contact', $handoff->contact)
            ->first();

        $lang = $session?->locked_language ?? 'es';

        $question = match ($lang) {
            'en' => "⭐ *How would you rate the support you received?*\n\nReply with a number from *1 to 5*:\n\n1 = Very bad\n2 = Bad\n3 = Average\n4 = Good\n5 = Excellent",
            'pt' => "⭐ *Como você avalia o atendimento recebido?*\n\nResponda com um número de *1 a 5*:\n\n1 = Péssimo\n2 = Ruim\n3 = Regular\n4 = Bom\n5 = Ótimo",
            default => "⭐ *¿Cómo calificarías la atención que recibiste?*\n\nResponde con un número del *1 al 5*:\n\n1 = Muy malo\n2 = Malo\n3 = Regular\n4 = Bueno\n5 = Excelente",
        };

        try {
            Http::timeout(8)->post(
                rtrim(env('WA_SERVER_URL', 'http://127.0.0.1:8000'), '/') . '/chats/send?id=device_' . $handoff->device_id,
                ['receiver' => $handoff->contact, 'message' => ['text' => $question]]
            );
        } catch (\Throwable $e) {
            // NPS es opcional — no interrumpir el cierre del handoff
        }

        if ($session) {
            $session->nps_pending = 1;
            $session->save();
        }
    }
}
