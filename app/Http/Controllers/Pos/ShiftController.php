<?php

namespace App\Http\Controllers\Pos;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Services\ShiftService;
use App\Support\Rupiah;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShiftController extends Controller
{
    use RequiresOpenShift;

    private const RECENT_SHIFTS = 5;

    public function show(Request $request, ShiftService $shifts): Response
    {
        $user = $request->user();
        $open = $user->openShift;

        return Inertia::render('Pos/Shift', [
            'shift' => fn () => $open ? [...$open->only(['id', 'opened_at', 'opening_cash']), ...$shifts->summary($open)] : null,
            'recent' => fn () => Shift::where('user_id', $user->id)->whereNotNull('closed_at')
                ->latest('opened_at')->limit(self::RECENT_SHIFTS)->get(),
        ]);
    }

    public function store(Request $request, ShiftService $shifts): RedirectResponse
    {
        $data = $request->validate(['opening_cash' => ['required', 'integer', 'min:0', 'max:100000000']]);
        $shifts->open($request->user(), $data['opening_cash']);

        return redirect()->route('pos.orders.create')->with('success', 'Shift dibuka. Selamat bekerja!');
    }

    public function update(Request $request, ShiftService $shifts): RedirectResponse
    {
        $data = $request->validate([
            'closing_cash_actual' => ['required', 'integer', 'min:0', 'max:100000000'],
            'closing_note' => ['nullable', 'string', 'max:255'],
        ]);

        $shift = $shifts->close($this->openShift($request), $data['closing_cash_actual'], $data['closing_note'] ?? null);
        $diff = $shift->cash_difference;
        $message = $diff === 0 ? 'Shift ditutup. Kas sesuai.' : 'Shift ditutup dengan selisih kas '.Rupiah::format($diff).'.';

        return redirect()->route('pos.shift.show')->with($diff === 0 ? 'success' : 'error', $message);
    }
}
