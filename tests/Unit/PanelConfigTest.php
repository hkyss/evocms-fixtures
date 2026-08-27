<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Unit;

use hkyss\Fixtures\Panel\Config;
use PHPUnit\Framework\TestCase;

class PanelConfigTest extends TestCase
{
    public function testItIsShutUnlessTheValueIsGated(): void
    {
        foreach ([false, true, 1, 'on', 'yes', '1', null, ''] as $value) {
            $config = Config::fromValue($value, static fn (): bool => true, 100);

            $this->assertFalse($config->open(), var_export($value, true) . ' should not open it');
        }
    }

    public function testGatedWithAGateThatSaysYesOpensIt(): void
    {
        $this->assertTrue(Config::fromValue('gated', static fn (): bool => true, 100)->open());
        $this->assertTrue(Config::fromValue('  GATED ', static fn (): bool => true, 100)->open());
    }

    public function testGatedWithoutAGateStaysShut(): void
    {
        $this->assertFalse(Config::fromValue('gated', null, 100)->open());
    }

    public function testAGateThatSaysNoKeepsItShut(): void
    {
        $this->assertFalse(Config::fromValue('gated', static fn (): bool => false, 100)->open());
    }

    public function testAGateThatAnswersAnythingButTrueKeepsItShut(): void
    {
        $this->assertFalse(Config::fromValue('gated', static fn (): mixed => 1, 100)->open());
    }

    public function testItSaysWhyItRefusedAValueSomebodyMeantToWork(): void
    {
        $config = Config::fromValue(true, static fn (): bool => true, 100);

        $this->assertSame('the panel answers to gated and to nothing else', $config->refusedBecause(true));
        $this->assertNull($config->refusedBecause(false));
        $this->assertSame(
            'gated was asked for and no gate was supplied',
            Config::fromValue('gated', null, 100)->refusedBecause('gated')
        );
    }

    public function testTheCeilingIsNeverBelowOne(): void
    {
        $this->assertSame(1, Config::fromValue('gated', null, 0)->maxDocuments);
        $this->assertSame(1, Config::fromValue('gated', null, -5)->maxDocuments);
        $this->assertSame(500, Config::fromValue('gated', null, 500)->maxDocuments);
    }

    public function testOffIsOff(): void
    {
        $this->assertFalse(Config::off()->open());
    }
}
