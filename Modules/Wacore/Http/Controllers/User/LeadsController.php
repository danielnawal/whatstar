<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\ChatbotLead;
use App\Models\Device;
use Auth;
use Illuminate\Http\Request;

class LeadsController extends Controller
{
    public function index(Request $request)
    {
        if (getUserPlanData('chatbot') == false) {
            return redirect()->route('user.subscription.index')
                ->with('saas_error', __('You need an active plan to access the Leads CRM.'));
        }

        $deviceIds = Device::where('user_id', Auth::id())->pluck('id');

        $query = ChatbotLead::whereIn('device_id', $deviceIds)->with('device')->latest();

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->search) {
            $q = '%' . $request->search . '%';
            $query->where(function ($w) use ($q) {
                $w->where('contact_name', 'like', $q)
                  ->orWhere('contact', 'like', $q)
                  ->orWhere('contact_email', 'like', $q)
                  ->orWhere('interest', 'like', $q);
            });
        }

        $leads  = $query->paginate(25)->withQueryString();
        $totals = [
            'total'    => ChatbotLead::whereIn('device_id', $deviceIds)->count(),
            'new'      => ChatbotLead::whereIn('device_id', $deviceIds)->where('status', 'new')->count(),
            'qualified'=> ChatbotLead::whereIn('device_id', $deviceIds)->where('status', 'qualified')->count(),
            'synced'   => ChatbotLead::whereIn('device_id', $deviceIds)->where('synced_to_crm', 1)->count(),
        ];

        return view('wacore::user.leads.index', compact('leads', 'totals'));
    }

    public function updateStatus(Request $request, $id)
    {
        $deviceIds = Device::where('user_id', Auth::id())->pluck('id');
        $lead = ChatbotLead::whereIn('device_id', $deviceIds)->findOrFail($id);

        $request->validate(['status' => 'required|in:new,contacted,qualified,lost,won']);
        $lead->status = $request->status;
        $lead->save();

        return response()->json(['message' => __('Status updated'), 'status' => $lead->status]);
    }

    public function export(Request $request)
    {
        if (getUserPlanData('chatbot') == false) {
            return redirect()->route('user.subscription.index');
        }

        $deviceIds = Device::where('user_id', Auth::id())->pluck('id');
        $leads = ChatbotLead::whereIn('device_id', $deviceIds)->latest()->get();

        $filename = 'leads_' . date('Ymd_His') . '.csv';
        $headers  = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($leads) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Contact', 'Name', 'Email', 'Interest', 'Score', 'Status', 'Channel', 'Date']);
            foreach ($leads as $l) {
                $data    = json_decode($l->full_data ?? '{}', true);
                $channel = $data['channel'] ?? 'whatsapp';
                fputcsv($handle, [
                    $l->id,
                    $l->contact,
                    $l->contact_name,
                    $l->contact_email,
                    $l->interest,
                    $l->score ?? '',
                    $l->status,
                    $channel,
                    $l->created_at,
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
