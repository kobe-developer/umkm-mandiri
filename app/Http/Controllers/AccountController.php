<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Account/Edit', ['hasPin' => $request->user()->hasPin()]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    /** PIN owner untuk menyetujui void/refund dari kasir. */
    public function updatePin(Request $request, ActivityLogger $logger): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'pin' => ['required', 'digits:6', 'confirmed'],
        ]);

        $user = $request->user();
        $user->forceFill(['pin_hash' => Hash::make($request->input('pin'))])->save();
        $logger->log('update_pin', $user, null, null, 'PIN owner diubah');

        return back()->with('success', 'PIN owner berhasil disimpan.');
    }
}
