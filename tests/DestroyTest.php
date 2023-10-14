<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class DestroyTest extends TestCase
{
    /** @var SessionInstance */
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new SessionInstance([], $this->phpWrapper);
    }

    /** @test */
    public function removesAllData()
    {
        $session = $this->session;
        $session->set([
            'foo' => 'bar',
            'name' => 'John Doe'
        ]);

        $session->destroy();

        self::assertNull($session->get('foo'));
        self::assertNull($session->get('name'));
    }

    /** @test */
    public function removesDataFromSuperglobal()
    {
        $session = $this->session;
        $session->set('foo', 'bar');

        $session->destroy();

        self::assertSame([], $_SESSION);
    }

    /** @test */
    public function destroysTheSession()
    {
        $session = $this->session;
        $session->set('foo', 'bar');

        $this->phpWrapper->shouldReceive('sessionDestroy')->with()->once()->passthru();

        $session->destroy();
    }

    /** @test */
    public function sessionGetRestarted()
    {
        $session = $this->session;
        $session->set('foo', 'bar');
        $this->phpWrapper->shouldReceive('sessionDestroy')->with()->once()->passthru();
        $session->destroy();

        $session->set('foo', 'bar');

        self::assertSame('bar', $session->get('foo'));
    }
}
