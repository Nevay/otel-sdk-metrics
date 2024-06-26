<?php declare(strict_types=1);
namespace Nevay\OTelSDK\Metrics\Exemplar;

use Nevay\OTelSDK\Common\Attributes;
use Nevay\OTelSDK\Metrics\ExemplarReservoir;
use Nevay\OTelSDK\Metrics\Internal\Exemplar\BucketStorage;
use OpenTelemetry\Context\ContextInterface;
use Random\Engine\PcgOneseq128XslRr64;
use Random\RandomException;
use Random\Randomizer;
use function mt_rand;
use const PHP_VERSION_ID;

final class SimpleFixedSizeExemplarReservoir implements ExemplarReservoir {

    private readonly ?Randomizer $randomizer;
    private readonly BucketStorage $storage;
    private readonly int $size;

    private int $measurements = 0;

    public function __construct(int $size, ?Randomizer $randomizer = null) {
        if (PHP_VERSION_ID >= 80200) {
            $randomizer ??= new Randomizer(new PcgOneseq128XslRr64());
        }

        $this->randomizer = $randomizer;
        $this->storage = new BucketStorage($size);
        $this->size = $size;
    }

    public function offer(float|int $value, Attributes $attributes, ContextInterface $context, int $timestamp): void {
        if ($this->measurements < $this->size) {
            $bucket = $this->measurements;
        } else {
            try {
                $bucket = $this->randomizer?->getInt(0, $this->measurements);
            } catch (RandomException) {}
            $bucket ??= mt_rand(0, $this->measurements);
        }
        $this->measurements++;
        if ($bucket < $this->size) {
            $this->storage->store($bucket, $value, $attributes, $context, $timestamp);
        }
    }

    public function collect(Attributes $dataPointAttributes): array {
        $this->measurements = 0;

        return $this->storage->collect($dataPointAttributes);
    }
}
