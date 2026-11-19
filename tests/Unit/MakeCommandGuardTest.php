<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Unit;

use hkyss\Fixtures\Console\Commands\MakeCommand;
use Illuminate\Container\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class MakeCommandGuardTest extends TestCase
{
    private string|false $appEnv;

    protected function setUp(): void
    {
        $this->appEnv = getenv('APP_ENV');
        putenv('APP_ENV');
    }

    protected function tearDown(): void
    {
        putenv($this->appEnv === false ? 'APP_ENV' : 'APP_ENV=' . $this->appEnv);
    }

    public function testAProductionLookingSiteIsRefusedWithoutForce(): void
    {
        [$code, $output] = $this->invoke(['app.env' => 'production', 'app.debug' => false], []);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('does not look like a development one', $output);
        $this->assertStringContainsString('--force', $output);
        $this->assertStringNotContainsString('About to write', $output);
    }

    public function testNoInteractionDoesNotBypassIt(): void
    {
        [$code, $output] = $this->invoke(['app.env' => 'production', 'app.debug' => false], [], interactive: false);

        $this->assertSame(1, $code);
        $this->assertStringContainsString('--force', $output);
    }

    public function testForceLetsItThrough(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reached the database');

        $this->invoke(['app.env' => 'production', 'app.debug' => false], ['--force' => true]);
    }

    public function testADevelopmentSiteNeedsNoForceToGetPastIt(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('reached the database');

        $this->invoke(['app.env' => 'local', 'app.debug' => false], []);
    }

    public function testTheProcessEnvironmentOverridesTheConfigName(): void
    {
        putenv('APP_ENV=local');

        $this->expectException(RuntimeException::class);

        $this->invoke(['app.env' => 'production', 'app.debug' => false], []);
    }

    /**
     * @param  array<string, mixed> $settings
     * @param  array<string, mixed> $options
     * @return array{int, string}
     */
    private function invoke(array $settings, array $options, bool $interactive = true): array
    {
        $container = new class () extends Container {
            public function runningUnitTests(): bool
            {
                return true;
            }
        };
        $container->instance('config', new class ($settings) {
            /** @param array<string, mixed> $settings */
            public function __construct(private readonly array $settings)
            {
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->settings[$key] ?? $default;
            }
        });
        $container->bind('db', static function (): never {
            throw new RuntimeException('reached the database');
        });

        $command = new MakeCommand();
        $command->setLaravel($container);

        $input = new ArrayInput($options + ['--documents' => '10']);
        $input->setInteractive($interactive);
        $output = new BufferedOutput();
        $code = $command->run($input, $output);

        return [$code, $output->fetch()];
    }
}
