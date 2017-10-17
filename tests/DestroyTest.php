<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class DestroyTest extends TestCase
{
    /** @test */
    public function removesAllData()
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

    /** @test */
    public function removesDataFromSuperglobal()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $session->destroy();

        self::assertSame([], $_SESSION);
    }

    /** @test */
    public function destroysTheSession()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');
        $this->sessionHandler->shouldReceive('destroy')->with(session_id())->once()->passthru();

        $session->destroy();
    }

    /** @test */
    public function sessionGetRestarted()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');
        $this->sessionHandler->shouldReceive('destroy')->with(session_id())->atLeast()->once()->passthru();
        $session->destroy();
        // in hhvm we need to define the session handler again after destroy
        session_set_save_handler($this->sessionHandler);

        $session->set('foo', 'bar');

        self::assertSame('bar', $session->get('foo'));
    }
}
