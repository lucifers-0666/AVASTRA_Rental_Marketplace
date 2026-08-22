<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Flexible pricing engine.
 *
 * A rental of N days is decomposed into months + weeks + days and priced
 * with the space's package rates; the customer is charged the CHEAPER of
 * the bundle price vs the flat daily rate (never penalize long stays).
 *
 * Days are counted inclusive: Aug 1 -> Aug 15 = 15 days.
 * Missing weekly/monthly rates fall back to 7x / 30x the daily rate.
 *
 * @param array $space spaces row (rate_daily, rate_weekly, rate_monthly, security_deposit)
 * @return array{total_days:int, months:int, weeks:int, days:int, base_amount:float,
 *               commission_amount:float, deposit_amount:float, total_amount:float, owner_payout:float}
 */
function calculate_price(array $space, string $startDate, string $endDate): array
{
    $start = new DateTime($startDate);
    $end   = new DateTime($endDate);
    if ($end < $start) {
        throw new InvalidArgumentException('end_date must be on or after start_date');
    }

    $totalDays = (int) $start->diff($end)->days + 1;

    $daily   = (float) $space['rate_daily'];
    $weekly  = isset($space['rate_weekly'])  ? (float) $space['rate_weekly']  : $daily * 7;
    $monthly = isset($space['rate_monthly']) ? (float) $space['rate_monthly'] : $daily * 30;

    $months = intdiv($totalDays, 30);
    $rem    = $totalDays % 30;
    $weeks  = intdiv($rem, 7);
    $days   = $rem % 7;

    $base = min(
        $months * $monthly + $weeks * $weekly + $days * $daily,
        $totalDays * $daily
    );

    $commission = round($base * current_commission_percent() / 100, 2);
    $deposit    = (float) ($space['security_deposit'] ?? 0);

    return [
        'total_days'        => $totalDays,
        'months'            => $months,
        'weeks'             => $weeks,
        'days'              => $days,
        'base_amount'       => round($base, 2),
        'commission_amount' => $commission,
        'deposit_amount'    => $deposit,
        'total_amount'      => round($base + $deposit, 2),
        'owner_payout'      => round($base - $commission, 2),
    ];
}
