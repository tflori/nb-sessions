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
        // in hhvm we need to define the session handler again after destroy
        session_set_save_handler($this->sessionHandler);

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
        // in hhvm we need to define the session handler again after destroy
        session_set_save_handler($this->sessionHandler);

        $session->set('foo', 'bar');

        self::assertSame('bar', $session->get('foo'));
    }
}
