<?php

declare(strict_types=1);

namespace Zandu\Platform\Operations;

final class OperationalMetrics
{
    /** @var array<string,int|float> */
    private array $values = [
        'outbox_pending_count' => 0,
        'outbox_oldest_pending_age' => 0,
        'outbox_publish_failures' => 0,
        'worker_retry_count' => 0,
        'dead_letter_count' => 0,
    ];

    public function setGauge(string $name, int|float $value): void
    {
        $this->assertKnown($name);
        $this->values[$name] = $value;
    }

    public function increment(string $name): void
    {
        $this->assertKnown($name);
        ++$this->values[$name];
    }

    /** @return array<string,int|float> */
    public function snapshot(): array
    {
        return $this->values;
    }

    private function assertKnown(string $name): void
    {
        if (!array_key_exists($name, $this->values)) {
            throw new \InvalidArgumentException('Unknown operational metric.');
        }
    }
}
