<?php

namespace App\Http\Controllers;

use App\Models\Hashtag;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HashtagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, string $handle): View
    {
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

        return view('hashtags', [
            'user' => $user,
            'hashtags' => $hashtags,
        ]);
    }
}