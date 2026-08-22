<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

/**
 * Booking conflict detection.
 * Overlap rule (from the project spec):
 *   existing.start < new.end  AND  existing.end > new.start  =>  CONFLICT
 * Only statuses that hold the space count as conflicts.
 */
function has_booking_conflict(int $spaceId, string $startDate, string $endDate, ?int $ignoreBookingId = null): bool
{
    $sql = 'SELECT COUNT(*) AS conflicts
            FROM bookings
            WHERE space_id = :space_id
              AND status IN ("pending", "approved", "confirmed", "active")
              AND start_date < :end_date
              AND end_date   > :start_date';
    $params = ['space_id' => $spaceId, 'start_date' => $startDate, 'end_date' => $endDate];

    if ($ignoreBookingId !== null) {
        $sql .= ' AND id <> :ignore_id';
        $params['ignore_id'] = $ignoreBookingId;
    }

    $row = Database::fetch($sql, $params);
    return $row !== null && (int) $row['conflicts'] > 0;
}

/**
 * True when [start, end] sits fully inside at least one OPEN availability
 * range and does not overlap any BLOCKED range.
 * Note: a space with no open availability range is not bookable.
 */
function is_within_availability(int $spaceId, string $startDate, string $endDate): bool
{
    $open = Database::fetch(
        'SELECT COUNT(*) AS c FROM space_availability
         WHERE space_id = ? AND is_blocked = 0 AND start_date <= ? AND end_date >= ?',
        [$spaceId, $startDate, $endDate]
    );
    if ($open === null || (int) $open['c'] === 0) {
        return false;
    }

    $blocked = Database::fetch(
        'SELECT COUNT(*) AS c FROM space_availability
         WHERE space_id = ? AND is_blocked = 1 AND start_date < ? AND end_date > ?',
        [$spaceId, $endDate, $startDate]
    );

    return $blocked !== null && (int) $blocked['c'] === 0;
}
