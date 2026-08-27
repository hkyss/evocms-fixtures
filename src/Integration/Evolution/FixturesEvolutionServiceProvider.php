<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Integration\Evolution;

use hkyss\Fixtures\Bench\Bench;
use hkyss\Fixtures\FixturesServiceProvider;
use hkyss\Fixtures\Generate\Generator;
use hkyss\Fixtures\Generate\Writer;
use hkyss\Fixtures\Panel\Config;
use hkyss\Fixtures\Panel\Endpoint;
use hkyss\Fixtures\Panel\Panel;
use hkyss\Fixtures\Panel\Token;
use hkyss\Fixtures\Record\Ledger;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;

class FixturesEvolutionServiceProvider extends FixturesServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        if (PHP_SAPI === 'cli') {
            return;
        }

        $config = $this->panelConfig();

        if (!$config->open()) {
            return;
        }

        /** @var Dispatcher $events */
        $events = $this->app->make('events');
        $token = new Token($this->secret());
        $session = (string) session_id();

        $events->listen('evolution.OnWebPageInit', function () use ($token, $session, $config): void {
            $this->answer($token, $session, $config);
        });

        $events->listen('evolution.OnWebPagePrerender', static function () use ($token, $session, $config): void {
            evo()->documentOutput = (new Panel($token->mint($session), $config->maxDocuments))
                ->injectInto((string) evo()->documentOutput);
        });
    }

    private function answer(Token $token, string $session, Config $config): void
    {
        $action = (string) ($_POST['__fixtures'] ?? '');

        if ($action === '' || !Token::knows($action)) {
            return;
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');

        if (!$token->accepts($session, (string) ($_POST['__fixtures_token'] ?? ''))) {
            http_response_code(403);
            echo (string) json_encode(['ok' => false, 'why' => 'This request did not come from the panel.']);
            exit;
        }

        $connection = $this->connection();
        $ledger = new Ledger($connection);
        $endpoint = new Endpoint(
            $ledger,
            new Generator($connection, $ledger, new Writer($connection, $this->chunk())),
            new Bench($connection),
            $config
        );

        $payload = array_map(static fn ($value): string => is_scalar($value) ? (string) $value : '', $_POST);

        echo (string) json_encode($endpoint->handle($action, $payload));
        exit;
    }

    private function panelConfig(): Config
    {
        $enabled = $this->setting('fixtures.panel.enabled', false);
        $gate = $this->setting('fixtures.panel.gate');

        return Config::fromValue(
            $enabled,
            is_callable($gate) ? \Closure::fromCallable($gate) : static fn (): bool => evo()->isLoggedIn('mgr'),
            (int) $this->setting('fixtures.panel.max_documents', 20000)
        );
    }

    private function connection(): Connection
    {
        $name = $this->setting('fixtures.connection');

        /** @var DatabaseManager $manager */
        $manager = $this->app->make('db');

        return $manager->connection(is_string($name) && $name !== '' ? $name : null);
    }

    private function chunk(): int
    {
        return max(50, (int) $this->setting('fixtures.chunk', 500));
    }

    // The panel's token is bound to the manager session, so it needs a secret that outlives a
    // request but never leaves the server; Evolution's site id is exactly that.
    private function secret(): string
    {
        $key = (string) (evo()->getConfig('site_id') ?? '');

        return $key !== '' ? $key : (string) (evo()->getConfig('site_sessionname') ?? 'evocms-fixtures');
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        try {
            return $this->app->make('config')->get($key, $default);
        } catch (\Throwable) {
            return $default;
        }
    }
}
