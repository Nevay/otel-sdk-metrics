<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Metrics;

use Closure;

final class View {

    /**
     * @param string|null $name name to use instead of original instrument name
     * @param string|false|null $unit unit to use instead of original instrument
     *        unit, false to ignore instrument unit
     * @param string|false|null $description description to use instead of
     *        original description, false to ignore instrument description
     * @param AttributeProcessor|false|null $attributeProcessor attribute
     *        processor, false to disable attribute preprocessing
     * @param Aggregation|false|null $aggregation aggregation, false to drop the
     *        metric
     * @param Closure(Aggregator): ExemplarReservoir|null $exemplarReservoir
     *        exemplar reservoir
     * @param int<0, max>|null $cardinalityLimit aggregation cardinality limit
     */
    public function __construct(
        public readonly ?string $name = null,
        public readonly string|false|null $unit = null,
        public readonly string|false|null $description = null,
        public readonly AttributeProcessor|false|null $attributeProcessor = null,
        public readonly Aggregation|false|null $aggregation = null,
        public readonly Closure|null $exemplarReservoir = null,
        public readonly int|null $cardinalityLimit = null,
    ) {}

    public static function drop(): View {
        static $drop = new View(aggregation: false);
        return $drop;
    }
}
