<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;

class IndexController extends Controller
{
    /**
     * Display the welcome page.
     *
     * @return View
     */
    public function index()
    {
        $recent_public_users = User::where('is_private', false)
            ->inRandomOrder()
            ->limit(10)
            ->get();

        return view('index', compact('recent_public_users'));
    }
}
