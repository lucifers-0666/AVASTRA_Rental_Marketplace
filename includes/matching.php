<?php
declare(strict_types=1);

require_once __DIR__ . '/booking.php';
require_once __DIR__ . '/pricing.php';

/**
 * Requirement-based matching engine — rule-based and explainable.
 * Each factor produces a 0.0–1.0 sub-score; the total is the weighted
 * sum expressed as a percentage ("Match: 96%"). No black-box ML.
 */
const MATCH_WEIGHTS = [
    'location'     => 20,
    'size'         => 20,
    'purpose'      => 20,
    'availability' => 20,
    'budget'       => 10,
    'amenities'    => 10,
];

/**
 * @param array $requirement keys: city, state, min_sqft, max_sqft, purpose_id,
 *                           start_date, end_date, budget, amenity_ids (int[])
 * @param array $space       spaces row + address fields (city, state) +
 *                           purpose_ids (int[]) + amenity_ids (int[])
 * @return array{total: float, factors: array<string, float>, estimate: array}
 */
function match_score(array $requirement, array $space): array
{
    $factors = [];

    // Location: same city = 1.0, same state = 0.5, else 0 (V2: distance via lat/lng)
    if (mb_strtolower((string) $space['city']) === mb_strtolower((string) $requirement['city'])) {
        $factors['location'] = 1.0;
    } elseif (mb_strtolower((string) $space['state']) === mb_strtolower((string) $requirement['state'])) {
        $factors['location'] = 0.5;
    } else {
        $factors['location'] = 0.0;
    }

    // Size: inside requested range = 1.0, within ±25% margin = 0.5, else 0
    $size = (float) $space['size_sqft'];
    $min  = (float) $requirement['min_sqft'];
    $max  = (float) $requirement['max_sqft'];
    if ($size >= $min && $size <= $max) {
        $factors['size'] = 1.0;
    } elseif ($size >= $min * 0.75 && $size <= $max * 1.25) {
        $factors['size'] = 0.5;
    } else {
        $factors['size'] = 0.0;
    }

    // Purpose: exact match against the space's declared purposes
    $factors['purpose'] = in_array(
        (int) $requirement['purpose_id'],
        array_map('intval', $space['purpose_ids'] ?? []),
        true
    ) ? 1.0 : 0.0;

    // Availability: inside an open range, no blocked overlap, no booking conflict
    $spaceId = (int) $space['id'];
    $factors['availability'] =
        is_within_availability($spaceId, $requirement['start_date'], $requirement['end_date'])
        && !has_booking_conflict($spaceId, $requirement['start_date'], $requirement['end_date'])
        ? 1.0 : 0.0;

    // Budget: estimated base within budget = 1.0, within +20% = 0.5, else 0
    $estimate = calculate_price($space, $requirement['start_date'], $requirement['end_date']);
    $budget   = (float) $requirement['budget'];
    if ($estimate['base_amount'] <= $budget) {
        $factors['budget'] = 1.0;
    } elseif ($estimate['base_amount'] <= $budget * 1.2) {
        $factors['budget'] = 0.5;
    } else {
        $factors['budget'] = 0.0;
    }

    // Amenities: fraction of required amenities the space provides
    $needed  = array_map('intval', $requirement['amenity_ids'] ?? []);
    $offered = array_map('intval', $space['amenity_ids'] ?? []);
    $factors['amenities'] = $needed === []
        ? 1.0
        : count(array_intersect($needed, $offered)) / count($needed);

    $total = 0.0;
    foreach ($factors as $name => $score) {
        $total += $score * MATCH_WEIGHTS[$name];
    }

    return [
        'total'    => round($total, 1),
        'factors'  => $factors,
        'estimate' => $estimate,
    ];
}
