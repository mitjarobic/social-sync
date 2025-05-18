<?php

namespace App\Http\Controllers;

use App\Support\DevHelper;
use Illuminate\Http\Request;
use App\Services\FacebookService;
use App\Services\PlatformSyncService;

class FacebookController extends Controller
{
    public function redirect(FacebookService $facebookService)
    {
        $helper = $facebookService->getRedirectLoginHelper();

        $permissions = [
            'pages_show_list',
            'pages_read_engagement',
            'pages_manage_posts',
            'pages_read_user_content',
            'instagram_basic', 
            'instagram_content_publish',
            'pages_read_engagement',
            'instagram_manage_insights',
            'business_management', // ✅ required for full access under Business Manager
        ];

        $url = DevHelper::withNgrokUrl(route('facebook.callback'));
        $loginUrl = $helper->getLoginUrl($url, $permissions);
        return redirect()->away($loginUrl);
    }

    public function callback(Request $request, FacebookService $facebookService)
    {
        $helper = $facebookService->getRedirectLoginHelper();

        if ($request->state) {
            $helper->getPersistentDataHandler()->set('state', $request->state);
        }

        try {
            $token = $helper->getAccessToken();
            // $token = $facebookService->exchangeForLongLivedToken($token);

            // Store user token
            auth()->user()->update([
                'facebook_token' => (string) $token
            ]);

            // Sync platforms
            (new PlatformSyncService(auth()->user()))->syncPlatforms();

            return redirect()->route('filament.admin.pages.profile')->with('success', 'Facebook connected.');
        } catch (\Exception $e) {
            logger()->info('Facebook callback error', [$e->getMessage()]);
            return redirect()->route('filament.admin.pages.profile')->with('error', $e->getMessage());
        }
    }
}
