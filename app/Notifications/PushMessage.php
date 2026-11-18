<?php

namespace App\Notifications;

use NotificationChannels\WebPush\WebPushMessage;

/** Format pesan web push yang dipahami public/sw.js (data.url dibuka saat notifikasi diklik). */
final class PushMessage
{
    /** Pesan status order tidak berguna lagi bila tertunda lebih dari ini (detik). */
    private const TTL_SECONDS = 3600;

    public static function make(string $title, string $body, string $url, string $tag): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($title)
            ->body($body)
            ->icon('/icons/icon-192.png')
            ->badge('/icons/badge-96.png')
            ->tag($tag)
            ->renotify()
            ->lang('id')
            ->data(['url' => $url])
            ->options(['TTL' => self::TTL_SECONDS, 'urgency' => 'high']);
    }
}
