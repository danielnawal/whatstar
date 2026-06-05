<?php

namespace Modules\Wacore\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Reply;
use App\Models\Template;
use App\Models\Device;
use App\Traits\Uploader;
use Auth;
use DB;
class ChatbotController extends Controller
{
    use Uploader;

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $templates=Template::where('user_id',Auth::id())->where('status',1)->latest()->get();
        $replies=Reply::where('user_id',Auth::id())->with('device')->latest()->paginate(20);
        $total_replies=Reply::where('user_id',Auth::id())->count();
        $template_replies=Reply::where('user_id',Auth::id())->where('template_id','!=',null)->count();
        $text_replies=Reply::where('user_id',Auth::id())->where('template_id',null)->count();
        $devices = Device::where('user_id',Auth::id())->where('status',1)->latest()->get();

        $allReplies = Reply::where('user_id', Auth::id())->whereNull('parent_reply_id')->latest()->get();

        return view('wacore::user.chatbot.index',compact('templates','replies','total_replies','template_replies','text_replies','devices','allReplies'));
    }

    

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if (getUserPlanData('chatbot') == false) {
            return response()->json([
                'message' => __('Chatbot features is not available with your subscription')
            ], 401);
        }

        $noKeywordTypes = ['any', 'first_contact'];

        $request->validate([
            'keyword'    => 'required_unless:match_type,' . implode(',match_type,', $noKeywordTypes),
            'match_type' => 'required|in:equal,starts_with,contains,like,fuzzy,regex,any,first_contact',
            'reply_type' => 'required',
            'device'     => 'required',
        ]);

        Device::where('user_id', Auth::id())->findOrFail($request->device);

        if ($request->reply_type === 'template') {
            $request->validate(['template' => 'required']);
            $template = Template::where('user_id', Auth::id())->where('status', 1)->findOrFail($request->template);
        } else {
            $request->validate(['reply' => 'required|max:2000']);
        }

        // Validar y limpiar interactive_options
        $interactiveOptions = null;
        if (in_array($request->interactive_type, ['list', 'buttons'])) {
            $opts = json_decode($request->interactive_options ?? '[]', true);
            $interactiveOptions = json_encode(is_array($opts) ? $opts : []);
        }

        $reply                     = new Reply;
        $reply->user_id            = Auth::id();
        $reply->device_id          = $request->device;
        $reply->template_id        = ($request->reply_type === 'template') ? ($template->id ?? null) : null;
        $reply->keyword            = in_array($request->match_type, $noKeywordTypes) ? '*' : $request->keyword;
        $reply->reply              = ($request->reply_type !== 'template') ? $request->reply : null;
        $reply->reply_en           = ($request->reply_type !== 'template') ? ($request->reply_en ?: null) : null;
        $reply->reply_pt           = ($request->reply_type !== 'template') ? ($request->reply_pt ?: null) : null;
        $reply->match_type         = $request->match_type;
        $reply->reply_type         = ($request->reply_type === 'template') ? 'template' : 'text';
        $reply->priority           = (int) ($request->priority ?? 0);
        $reply->cooldown_minutes   = (int) ($request->cooldown_minutes ?? 0);
        $reply->only_once          = $request->boolean('only_once');
        $reply->schedule_enabled   = $request->boolean('schedule_enabled');
        $reply->schedule_start     = $request->schedule_start    ?: '08:00:00';
        $reply->schedule_end       = $request->schedule_end      ?: '18:00:00';
        $reply->schedule_days      = implode(',', $request->schedule_days ?? ['1','2','3','4','5','6','7']);
        $reply->out_of_hours_reply = $request->out_of_hours_reply ?: null;
        $reply->parent_reply_id    = $request->parent_reply_id   ?: null;
        $reply->interactive_type   = $request->interactive_type  ?: null;
        $reply->interactive_options = $interactiveOptions;
        $reply->capture_field      = $request->capture_field     ?: null;
        $reply->trigger_handoff    = $request->boolean('trigger_handoff');

        // Manejo de archivo adjunto opcional (PDF, imagen, doc, audio)
        if ($request->hasFile('media_file')) {
            $request->validate([
                'media_file' => 'mimes:jpg,jpeg,png,webp,pdf,docx,xlsx,csv,txt,mp3,ogg,mp4|max:20480',
            ]);
            $f = $request->file('media_file');
            $reply->media_path     = $this->saveFile($request, 'media_file');
            $reply->media_filename = $f->getClientOriginalName();
            $reply->media_mime     = $f->getMimeType();
        }

        $reply->save();

        return response()->json([
            'message'  => __('Reply Created Successfully'),
            'redirect' => route('user.chatbot.index'),
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $noKeywordTypes = ['any', 'first_contact'];

        $request->validate([
            'keyword'    => 'required_unless:match_type,' . implode(',match_type,', $noKeywordTypes),
            'match_type' => 'required|in:equal,starts_with,contains,like,fuzzy,regex,any,first_contact',
            'reply_type' => 'required',
            'device'     => 'required',
        ]);

        Device::where('user_id', Auth::id())->findOrFail($request->device);

        if ($request->reply_type === 'template') {
            $request->validate(['template' => 'required']);
            $template = Template::where('user_id', Auth::id())->where('status', 1)->findOrFail($request->template);
        } else {
            $request->validate(['reply' => 'required|max:2000']);
        }

        // Validar y limpiar interactive_options
        $interactiveOptions = null;
        if (in_array($request->interactive_type, ['list', 'buttons'])) {
            $opts = json_decode($request->interactive_options ?? '[]', true);
            $interactiveOptions = json_encode(is_array($opts) ? $opts : []);
        }

        $reply                     = Reply::where('user_id', Auth::id())->findOrFail($id);
        $reply->device_id          = $request->device;
        $reply->template_id        = ($request->reply_type === 'template') ? ($template->id ?? null) : null;
        $reply->keyword            = in_array($request->match_type, $noKeywordTypes) ? '*' : $request->keyword;
        $reply->reply              = ($request->reply_type !== 'template') ? $request->reply : null;
        $reply->reply_en           = ($request->reply_type !== 'template') ? ($request->reply_en ?: null) : null;
        $reply->reply_pt           = ($request->reply_type !== 'template') ? ($request->reply_pt ?: null) : null;
        $reply->match_type         = $request->match_type;
        $reply->reply_type         = ($request->reply_type === 'template') ? 'template' : 'text';
        $reply->priority           = (int) ($request->priority ?? 0);
        $reply->cooldown_minutes   = (int) ($request->cooldown_minutes ?? 0);
        $reply->only_once          = $request->boolean('only_once');
        $reply->schedule_enabled   = $request->boolean('schedule_enabled');
        $reply->schedule_start     = $request->schedule_start    ?: '08:00:00';
        $reply->schedule_end       = $request->schedule_end      ?: '18:00:00';
        $reply->schedule_days      = implode(',', $request->schedule_days ?? ['1','2','3','4','5','6','7']);
        $reply->out_of_hours_reply = $request->out_of_hours_reply ?: null;
        $reply->parent_reply_id    = $request->parent_reply_id   ?: null;
        $reply->interactive_type   = $request->interactive_type  ?: null;
        $reply->interactive_options = $interactiveOptions;
        $reply->capture_field      = $request->capture_field     ?: null;
        $reply->trigger_handoff    = $request->boolean('trigger_handoff');

        // Manejo de archivo adjunto opcional (mismo flujo que store).
        // Si no se sube archivo nuevo, se preserva el existente.
        // Si llega ?media_remove=1, se elimina el adjunto.
        if ($request->hasFile('media_file')) {
            $request->validate([
                'media_file' => 'mimes:jpg,jpeg,png,webp,pdf,docx,xlsx,csv,txt,mp3,ogg,mp4|max:20480',
            ]);
            $f = $request->file('media_file');
            // Borrar archivo anterior si existía
            if ($reply->media_path) $this->removeFile($reply->media_path);
            $reply->media_path     = $this->saveFile($request, 'media_file');
            $reply->media_filename = $f->getClientOriginalName();
            $reply->media_mime     = $f->getMimeType();
        } elseif ($request->boolean('media_remove') && $reply->media_path) {
            $this->removeFile($reply->media_path);
            $reply->media_path     = null;
            $reply->media_filename = null;
            $reply->media_mime     = null;
        }

        $reply->save();

        return response()->json([
            'message'  => __('Reply Updated Successfully'),
            'redirect' => route('user.chatbot.index'),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $reply =  Reply::where('user_id',Auth::id())->findorFail($id);
        $reply->delete();

        return response()->json([
            'message' => __('Congratulations! Your Reply Successfully Removed'),
            'redirect' => route('user.chatbot.index')
        ]);
    }
}
