<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class DestroyTest extends TestCase
{
    public function testRemovesAllData()
    {
        $session = new SessionInstance('session');
        $session->set([
            'foo' => 'bar',
            'name' => 'John Doe'
        ]);

        $session->destroy();

        self::assertNull($session->get('foo'));
        self::assertNull($session->get('name'));
    }

    public function testRemovesDataFromSuperglobal()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $session->destroy();

        self::assertSame([], $_SESSION);
    }

    public function testDestroysTheSession()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');
        $this->sessionHandler->shouldReceive('destroy')->with(session_id())->once()->passthru();

        $session->destroy();
    }

    public function testSessionGetRestarted()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');
        $this->sessionHandler->shouldReceive('destroy')->with(session_id())->once()->passthru();
        $session->destroy();

        $session->set('foo', 'bar');

        self::assertSame('bar', $session->get('foo'));
    }
}
