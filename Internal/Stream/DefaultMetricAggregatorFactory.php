<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Metrics\Internal\Stream;

use AssertionError;
use Nevay\OTelSDK\Metrics\Aggregator;
use Nevay\OTelSDK\Metrics\Data\Data;
use Nevay\OTelSDK\Metrics\Data\DataPoint;
use Nevay\OTelSDK\Metrics\Internal\AttributeProcessor\AttributeProcessor;
use Nevay\OTelSDK\Metrics\Internal\Exemplar\AlwaysOffFilter;

/**
 * @template TSummary
 * @implements MetricAggregatorFactory<TSummary>
 *
 * @internal
 */
final class DefaultMetricAggregatorFactory implements MetricAggregatorFactory {

    /**
     * @param Aggregator<TSummary, Data, DataPoint> $aggregator
     */
    public function __construct(
        private readonly Aggregator $aggregator,
        private readonly AttributeProcessor $attributeProcessor,
        private readonly ?int $cardinalityLimit,
    ) {}

    public function create(): MetricAggregator {
        return new DefaultMetricAggregator($this->aggregator, $this->attributeProcessor, new AlwaysOffFilter(), static fn() => throw new AssertionError(), $this->cardinalityLimit);
    }
}
