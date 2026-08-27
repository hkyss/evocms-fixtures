<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Panel;

final class Token
{
    private const ACTIONS = ['state', 'make', 'drop', 'bench'];

    public function __construct(private readonly string $secret)
    {
    }

    public function mint(string $session): string
    {
        return hash_hmac('sha256', $session, $this->secret);
    }

    public function accepts(string $session, string $offered): bool
    {
        return $offered !== '' && hash_equals($this->mint($session), $offered);
    }

    public static function knows(string $action): bool
    {
        return in_array($action, self::ACTIONS, true);
    }
}
