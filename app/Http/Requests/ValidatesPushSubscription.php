<?php

namespace App\Http\Requests;

use App\Rules\PushServiceEndpoint;
use NotificationChannels\WebPush\PushSubscription;

/** Aturan validasi objek PushSubscription dari browser (hasil subscription.toJSON()). */
trait ValidatesPushSubscription
{
    /** @return array<string, list<mixed>> */
    protected function pushSubscriptionRules(string $prefix = '', bool $required = true): array
    {
        $presence = $required ? 'required' : 'required_with:'.rtrim($prefix, '.');

        return [
            "{$prefix}endpoint" => [$presence, 'string', 'max:'.PushSubscription::ENDPOINT_MAX_LENGTH, new PushServiceEndpoint],
            "{$prefix}keys" => [$presence, 'array'],
            "{$prefix}keys.p256dh" => [$presence, 'string', 'max:255'],
            "{$prefix}keys.auth" => [$presence, 'string', 'max:255'],
            "{$prefix}content_encoding" => ['nullable', 'in:aesgcm,aes128gcm'],
        ];
    }
}
