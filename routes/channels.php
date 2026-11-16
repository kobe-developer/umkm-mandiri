<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

// Notifikasi order untuk Owner & Kasir yang aktif
Broadcast::channel('orders', fn (User $user) => $user->is_active);
