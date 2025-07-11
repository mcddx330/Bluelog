<?php

namespace App\Http\Controllers;

use App\Services\SettingService;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    private SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function show(): Response
    {
        $deny_all = $this->settingService->get('deny_all_crawlers', false);
        $content = view('robots', ['deny_all' => $deny_all])->render();

        return response($content, 200, ['Content-Type' => 'text/plain']);
    }
}
