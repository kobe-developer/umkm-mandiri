<?php

use App\Services\PushSubscriptionService;
use App\Services\StoreStatusService;
use Illuminate\Support\Facades\Schedule;

// Buka/tutup toko otomatis sesuai jam operasional
Schedule::call(fn (StoreStatusService $status) => $status->syncWithSchedule(now()))
    ->name('store:sync-schedule')
    ->everyMinute()
    ->withoutOverlapping();

// Backup database harian (salin folder storage/app/backups ke luar VPS)
Schedule::command('app:backup-database')->dailyAt('03:00')->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')->daily();

// Langganan web push pelanggan untuk order yang sudah final tidak diperlukan lagi
Schedule::call(fn (PushSubscriptionService $push) => $push->pruneFinishedOrders())
    ->name('push:prune-order-subscriptions')
    ->dailyAt('03:30');
