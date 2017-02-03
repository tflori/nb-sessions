<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        if (session_status() === 2) {
            session_write_close();
        }
    }

    public function testRequiresAName()
    {
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Cannot start session, no name has been specified');

        $session = new SessionInstance('');
    }

    public function testCanBeInitialized()
    {
        $session = new SessionInstance('session');

        self::assertInstanceOf(SessionInstance::class, $session);
    }

    public function testReturnsNullForUnknownKeys()
    {
        $session = new SessionInstance('session');

        $result = $session->get('foobar');

        self::assertNull($result);
    }

    public function testDoesNotStartSessionWithoutInteraction()
    {
        $session = new SessionInstance('session');

        self::assertNotSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testStartsSessionOnFirstInteraction()
    {
        $_SESSION['foo'] = 'bar';
        $session = new SessionInstance('session');

        $session->get('foo');

        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    public function testClosesSessionAfterInitialization()
    {
        $session = new SessionInstance('session');

        $session->get('foo');

        self::assertNotSame(PHP_SESSION_ACTIVE, session_status());
    }

    public function testSetsTheSessionNameOnInit()
    {
        $session = new SessionInstance('foobar');

        $session->get('foo');

        self::assertSame('foobar', session_name());
    }

    public function testDoesNotReReadSessionWithoutChanges()
    {
        $session = new SessionInstance('session');
        $session->get('foo');
        $_SESSION['test'] = 'foobar';

        $session->get('bar');

        self::assertSame('foobar', $_SESSION['test']);
    }
}
