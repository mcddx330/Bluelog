<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class FaqController extends Controller
{
    /**
     * Display the FAQ page.
     *
     * @return View
     */
    public function index()
    {
        return view('faq');
    }
}
