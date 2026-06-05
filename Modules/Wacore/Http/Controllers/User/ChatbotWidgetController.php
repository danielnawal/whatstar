<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Auth;

class ChatbotWidgetController extends Controller
{
    public function index()
    {
        if (getUserPlanData('chatbot') == false) {
            return redirect()->route('user.subscription.index')
                ->with('saas_error', __('You need an active plan to use the Web Widget.'));
        }

        $devices = Device::where('user_id', Auth::id())->where('status', 1)->get();
        $baseUrl = rtrim(config('app.url'), '/');
        return view('wacore::user.chatbot.widget', compact('devices', 'baseUrl'));
    }
}
