<?php

namespace App\Services;

use App\Enums\PaymentMethodType;
use App\Enums\PaymentStatus;
use App\Models\Shift;
use App\Models\User;
use App\Support\BusinessRule;
use Illuminate\Support\Facades\DB;

class ShiftService
{
    public function open(User $user, int $openingCash): Shift
    {
        return DB::transaction(function () use ($user, $openingCash) {
            $hasOpen = Shift::where('user_id', $user->id)->whereNull('closed_at')->lockForUpdate()->exists();
            BusinessRule::check(! $hasOpen, 'Masih ada shift yang terbuka.', 'shift');

            return Shift::create(['user_id' => $user->id, 'opened_at' => now(), 'opening_cash' => $openingCash]);
        });
    }

    public function close(Shift $shift, int $actualCash, ?string $note): Shift
    {
        return DB::transaction(function () use ($shift, $actualCash, $note) {
            $locked = Shift::whereKey($shift->id)->lockForUpdate()->firstOrFail();
            BusinessRule::check($locked->isOpen(), 'Shift sudah ditutup.', 'shift');

            $systemCash = $locked->opening_cash + $this->summary($locked)['cash_sales'];
            $locked->fill([
                'closed_at' => now(),
                'closing_cash_actual' => $actualCash,
                'closing_cash_system' => $systemCash,
                'cash_difference' => $actualCash - $systemCash,
                'closing_note' => $note,
            ])->save();

            return $locked;
        });
    }

    /**
     * Ringkasan penjualan dalam shift (pembayaran lunas yang dicatat di shift ini).
     *
     * @return array{cash_sales: int, non_cash_sales: int, paid_count: int, unpaid_count: int, expected_cash: int}
     */
    public function summary(Shift $shift): array
    {
        $rows = DB::table('orders')
            ->join('payment_methods', 'payment_methods.id', '=', 'orders.payment_method_id')
            ->where('orders.shift_id', $shift->id)
            ->where('orders.payment_status', PaymentStatus::Paid->value)
            ->selectRaw('payment_methods.type as type, SUM(orders.total) as total, COUNT(*) as cnt')
            ->groupBy('payment_methods.type')
            ->get();

        $cash = (int) $rows->where('type', PaymentMethodType::Cash->value)->sum('total');
        $nonCash = (int) $rows->where('type', '!=', PaymentMethodType::Cash->value)->sum('total');

        return [
            'cash_sales' => $cash,
            'non_cash_sales' => $nonCash,
            'paid_count' => (int) $rows->sum('cnt'),
            'unpaid_count' => $shift->orders()->unpaid()->count(),
            'expected_cash' => $shift->opening_cash + $cash,
        ];
    }
}
