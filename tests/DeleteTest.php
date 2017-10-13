<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class DeleteTest extends TestCase
{
    /** @test */
    public function deletedKeysDoNotExists()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $session->delete('foo');

        self::assertNull($session->get('foo'));
        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    /** @test */
    public function deletesWorksWithMultipleKeys()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');
        $session->set('sense', 42);

        $session->delete('foo', 'sense');

        self::assertNull($session->get('foo'));
        self::assertNull($session->get('sense'));
    }

    /** @test */
    public function deletesInSessionFile()
    {
        $session = new SessionInstance('session');
        $session->set('sense', 42);
        $session->set('foo', 'bar');

        $this->sessionHandler->shouldReceive('write')
            ->with(session_id(), 'sense|i:42;')->once()->passthru();

        $session->delete('foo');
    }

    /** @test */
    public function returnsSessionInstance()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $result = $session->delete('foo');

        self::assertSame($session, $result);
    }

    /** @test */
    public function doesNotWriteWhenNothingDeleted()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $this->sessionHandler->shouldNotReceive('write')->passthru();

        $session->delete('sense');
    }

    /** @test */
    public function destorysTheSessionWhenLastKeyGotDeleted()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $this->sessionHandler->shouldReceive('destroy')->once()->passthru();

        $session->delete('foo');
    }
}
