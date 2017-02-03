<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;
use PHPUnit\Framework\TestCase;

class CookieParamsTest extends TestCase
{
    public function testChangesCookieParamsOnInit()
    {
        $oldParmas = session_get_cookie_params();
        $newParams = array_merge($oldParmas, [
            'path' => '/product',
            'domain' => 'example.com',
            'lifetime' => 3600
        ]);

        $session = new SessionInstance('session', $newParams);

        $session->get('foo');

        self::assertSame($newParams, session_get_cookie_params());
    }

    public function testCookieParamsCanBePartially()
    {
        $session = new SessionInstance('session', ['path' => '/product']);

        $session->get('foo');

        self::assertSame('/product', session_get_cookie_params()['path']);
    }
}