<?php

namespace App\Http\Controllers\Api;

use App\Console\Commands\ExportInboundContacts;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Endpoint para que n8n / Zapier / scripts externos lean la lista de
 * contactos inbound del bot WhatsApp.
 *
 * Auth: token simple en query string ?token=XXX (definido en env APPOGIO_EXPORT_TOKEN).
 * Formato: ?format=json (default) o ?format=csv
 *
 * Filtros: device, group, since (días), lang, has_lead, nps_min
 *
 * Ejemplo n8n:  GET https://tu-dominio/api/contacts/export?token=ABC&format=csv&since=30
 */
class ContactExportController extends Controller
{
    public function index(Request $request)
    {
        $expected = env('APPOGIO_EXPORT_TOKEN');
        if (!$expected) {
            return response()->json(['error' => 'export not configured (set APPOGIO_EXPORT_TOKEN in .env)'], 503);
        }
        if (!hash_equals($expected, (string) $request->query('token', ''))) {
            return response()->json(['error' => 'invalid token'], 401);
        }

        // Reusamos buildRows del comando para no duplicar la lógica de filtrado
        $rows = (new ExportInboundContacts())->buildRows([
            'device'   => $request->query('device') ? (int) $request->query('device') : null,
            'group'    => $request->query('group')  ? (int) $request->query('group')  : null,
            'since'    => $request->query('since')  ? (int) $request->query('since')  : 365,
            'lang'     => $request->query('lang'),
            'has_lead' => filter_var($request->query('has_lead'), FILTER_VALIDATE_BOOLEAN),
            'nps_min'  => $request->query('nps_min') ? (int) $request->query('nps_min') : null,
        ]);

        $format = strtolower($request->query('format', 'json'));

        if ($format === 'csv') {
            $callback = function() use ($rows) {
                $fp = fopen('php://output', 'w');
                if (empty($rows)) {
                    fputcsv($fp, ['phone','name','first_seen','last_seen','language','lead_name','lead_qty','has_lead','nps','had_handoff','last_intent','last_sentiment','total_messages']);
                } else {
                    fputcsv($fp, array_keys($rows[0]));
                    foreach ($rows as $row) fputcsv($fp, $row);
                }
                fclose($fp);
            };
            return response()->streamDownload($callback, 'inbound_contacts_' . date('Ymd_His') . '.csv', [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        }

        // JSON por default — formato cómodo para n8n
        return response()->json([
            'count'        => count($rows),
            'generated_at' => now()->toIso8601String(),
            'filters'      => array_intersect_key($request->query(), array_flip(['device','group','since','lang','has_lead','nps_min'])),
            'contacts'     => $rows,
        ]);
    }
}
