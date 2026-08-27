<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Panel;

final class Panel
{
    public function __construct(
        private readonly string $token,
        private readonly int $maxDocuments,
    ) {
    }

    public function injectInto(string $page): string
    {
        $markup = $this->render();
        $closing = strripos($page, '</body>');

        if ($closing === false) {
            return $page . $markup;
        }

        return substr($page, 0, $closing) . $markup . substr($page, $closing);
    }

    public function render(): string
    {
        return sprintf(
            '<div id="evofx" data-token="%s" data-ceiling="%d">'
            . '<button id="evofx-pill" type="button"><i></i>fixtures<b>panel</b></button>'
            . '<div id="evofx-panel" hidden>'
            . '<header><h3>Fixtures</h3><button type="button" aria-label="Close">&times;</button></header>'
            . '<nav>'
            . '<button type="button" data-tab="batches" aria-selected="true">Batches</button>'
            . '<button type="button" data-tab="generate" aria-selected="false">Generate</button>'
            . '<button type="button" data-tab="bench" aria-selected="false">Benchmark</button>'
            . '</nav><div class="body"></div></div></div>'
            . '<style>%s</style><script>%s</script>',
            htmlspecialchars($this->token, ENT_QUOTES),
            $this->maxDocuments,
            self::asset('panel.css'),
            self::asset('panel.js')
        );
    }

    private static function asset(string $name): string
    {
        $path = __DIR__ . '/../Assets/' . $name;

        return is_file($path) ? (string) file_get_contents($path) : '';
    }
}
