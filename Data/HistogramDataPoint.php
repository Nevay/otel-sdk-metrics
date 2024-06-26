<?php

declare(strict_types=1);

namespace Nevay\OTelSDK\Metrics\Data;

use Nevay\OTelSDK\Common\Attributes;

final class HistogramDataPoint implements DataPoint {

    /**
     * @param list<int> $bucketCounts
     * @param list<float|int> $explicitBounds
     * @param iterable<Exemplar> $exemplars
     */
    public function __construct(
        public readonly int $count,
        public readonly float|int|null $sum,
        public readonly float|int|null $min,
        public readonly float|int|null $max,
        public readonly array $bucketCounts,
        public readonly array $explicitBounds,
        public readonly Attributes $attributes,
        public readonly int $startTimestamp,
        public readonly int $timestamp,
        public readonly iterable $exemplars = [],
    ) {}
}
