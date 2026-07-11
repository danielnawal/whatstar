<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceAiConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DeviceAiConfigController extends Controller
{
    public function index()
    {
        $devices = Device::where('user_id', Auth::id())
            ->leftJoin('device_ai_configs as c', 'c.device_id', '=', 'devices.id')
            ->select('devices.*', 'c.business_name as ai_business', 'c.ai_enabled', 'c.industry')
            ->get();

        return view('wacore::user.ai-config.index', compact('devices'));
    }

    public function edit($deviceId)
    {
        $device = Device::where('id', $deviceId)->where('user_id', Auth::id())->firstOrFail();
        $config = DeviceAiConfig::firstOrNew(['device_id' => $device->id]);
        return view('wacore::user.ai-config.edit', compact('device', 'config'));
    }

    public function save(Request $request, $deviceId)
    {
        $device = Device::where('id', $deviceId)->where('user_id', Auth::id())->firstOrFail();

        $data = $request->validate([
            'business_name'         => 'nullable|string|max:100',
            'industry'              => 'nullable|string|max:50',
            'business_context'      => 'nullable|string|max:4000',
            'custom_system_prompt'  => 'nullable|string|max:8000',
            'ai_enabled'            => 'nullable|boolean',
            'use_default_prompt'    => 'nullable|boolean',
            'banned_terms'          => 'nullable|string|max:500',
        ]);

        $banned = array_filter(array_map('trim', explode(',', $data['banned_terms'] ?? '')));

        DeviceAiConfig::updateOrCreate(
            ['device_id' => $device->id],
            [
                'business_name'        => $data['business_name'] ?? null,
                'industry'             => $data['industry'] ?? null,
                'business_context'     => $data['business_context'] ?? null,
                'custom_system_prompt' => $data['custom_system_prompt'] ?? null,
                'ai_enabled'           => (bool) ($data['ai_enabled'] ?? false),
                'use_default_prompt'   => (bool) ($data['use_default_prompt'] ?? false),
                'banned_terms'         => $banned ?: null,
            ]
        );

        return back()->with('success', 'Configuración IA guardada para device '.$device->name);
    }
}
