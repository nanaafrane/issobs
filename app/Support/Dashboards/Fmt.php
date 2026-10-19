<?php

namespace App\Support\Dashboards;

/** Number formatting shared by the dashboard views. */
class Fmt
{
    /** GH₵ 12,345.67 */
    public static function money(?float $v, int $dp = 2): string
    {
        return 'GH₵ ' . number_format((float) $v, $dp);
    }

    /** GH₵ 1.24M / GH₵ 845.3K / GH₵ 912 — for tiles where the full figure is in the tooltip. */
    public static function short(?float $v): string
    {
        $v = (float) $v;
        $abs = abs($v);
        $s = match (true) {
            $abs >= 1_000_000 => number_format($v / 1_000_000, 2) . 'M',
            $abs >= 10_000 => number_format($v / 1_000, 1) . 'K',
            default => number_format($v, 0),
        };

        return 'GH₵ ' . $s;
    }

    public static function pct(?float $v, int $dp = 1): string
    {
        return $v === null ? '—' : number_format($v, $dp) . '%';
    }

    public static function int(?int $v): string
    {
        return number_format((int) $v);
    }

    /**
     * Change against a previous value: ['text' => '+12.4% vs Sep', 'dir' => up|down|flat] or null
     * when there is nothing to compare with.
     */
    public static function delta(float $now, float $before, string $label): ?array
    {
        if (abs($before) < 0.005) {
            return null;
        }
        $pct = ($now - $before) / abs($before) * 100;
        $dir = abs($pct) < 0.05 ? 'flat' : ($pct > 0 ? 'up' : 'down');

        return ['text' => ($pct > 0 ? '+' : '') . number_format($pct, 1) . '% vs ' . $label, 'dir' => $dir];
    }
}
