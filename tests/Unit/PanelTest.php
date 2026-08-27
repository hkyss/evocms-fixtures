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

    public function testAPageWithoutABodyTagGetsItAtTheEnd(): void
    {
        $page = (new Panel('abc', 100))->injectInto('just text');

        $this->assertStringStartsWith('just text<div id="evofx"', $page);
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
