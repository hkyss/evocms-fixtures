<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Panel;

use Closure;

final class Config
{
    private function __construct(
        private readonly bool $gated,
        private readonly ?Closure $gate,
        public readonly int $maxDocuments,
    ) {
    }

    public static function fromValue(mixed $enabled, ?Closure $gate, int $maxDocuments): self
    {
        $gated = is_string($enabled) && strtolower(trim($enabled)) === 'gated';

        return new self($gated, $gate, max(1, $maxDocuments));
    }

    public static function off(): self
    {
        return new self(false, null, 1);
    }

    public function open(): bool
    {
        if (!$this->gated || $this->gate === null) {
            return false;
        }

        return ($this->gate)() === true;
    }

    public function refusedBecause(mixed $enabled): ?string
    {
        if ($enabled === false || $enabled === null || $enabled === '') {
            return null;
        }

        if (is_string($enabled) && strtolower(trim($enabled)) === 'gated') {
            return $this->gate === null ? 'gated was asked for and no gate was supplied' : null;
        }

        return 'the panel answers to gated and to nothing else';
    }
}
