<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Console;

final class Environment
{
    private const DEVELOPMENT = ['local', 'development', 'dev', 'testing', 'test'];

    public function __construct(
        public readonly string $name,
        public readonly bool $debug,
    ) {
    }

    public function looksLikeDevelopment(): bool
    {
        return $this->debug || in_array(strtolower($this->name), self::DEVELOPMENT, true);
    }

    public function describe(): string
    {
        return sprintf('app.env is "%s", app.debug is %s', $this->name, $this->debug ? 'on' : 'off');
    }
}
