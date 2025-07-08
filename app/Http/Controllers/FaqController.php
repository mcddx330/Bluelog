<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FaqController extends Controller
{
    /**
     * Display the FAQ page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('faq');
    }
}
