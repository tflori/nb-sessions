<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class CookieParamsTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        parent::tearDownAfterClass();
        session_set_cookie_params(0, null, null, false, false);
    }

    /** @test */
    public function changesCookieParamsOnInit()
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

    /** @test */
    public function cookieParamsCanBePartially()
    {
        $session = new SessionInstance('session', ['path' => '/product']);

        $session->get('foo');

        self::assertSame('/product', session_get_cookie_params()['path']);
    }
}
