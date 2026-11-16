<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Rentang laporan + periode pembanding dengan panjang yang sama. */
final class ReportPeriod
{
    public const PRESETS = ['today', '7d', '30d', 'month', 'custom'];

    public const MAX_CUSTOM_DAYS = 366;

    private function __construct(
        public readonly string $preset,
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function make(?string $preset, ?string $from = null, ?string $to = null): self
    {
        $today = CarbonImmutable::today();

        return match ($preset) {
            '7d' => new self('7d', $today->subDays(6), $today->endOfDay()),
            '30d' => new self('30d', $today->subDays(29), $today->endOfDay()),
            'month' => new self('month', $today->startOfMonth(), $today->endOfDay()),
            'custom' => self::custom($from, $to),
            default => new self('today', $today, $today->endOfDay()),
        };
    }

    public function previous(): self
    {
        $days = $this->days();

        return new self($this->preset, $this->start->subDays($days), $this->start->subSecond());
    }

    public function days(): int
    {
        return (int) $this->start->startOfDay()->diffInDays($this->end->startOfDay()) + 1;
    }

    /** @return array{0: string, 1: string} */
    public function range(): array
    {
        return [$this->start->toDateTimeString(), $this->end->toDateTimeString()];
    }

    public function toArray(): array
    {
        return [
            'preset' => $this->preset,
            'from' => $this->start->toDateString(),
            'to' => $this->end->toDateString(),
        ];
    }

    private static function custom(?string $from, ?string $to): self
    {
        try {
            $start = CarbonImmutable::parse($from ?? 'today')->startOfDay();
            $end = CarbonImmutable::parse($to ?? 'today')->endOfDay();
        } catch (\Throwable) {
            return self::make('today');
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->startOfDay(), $start->endOfDay()];
        }

        if ($start->diffInDays($end) > self::MAX_CUSTOM_DAYS) {
            $start = $end->subDays(self::MAX_CUSTOM_DAYS)->startOfDay();
        }

        return new self('custom', $start, $end);
    }
}
