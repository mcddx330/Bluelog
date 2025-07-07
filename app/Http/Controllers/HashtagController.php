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

        $sort_by = $request->input('sort_by', 'count'); // 'count' or 'tag'
        $order = $request->input('order', 'desc'); // 'asc' or 'desc'

        $hashtags = Hashtag::select('tag')
            ->selectRaw('count(*) as count')
            ->whereHas('post', function ($query) use ($user) {
                $query->where('did', $user->did);
            })
            ->groupBy('tag');

        if ($sort_by === 'tag') {
            $hashtags->orderBy('tag', $order);
        } else { // default sort by count
            $hashtags->orderBy('count', $order);
            $hashtags->orderBy('tag', 'asc'); // Secondary sort
        }

        $hashtags = $hashtags->paginate(50);

        return view('hashtags', array_merge([
            'breadcrumbs' => $this
                ->addBreadcrumb($user->handle, route('profile.show', ['handle' => $user->handle]))
                ->addBreadcrumb('ハッシュタグ')
                ->getBreadcrumbs(),
            'hashtags'    => $hashtags,
        ], $this->prepareCommonProfileData($user)));
    }
}
