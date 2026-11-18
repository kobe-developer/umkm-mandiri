<?php

namespace App\Http\Controllers;

use App\Http\Requests\PushSubscriptionRequest;
use App\Services\PushSubscriptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Perangkat owner/kasir mendaftar / berhenti menerima web push order online baru. */
class PushSubscriptionController extends Controller
{
    public function __construct(private readonly PushSubscriptionService $subscriptions) {}

    public function store(PushSubscriptionRequest $request): JsonResponse
    {
        $this->subscriptions->subscribeUser($request->user(), $request->validated());

        return response()->json(['subscribed' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['endpoint' => ['required', 'string', 'max:1024']]);
        $this->subscriptions->unsubscribeUser($request->user(), $data['endpoint']);

        return response()->json(['subscribed' => false]);
    }
}
