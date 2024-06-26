<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Metrics\Internal\Aggregation;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Metrics\Aggregator;
use Nevay\OTelSDK\Metrics\Data\Data;
use Nevay\OTelSDK\Metrics\Data\DataPoint;
use Nevay\OTelSDK\Metrics\Data\Temporality;
use OpenTelemetry\Context\ContextInterface;

/**
 * @implements Aggregator<null, Data, DataPoint>
 *
 * @internal
 */
final class DropAggregator implements Aggregator {

    public function initialize(): mixed {
        return null;
    }

    public function record(mixed $summary, float|int $value, Attributes $attributes, ContextInterface $context, int $timestamp): void {
        // no-op
    }

    public function merge(mixed $left, mixed $right): mixed {
        return $right;
    }

    public function diff(mixed $left, mixed $right): mixed {
        return $right;
    }

    public function toDataPoint(Attributes $attributes, mixed $summary, iterable $exemplars, int $startTimestamp, int $timestamp): DataPoint {
        return new class implements DataPoint {};
    }

    public function toData(array $dataPoints, Temporality $temporality): Data {
        return new class implements Data {
            public array $dataPoints = [];
        };
    }
}
