<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Unit;

use hkyss\Fixtures\Console\Environment;
use PHPUnit\Framework\TestCase;

class EnvironmentTest extends TestCase
{
    public function testProductionWithoutDebugIsNotDevelopment(): void
    {
        $this->assertFalse((new Environment('production', false))->looksLikeDevelopment());
    }

    /** @dataProvider developmentNames */
    public function testTheUsualDevelopmentNamesAreDevelopment(string $name): void
    {
        $this->assertTrue((new Environment($name, false))->looksLikeDevelopment());
    }

    /** @return array<string, array{string}> */
    public static function developmentNames(): array
    {
        return ['local' => ['local'], 'development' => ['development'], 'dev' => ['dev'], 'testing' => ['testing'], 'case' => ['Local']];
    }

    public function testDebugOnMakesItDevelopmentWhateverTheNameSays(): void
    {
        $this->assertTrue((new Environment('production', true))->looksLikeDevelopment());
    }

    public function testAnUnknownNameIsNotDevelopment(): void
    {
        $this->assertFalse((new Environment('staging', false))->looksLikeDevelopment());
        $this->assertFalse((new Environment('', false))->looksLikeDevelopment());
    }

    public function testItSaysWhatItSaw(): void
    {
        $this->assertSame('app.env is "production", app.debug is off', (new Environment('production', false))->describe());
        $this->assertSame('app.env is "local", app.debug is on', (new Environment('local', true))->describe());
    }
}
