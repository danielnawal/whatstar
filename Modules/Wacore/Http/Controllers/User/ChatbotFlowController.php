<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Reply;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotFlowController extends Controller
{
    public function index(Request $request)
    {
        $deviceId = $request->input('device_id');
        $devices  = Device::where('user_id', Auth::id())->get(['id', 'name']);

        if (!$deviceId && $devices->isNotEmpty()) {
            $deviceId = $devices->first()->id;
        }

        $rules = $deviceId
            ? Reply::where('user_id', Auth::id())->where('device_id', $deviceId)
                ->orderByDesc('priority')->get()
            : collect();

        $flowData = $this->buildFlowData($rules);

        return view('wacore::user.chatbot.flow', compact('devices', 'deviceId', 'rules', 'flowData'));
    }

    public function save(Request $request)
    {
        $data = $request->validate([
            'device_id' => 'required|exists:devices,id',
            'nodes'     => 'required|array',
        ]);

        $device = Device::where('id', $data['device_id'])->where('user_id', Auth::id())->firstOrFail();

        $existingIds = [];
        foreach ($data['nodes'] as $node) {
            $payload = [
                'user_id'         => Auth::id(),
                'device_id'       => $device->id,
                'keyword'         => $node['keyword'] ?? '',
                'match_type'      => $node['match_type'] ?? 'like',
                'reply'           => $node['reply'] ?? '',
                'priority'        => (int) ($node['priority'] ?? 5),
                'parent_reply_id' => $node['parent_reply_id'] ?? null,
                'reply_type'      => $node['reply_type'] ?? 'plain-text',
            ];

            if (!empty($node['id']) && is_numeric($node['id'])) {
                $reply = Reply::where('id', $node['id'])->where('user_id', Auth::id())->first();
                if ($reply) {
                    foreach ($payload as $k => $v) $reply->$k = $v;
                    $reply->save();
                    $existingIds[] = $reply->id;
                    continue;
                }
            }
            $r = new Reply();
            foreach ($payload as $k => $v) $r->$k = $v;
            $r->save();
            $existingIds[] = $r->id;
        }

        return response()->json(['success' => true, 'ids' => $existingIds]);
    }

    private function buildFlowData($rules): array
    {
        $nodes = [];
        $edges = [];

        foreach ($rules as $r) {
            $nodes[] = [
                'id'    => (string) $r->id,
                'data'  => [
                    'keyword'    => $r->keyword,
                    'match_type' => $r->match_type,
                    'reply'      => mb_substr($r->reply ?? '', 0, 200),
                    'priority'   => $r->priority,
                ],
                'parent_reply_id' => $r->parent_reply_id,
            ];
            if ($r->parent_reply_id) {
                $edges[] = ['source' => (string) $r->parent_reply_id, 'target' => (string) $r->id];
            }
        }

        return ['nodes' => $nodes, 'edges' => $edges];
    }
}
