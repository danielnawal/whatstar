<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Device;
use App\Models\Template;
use App\Models\User;
use DB;
use Auth;
use App\Traits\Whatsapp;
use Cache;
class ChatController extends Controller
{
    use Whatsapp;

    public function chats($id)
    {
        $device = Device::where("user_id", Auth::id())
            ->where("status", 1)
            ->where("uuid", $id)
            ->first();
        abort_if(empty($device), 404);
        $templates = Template::where("user_id", Auth::id())
            ->where("status", 1)
            ->latest()
            ->get();
        return view("wacore::user.chats.list", compact("device", "templates"));
    }

    public function getGroupMetaData(Request $request)
    {
        $device = Device::where("user_id", Auth::id())
            ->where("status", 1)
            ->where("uuid", $request->device_id)
            ->first();

        abort_if(empty($device), 404);

        $metaData = Cache::remember(
            "groups_" . $device->uuid . $request->id,
            520,
            function () use ($device, $request) {
                return $this->groupMetaData($request->id, $device->id);
            }
        );

        return response()->json($metaData);
    }

    public function sendGroupBulkMessage(Request $request, $id)
    {
        if (getUserPlanData("messages_limit") == false) {
            return response()->json(
                [
                    "message" => __("Maximum Monthly Messages Limit Exceeded"),
                ],
                401
            );
        }
        $device = Device::where("user_id", Auth::id())
            ->where("status", 1)
            ->where("uuid", $id)
            ->first();
        abort_if(empty($device), 404);

        if (count($request->groups) == 0) {
            return response()->json(
                [
                    "message" => __("Select Some Groups"),
                ],
                401
            );
        }

        $validated = $request->validate([
            "selecttype" => "required",
        ]);

        $success_requests = 0;
        $faild_requests = 0;
        $user = User::where("id", Auth::id())->first();

        if ($request->selecttype == "template") {
            $validated = $request->validate([
                "template" => "required",
            ]);
            $template = Template::where("user_id", Auth::id())
                ->where("status", 1)
                ->findorFail($request->template);

            foreach ($request->groups as $key => $group) {
                $isGroup = explode("@", $group);
                $isGroup = $isGroup[1];
                abort_if($isGroup != "g.us", 404);

                if (isset($template->body["text"])) {
                    $body = $template->body;
                    

                    $text = $this->formatText(
                        $template->body["text"],
                        [],
                        $user
                    );
                    $body["text"] = $text;
                } else {
                    $body = $template->body;
                }
                $type = $template->type;

                try {
                    $response = $this->sendMessageToGroup(
                        $body,
                        $device->id,
                        $group,
                        $type,
                        true,
                        env('DELAY_TIME',0)
                    );

                    if ($response["status"] == 200) {
                        $logs["user_id"] = Auth::id();
                        $logs["device_id"] = $device->id;
                        $logs["from"] = $device->phone ?? null;
                        $logs["to"] = "Group : " . $request->group_name;
                        $logs["template_id"] = $template->id ?? null;
                        $logs["type"] = "single-send";
                        $this->saveLog($logs);

                        
                        $success_requests = $success_requests+1;
                    } else {
                        
                        $faild_requests = $faild_requests+1;
                        
                    }
                } catch (Exception $e) {
                    $faild_requests = $faild_requests+1;
                }
            }
        } else {
            $validated = $request->validate([
                "message" => "required|max: 2000",
            ]);

            $text = $this->formatText($request->message);
            $body["text"] = $text;
            $type = "plain-text";

             foreach ($request->groups as $key => $group) {

                $isGroup = explode("@", $group);
                $isGroup = $isGroup[1];
                abort_if($isGroup != "g.us", 404);

                try {
                    $response = $this->sendMessageToGroup(
                        $body,
                        $device->id,
                        $group,
                        $type,
                        true,
                        env('DELAY_TIME',0)
                    );

                    if ($response["status"] == 200) {
                        $logs["user_id"] = Auth::id();
                        $logs["device_id"] = $device->id;
                        $logs["from"] = $device->phone ?? null;
                        $logs["to"] = "Group : " . $request->group_name;
                        $logs["template_id"] = $template->id ?? null;
                        $logs["type"] = "single-send";
                        $this->saveLog($logs);

                       $success_requests = $success_requests+1;
                    } else {
                        $faild_requests = $faild_requests+1;
                    }
                } catch (Exception $e) {
                   $faild_requests = $faild_requests+1;
                }

           }
        }

        return response()->json(
            [
                "message" => __("Total Message Sent in (".$success_requests.") Groups. Total Sending Faild in (".$faild_requests.") Groups"),
            ],
            200
        );
    }

    public function sendMessage(Request $request, $id)
    {
        if (getUserPlanData("messages_limit") == false) {
            return response()->json(
                [
                    "message" => __("Maximum Monthly Messages Limit Exceeded"),
                ],
                401
            );
        }

        $device = Device::where("user_id", Auth::id())
            ->where("status", 1)
            ->where("uuid", $id)
            ->first();
        abort_if(empty($device), 404);

        $validated = $request->validate([
            "reciver" => "required|max:20",
            "selecttype" => "required",
        ]);

        if ($request->selecttype == "template") {
            $validated = $request->validate([
                "template" => "required",
            ]);
            $template = Template::where("user_id", Auth::id())
                ->where("status", 1)
                ->findorFail($request->template);

            if (isset($template->body["text"])) {
                $body = $template->body;
                $user = User::where("id", Auth::id())->first();

                $text = $this->formatText($template->body["text"], [], $user);
                $body["text"] = $text;
            } else {
                $body = $template->body;
            }
            $type = $template->type;
        } else {
            $validated = $request->validate([
                "message" => "required|max: 4000",
            ]);

            // ── Modo entrenamiento: comando /aprender ───────────────────────
            // Sintaxis:  /aprender keyword1, keyword2 | respuesta del bot
            // Crea una regla nueva en BD para el device del agente, con priority
            // alta. El mensaje NO se envía al cliente.
            $rawMsg = trim($request->message);
            if (\Illuminate\Support\Str::startsWith($rawMsg, ['/aprender ', '/learn '])) {
                return $this->createRuleFromTraining($rawMsg, $device, $request->reciver);
            }

            $text = $this->formatText($request->message);
            $body["text"] = $text;
            $type = "plain-text";
        }

        if (!isset($body)) {
            return response()->json(["error" => "Request Failed"], 401);
        }

        try {
            $response = $this->messageSend(
                $body,
                $device->id,
                $request->reciver,
                $type,
                true
            );

            if ($response["status"] == 200) {
                $logs["user_id"] = Auth::id();
                $logs["device_id"] = $device->id;
                $logs["from"] = $device->phone ?? null;
                $logs["to"] = $request->reciver;
                $logs["template_id"] = $template->id ?? null;
                $logs["type"] = "single-send";
                $this->saveLog($logs);

                // Si este contacto tiene una sesión de chatbot pausada por
                // handoff humano, resetear el timer a +24h: el agente acaba
                // de responder, así que sigue conduciendo la conversación.
                \App\Models\ChatbotSession::where('device_id', $device->id)
                    ->where('contact', $request->reciver)
                    ->where('is_paused', 1)
                    ->update([
                        'paused_until' => \Carbon\Carbon::now()->addHours(24),
                    ]);

                return response()->json(
                    [
                        "message" => __("Message sent successfully..!!"),
                    ],
                    200
                );
            } else {
                return response()->json(["error" => "Request Failed"], 401);
            }
        } catch (Exception $e) {
            return response()->json(["error" => "Request Failed"], 401);
        }
    }

    /**
     * Crea una regla nueva del chatbot a partir del comando /aprender enviado
     * por el agente desde el panel.
     * Sintaxis: "/aprender keyword1, keyword2 | respuesta del bot"
     */
    private function createRuleFromTraining(string $msg, $device, string $contact)
    {
        // Quitar prefijo de comando
        $payload = preg_replace('/^\/(aprender|learn)\s+/i', '', $msg);
        $parts   = explode('|', $payload, 2);

        if (count($parts) < 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            return response()->json([
                'error' => "Formato inválido. Uso: /aprender keyword1, keyword2 | respuesta del bot"
            ], 422);
        }

        $keywords = trim($parts[0]);
        $reply    = trim($parts[1]);

        if (mb_strlen($reply) < 5) {
            return response()->json(['error' => 'La respuesta es demasiado corta'], 422);
        }

        try {
            $rule = new \App\Models\Reply();
            $rule->user_id          = Auth::id();
            $rule->device_id        = $device->id;
            $rule->keyword          = mb_substr($keywords, 0, 500);
            $rule->reply            = mb_substr($reply, 0, 4000);
            $rule->reply_en         = '';
            $rule->reply_pt         = '';
            $rule->match_type       = 'like';
            $rule->priority         = 7; // mayor que reglas avanzadas (6)
            $rule->cooldown_minutes = 0;
            $rule->only_once        = 0;
            $rule->schedule_enabled = 0;
            $rule->schedule_days    = '1,2,3,4,5,6,7';
            $rule->reply_type       = 'text';
            $rule->trigger_handoff  = 0;
            $rule->save();

            return response()->json([
                'message' => "✅ Regla aprendida (#{$rule->id}). Disparará con: {$keywords}",
                'rule_id' => $rule->id,
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Error guardando regla: ' . $e->getMessage()], 500);
        }
    }

    public function chatHistory($id)
    {
        $device = Device::where("user_id", Auth::id())
            ->where("status", 1)
            ->where("uuid", $id)
            ->first();
        abort_if(empty($device), 404);

        $response = Cache::remember(
            "groups_" . $device->uuid,
            120,
            function () use ($device) {
                return $this->getChats($device->id);
            }
        );
        if ($response["status"] == 200) {
            $data["chats"] = $response["data"];
            $data["device_name"] = $device->name;
            $data["phone"] = $device->phone;
            return response()->json($data);
        }

        $data["message"] = $response["message"];
        $data["status"] = $response["status"];

        return response()->json($data, 401);
    }

    public function groups($id)
    {
        $device = Device::where("user_id", Auth::id())
            ->where("status", 1)
            ->where("uuid", $id)
            ->first();
        abort_if(empty($device), 404);
        $templates = Template::where("user_id", Auth::id())
            ->where("status", 1)
            ->latest()
            ->get();
        return view("wacore::user.chats.groups", compact("device", "templates"));
    }

    public function groupHistory($id)
    {
        $device = Device::where("user_id", Auth::id())
            ->where("status", 1)
            ->where("uuid", $id)
            ->first();
        abort_if(empty($device), 404);

        $response = $this->getGroupList($device->id);

        if ($response["status"] == 200) {
            $data["chats"] = $response["data"];
            $data["device_name"] = $device->name;
            $data["phone"] = $device->phone;
            return response()->json($data);
        }

        $data["message"] = $response["message"];
        $data["status"] = $response["status"];

        return response()->json($data, 401);
    }

    public function sendGroupMessage(Request $request, $id)
    {
        $device = Device::where("user_id", Auth::id())
            ->where("status", 1)
            ->where("uuid", $id)
            ->first();
        abort_if(empty($device), 404);

        $validated = $request->validate([
            "group" => "required|max:50",
            "group_name" => "required|max:100",
            "selecttype" => "required",
        ]);

        $isGroup = explode("@", $request->group);
        $isGroup = $isGroup[1];
        abort_if($isGroup != "g.us", 404);

        if ($request->selecttype == "template") {
            $validated = $request->validate([
                "template" => "required",
            ]);

            $template = Template::where("user_id", Auth::id())
                ->where("status", 1)
                ->findorFail($request->template);

            if (isset($template->body["text"])) {
                $body = $template->body;
                $user = User::where("id", Auth::id())->first();

                $text = $this->formatText($template->body["text"], [], $user);
                $body["text"] = $text;
            } else {
                $body = $template->body;
            }
            $type = $template->type;
        } else {
            $validated = $request->validate([
                "message" => "required|max: 500",
            ]);

            $text = $this->formatText($request->message);
            $body["text"] = $text;
            $type = "plain-text";
        }

        if (!isset($body)) {
            return response()->json(["error" => "Request Failed"], 401);
        }

        try {
            $response = $this->sendMessageToGroup(
                $body,
                $device->id,
                $request->group,
                $type,
                true,
                0
            );

            if ($response["status"] == 200) {
                $logs["user_id"] = Auth::id();
                $logs["device_id"] = $device->id;
                $logs["from"] = $device->phone ?? null;
                $logs["to"] = "Group : " . $request->group_name;
                $logs["template_id"] = $template->id ?? null;
                $logs["type"] = "single-send";
                $this->saveLog($logs);

                return response()->json(
                    [
                        "message" => __("Message sent successfully..!!"),
                    ],
                    200
                );
            } else {
                return response()->json(["error" => "Request Failed"], 401);
            }
        } catch (Exception $e) {
            return response()->json(["error" => "Request Failed"], 401);
        }
    }
}
