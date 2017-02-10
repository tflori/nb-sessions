<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class DeleteTest extends TestCase
{
    public function testDeletedKeysDoNotExists()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $session->delete('foo');

        self::assertNull($session->get('foo'));
        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    public function testDeletesWorksWithMultipleKeys()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');
        $session->set('sense', 42);

        $session->delete('foo', 'sense');

        self::assertNull($session->get('foo'));
        self::assertNull($session->get('sense'));
    }

    public function testDeletesInSessionFile()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $this->sessionHandler->shouldReceive('write')
            ->with(session_id(), '')->once()->passthru();

        $session->delete('foo');
    }

    public function testReturnsSessionInstance()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $result = $session->delete('foo');

        self::assertSame($session, $result);
    }

    public function testDoesNotWriteWhenNothingDeleted()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');
        $this->sessionHandler->shouldNotReceive('write')->passthru();

        $session->delete('sense');
    }
}
