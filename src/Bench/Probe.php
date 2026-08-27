<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Bench;

final class Probe
{
    /** @param list<string> $binds */
    public function __construct(
        public readonly string $name,
        public readonly string $sql,
        public readonly array $binds,
        public readonly string $reads,
    ) {
    }
}
