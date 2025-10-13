<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * Redirect authenticated users to dashboard, show landing page to guests.
     */
    public function __invoke(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('landing');
    }
}
