<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Metrics\Internal;

use Amp\Cancellation;
use Amp\Pipeline\DisposedException;
use Amp\Pipeline\Queue;
use Nevay\OTelSDK\Metrics\MetricFilter;
use Nevay\OTelSDK\Metrics\MetricProducer;
use Revolt\EventLoop;
use function count;

final class MultiMetricProducer implements MetricProducer {

    /** @var list<MetricProducer> */
    public array $metricProducers = [];

    public function produce(?MetricFilter $metricFilter = null, ?Cancellation $cancellation = null): iterable {
        if (!$this->metricProducers) {
            return [];
        }

        $queue = new Queue();
        $pending = count($this->metricProducers);
        $handler = static function(MetricProducer $metricProducer, ?MetricFilter $metricFilter, ?Cancellation $cancellation, Queue $queue) use (&$pending): void {
            try {
                if (!$queue->isDisposed()) {
                    foreach ($metricProducer->produce($metricFilter, $cancellation) as $metric) {
                        $queue->push($metric);
                    }
                }
            } catch (DisposedException) {
            } finally {
                if (!--$pending) {
                    $queue->complete();
                }
            }
        };
        foreach ($this->metricProducers as $metricProducer) {
            EventLoop::queue($handler, $metricProducer, $metricFilter, $cancellation, $queue);
        }

        return $queue->iterate();
    }
}
