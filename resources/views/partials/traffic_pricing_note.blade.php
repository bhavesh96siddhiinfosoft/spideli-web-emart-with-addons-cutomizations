{{-- Point 103: how traffic changed the price - the minutes with traffic that
     were charged, and the busy-time / busy-zone multiplier - shown before the
     customer pays. Nothing when the price is the distance alone. --}}
@php
    $tpNote = is_array($trafficPricing ?? null) ? $trafficPricing : null;
    $tpParts = [];
    if ($tpNote) {
        if (($tpNote['perMinute'] ?? 0) > 0 && ($tpNote['estimatedMinutes'] ?? 0) > 0) {
            $tpParts[] = trans('lang.traffic_note_minutes', ['minutes' => round($tpNote['estimatedMinutes'])]);
        }
        if (($tpNote['multiplier'] ?? 1) > 1) {
            $tpParts[] = trans(($tpNote['multiplierReason'] ?? '') === 'busy_zone' ? 'lang.traffic_note_busy_zone' : 'lang.traffic_note_busy_hour',
                ['multiplier' => rtrim(rtrim(number_format((float) $tpNote['multiplier'], 2, '.', ''), '0'), '.')]);
        }
    }
@endphp
@if(!empty($tpParts))
    <div class="traffic-pricing-note mb-2">
        <small class="text-muted">{{ implode(' · ', $tpParts) }}</small>
    </div>
@endif
