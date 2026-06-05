<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Option;
use App\Traits\Uploader;
use App\Actions\Optionupdate;
use Cache;

class AppSettingsController extends Controller
{
    use Uploader;

    public function __construct()
    {
        $this->middleware('permission:page-settings');
    }

    public function index()
    {
        return app(SettingsController::class)->index();
    }

    public function store(Request $request)
    {
        return app(SettingsController::class)->store($request);
    }

    public function update(Request $request, $id)
    {
        return app(SettingsController::class)->update($request, $id);
    }

    public function destroy($id)
    {
        return app(SettingsController::class)->destroy($id);
    }

    public function edit($id)
    {
        return app(SettingsController::class)->edit($id);
    }

    public function create()
    {
        return app(SettingsController::class)->create();
    }

    public function show($id)
    {
        return app(SettingsController::class)->show($id);
    }
}
