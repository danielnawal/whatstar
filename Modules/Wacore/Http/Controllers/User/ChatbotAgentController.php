<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatbotAgent;
use App\Models\Device;
use Auth;
use Illuminate\Http\Request;

class ChatbotAgentController extends Controller
{
    public function index()
    {
        $devices = Device::where('user_id', Auth::id())->where('status', 1)->get();
        $agents  = ChatbotAgent::where('user_id', Auth::id())
            ->with('device')
            ->orderBy('device_id')
            ->orderBy('name')
            ->get();

        return view('wacore::user.chatbot.agents', compact('devices', 'agents'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'device_id' => 'required|integer',
            'name'      => 'required|string|max:100',
            'phone'     => 'required|string|max:30',
            'role'      => 'nullable|string|max:50',
            'region'    => 'nullable|string|max:50',
        ]);

        Device::where('user_id', Auth::id())->findOrFail($request->device_id);

        ChatbotAgent::create([
            'device_id' => $request->device_id,
            'user_id'   => Auth::id(),
            'name'      => $request->name,
            'phone'     => preg_replace('/[^0-9]/', '', $request->phone),
            'role'      => $request->role ?: 'sales',
            'region'    => $request->region ?: null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Agente agregado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $agent = ChatbotAgent::where('user_id', Auth::id())->findOrFail($id);

        $request->validate([
            'name'   => 'required|string|max:100',
            'phone'  => 'required|string|max:30',
            'role'   => 'nullable|string|max:50',
            'region' => 'nullable|string|max:50',
        ]);

        $agent->update([
            'name'      => $request->name,
            'phone'     => preg_replace('/[^0-9]/', '', $request->phone),
            'role'      => $request->role ?: 'sales',
            'region'    => $request->region ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Agente actualizado.');
    }

    public function destroy($id)
    {
        ChatbotAgent::where('user_id', Auth::id())->findOrFail($id)->delete();
        return back()->with('success', 'Agente eliminado.');
    }
}
