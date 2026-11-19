<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Unit;

use hkyss\Fixtures\Bench\Suite;
use hkyss\Fixtures\Panel\Panel;
use PHPUnit\Framework\TestCase;

class PanelTest extends TestCase
{
    public function testItGoesInAheadOfTheClosingBodyTag(): void
    {
        $page = (new Panel('abc', 100))->injectInto('<html><body><p>hi</p></body></html>');

        $this->assertStringContainsString('<p>hi</p><div id="evofx"', $page);
        $this->assertStringEndsWith('</body></html>', $page);
    }

    public function testAPageWithoutABodyTagIsLeftAlone(): void
    {
        $this->assertSame('just text', (new Panel('abc', 100))->injectInto('just text'));
    }

    /** @dataProvider documentsThatAreNotHtml */
    public function testADocumentThatIsNotHtmlIsLeftAlone(string $contentType): void
    {
        $page = '{"note": "</body>"}';

        $this->assertSame($page, (new Panel('abc', 100))->injectInto($page, $contentType));
    }

    /** @return array<string, array{string}> */
    public static function documentsThatAreNotHtml(): array
    {
        return [
            'json' => ['application/json'],
            'xml' => ['text/xml; charset=utf-8'],
            'plain text' => ['text/plain'],
            'rss' => ['application/rss+xml'],
            'javascript' => ['application/javascript'],
        ];
    }

    /** @dataProvider htmlDocuments */
    public function testAnHtmlDocumentGetsThePanel(string $contentType): void
    {
        $page = (new Panel('abc', 100))->injectInto('<body>x</body>', $contentType);

        $this->assertStringContainsString('<div id="evofx"', $page);
    }

    /** @return array<string, array{string}> */
    public static function htmlDocuments(): array
    {
        return [
            'plain' => ['text/html'],
            'charset' => ['text/html; charset=UTF-8'],
            'upper case' => ['TEXT/HTML'],
            'xhtml' => ['application/xhtml+xml'],
        ];
    }

    public function testItGoesInAheadOfTheLastClosingBodyTagAndNotTheFirstMention(): void
    {
        $page = (new Panel('abc', 100))->injectInto('<body>talking about &lt;/body&gt; here</body>');

        $this->assertSame(1, substr_count($page, '<div id="evofx"'));
        $this->assertStringEndsWith('</body>', $page);
    }

    public function testTheTokenIsEscapedIntoTheMarkup(): void
    {
        $markup = (new Panel('a"b', 100))->render();

        $this->assertStringContainsString('data-token="a&quot;b"', $markup);
        $this->assertStringNotContainsString('data-token="a"b"', $markup);
    }

    public function testTheCeilingReachesTheMarkupSoTheFormCanSayIt(): void
    {
        $this->assertStringContainsString('data-ceiling="4321"', (new Panel('t', 4321))->render());
    }

    public function testTheStyleAndScriptTravelWithIt(): void
    {
        $markup = (new Panel('t', 1))->render();

        $this->assertStringContainsString('#evofx-pill', $markup);
        $this->assertStringContainsString("getElementById('evofx')", $markup);
    }

    public function testEveryProbeBindsOnlyNamesTheBenchCanSupply(): void
    {
        foreach (Suite::evolution('evo_') as $probe) {
            $this->assertNotSame('', $probe->name);
            $this->assertStringContainsString('`evo_', $probe->sql);
            $this->assertSame(substr_count($probe->sql, '?'), count($probe->binds));

            foreach ($probe->binds as $bind) {
                $this->assertContains($bind, ['document', 'folder', 'parent', 'alias', 'template']);
            }
        }
    }
}
