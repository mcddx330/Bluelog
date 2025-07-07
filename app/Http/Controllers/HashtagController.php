<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\User;
use App\Traits\BuildViewBreadcrumbs;
use App\Traits\PreparesProfileData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HashtagController extends Controller {
    use PreparesProfileData, BuildViewBreadcrumbs;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $handle): View {
        $user = User::where('handle', $handle)->firstOrFail();

        $hashtags = Hashtag::select('tag')
            ->selectRaw('count(*) as count')
            ->whereHas('post', function ($query) use ($user) {
                $query->where('did', $user->did);
            })
            ->groupBy('tag')
            ->orderByDesc('count')
            ->orderBy('tag')
            ->paginate(50);

        return view('hashtags', array_merge([
            'breadcrumbs' => $this
                ->addBreadcrumb($user->handle, route('profile.show', ['handle' => $user->handle]))
                ->addBreadcrumb('ハッシュタグ')
                ->getBreadcrumbs(),
            'user'        => $user,
            'hashtags'    => $hashtags,
        ], $this->prepareCommonProfileData($user)));
    }
}
