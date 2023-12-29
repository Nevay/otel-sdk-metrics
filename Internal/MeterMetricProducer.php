<?php declare(strict_types=1);
namespace Nevay\OtelSDK\Metrics\Internal;

use Amp\Cancellation;
use Nevay\OtelSDK\Metrics\AggregationResolver;
use Nevay\OtelSDK\Metrics\CardinalityLimitResolver;
use Nevay\OtelSDK\Metrics\Data\Descriptor;
use Nevay\OtelSDK\Metrics\Data\Metric;
use Nevay\OtelSDK\Metrics\Data\Temporality;
use Nevay\OtelSDK\Metrics\ExemplarReservoirResolver;
use Nevay\OtelSDK\Metrics\Internal\Registry\MetricCollector;
use Nevay\OtelSDK\Metrics\Internal\Stream\MetricStream;
use Nevay\OtelSDK\Metrics\MetricFilter;
use Nevay\OtelSDK\Metrics\MetricFilterResult;
use Nevay\OtelSDK\Metrics\MetricProducer;
use Nevay\OtelSDK\Metrics\TemporalityResolver;
use function array_keys;
use function count;

final class MeterMetricProducer implements MetricProducer {

    private readonly MetricCollector $collector;
    public readonly TemporalityResolver $temporalityResolver;
    public readonly AggregationResolver $aggregationResolver;
    public readonly ExemplarReservoirResolver $exemplarReservoirResolver;
    public readonly CardinalityLimitResolver $cardinalityLimitResolver;

    /** @var array<int, list<MetricStreamSource>> */
    private array $sources = [];
    private ?array $streamIds = null;

    public function __construct(
        MetricCollector $collector,
        TemporalityResolver $temporalityResolver,
        AggregationResolver $aggregationResolver,
        ExemplarReservoirResolver $exemplarReservoirResolver,
        CardinalityLimitResolver $cardinalityLimitResolver,
    ) {
        $this->collector = $collector;
        $this->temporalityResolver = $temporalityResolver;
        $this->aggregationResolver = $aggregationResolver;
        $this->exemplarReservoirResolver = $exemplarReservoirResolver;
        $this->cardinalityLimitResolver = $cardinalityLimitResolver;
    }

    public function unregisterStream(int $streamId): void {
        if (!isset($this->sources[$streamId])) {
            return;
        }

        unset($this->sources[$streamId]);
        $this->streamIds = null;
    }

    public function registerMetricSource(int $streamId, Descriptor $descriptor, MetricStream $stream, Temporality $temporality): void {
        $this->sources[$streamId][] = new MetricStreamSource(
            $descriptor,
            $stream,
            $stream->register($temporality),
        );
        $this->streamIds = null;
    }

    public function produce(?MetricFilter $metricFilter = null, ?Cancellation $cancellation = null): iterable {
        $sources = $metricFilter
            ? $this->applyMetricFilter($this->sources, $metricFilter)
            : $this->sources;
        $streamIds = count($sources) === count($this->sources)
            ? $this->streamIds ??= array_keys($this->sources)
            : array_keys($sources);

        $this->collector->collectAndPush($streamIds, $cancellation);
        unset($streamIds, $metricFilter, $cancellation);

        foreach ($sources as $streamSources) {
            foreach ($streamSources as $source) {
                yield new Metric(
                    $source->descriptor,
                    $source->stream->collect($source->reader),
                );
            }
        }
    }

    /**
     * @param array<int, list<MetricStreamSource>> $sources
     * @return array<int, array<int, MetricStreamSource>>
     */
    private function applyMetricFilter(array $sources, MetricFilter $filter): array {
        foreach ($sources as $streamId => $streamSources) {
            foreach ($streamSources as $sourceId => $source) {
                $result = $filter->testMetric(
                    $source->descriptor->instrumentationScope,
                    $source->descriptor->name,
                    $source->descriptor->instrumentType,
                    $source->descriptor->unit,
                );

                if ($result === MetricFilterResult::Accept) {
                    // no-op
                }
                if ($result === MetricFilterResult::AcceptPartial) {
                    $sources[$streamId][$sourceId] = new MetricStreamSource(
                        $source->descriptor,
                        new FilteredMetricStream($source->descriptor, $source->stream, $filter),
                        $source->reader,
                    );
                }
                if ($result === MetricFilterResult::Drop) {
                    unset($sources[$streamId][$sourceId]);
                    if (!$sources[$streamId]) {
                        /** @noinspection PhpConditionAlreadyCheckedInspection */
                        unset($sources[$streamId]);
                    }
                }
            }
        }

        return $sources;
    }
}
