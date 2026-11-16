<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $attempt = [...$credentials, 'is_active' => true];
        if (! Auth::attempt($attempt, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['username' => 'Username atau password salah.']);
        }

        $request->session()->regenerate();
        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended($user->isOwner() ? route('admin.dashboard') : route('pos.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
