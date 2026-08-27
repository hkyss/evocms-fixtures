<?php

declare(strict_types=1);

namespace hkyss\Fixtures\Tests\Unit;

use hkyss\Fixtures\Panel\Token;
use PHPUnit\Framework\TestCase;

class PanelTokenTest extends TestCase
{
    public function testATokenIsAcceptedOnlyForTheSessionItWasMintedFor(): void
    {
        $token = new Token('site-secret');
        $minted = $token->mint('session-one');

        $this->assertTrue($token->accepts('session-one', $minted));
        $this->assertFalse($token->accepts('session-two', $minted));
    }

    public function testATokenFromAnotherSiteIsNotAccepted(): void
    {
        $minted = (new Token('one-site'))->mint('session');

        $this->assertFalse((new Token('another-site'))->accepts('session', $minted));
    }

    public function testAnEmptyTokenIsNeverAccepted(): void
    {
        $this->assertFalse((new Token('secret'))->accepts('session', ''));
    }

    public function testOnlyTheActionsItKnowsAreAnswered(): void
    {
        foreach (['state', 'make', 'drop', 'bench'] as $action) {
            $this->assertTrue(Token::knows($action));
        }

        foreach (['', 'MAKE', 'delete', 'exec', 'state ', '../make'] as $action) {
            $this->assertFalse(Token::knows($action), $action . ' should not be answered');
        }
    }
}
