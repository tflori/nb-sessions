<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class BasicTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        if (session_status() === 2) {
            session_write_close();
        }
    }

    /** @test */
    public function requiresAName()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Cannot start session, no name has been specified');

        new SessionInstance('');
    }

    /** @test */
    public function canBeInitialized()
    {
        $session = new SessionInstance('session');

        self::assertInstanceOf(SessionInstance::class, $session);
    }

    /** @test */
    public function doesNotStartSessionWithoutInteraction()
    {
        new SessionInstance('session');

        self::assertNotSame(PHP_SESSION_ACTIVE, session_status());
    }

    /** @test */
    public function doesNotStartASessionWhenNoSessionIdGiven()
    {
        $session = new SessionInstance('session');
        if (!empty(session_id())) {
            $this->markTestSkipped('Session already started in previous tests');
        }

        $session->get('foo');

        self::assertEmpty(session_id());
    }

    /** @test */
    public function resetsSessionVariablesSetOutside()
    {
        $_SESSION['foo'] = 'bar';
        $session = new SessionInstance('session');

        $session->get('foo');

        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    /** @test */
    public function startsSessionWhenCookiePresent()
    {
        $_COOKIE['session'] = 'abc123';
        $session = new SessionInstance('session');

        $session->get('foo');

        self::assertNotEmpty(session_id());
    }

    /** @test */
    public function closesSessionAfterInitialization()
    {
        $session = new SessionInstance('session');

        $session->get('foo');

        self::assertNotSame(PHP_SESSION_ACTIVE, session_status());
    }

    /** @test */
    public function setsTheSessionNameOnInit()
    {
        $session = new SessionInstance('foobar');

        $session->get('foo');

        self::assertSame('foobar', session_name());
    }

    /** @test */
    public function doesNotReReadSessionWithoutChanges()
    {
        $session = new SessionInstance('session');
        $session->get('foo');
        $_SESSION['test'] = 'foobar';

        $session->get('bar');

        self::assertSame('foobar', $_SESSION['test']);
    }
}
