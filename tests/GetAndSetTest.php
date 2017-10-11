<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class GetAndSetTest extends TestCase
{
    /** @test */
    public function returnsNullForUnknownKeys()
    {
        $session = new SessionInstance('session');

        $result = $session->get('foobar');

        self::assertNull($result);
    }

    /** @test */
    public function storesData()
    {
        $session = new SessionInstance('session');

        $session->set('foo', 'bar');

        self::assertSame('bar', $session->get('foo'));
    }

    /** @test */
    public function storesDataToSession()
    {
        $session = new SessionInstance('session');

        $this->sessionHandler->shouldReceive('write')->once()
            ->with(session_id(), 'foo|' . serialize('bar'))->passthru();

        $session->set('foo', 'bar');
    }

    /** @test */
    public function doesNotStoreWhenNothingChanged()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $this->sessionHandler->shouldNotReceive('write')
            ->with(session_id(), 'foo|' . serialize('bar'))->passthru();

        $session->set('foo', 'bar');
    }

    /** @test */
    public function setCanHandleArrays()
    {
        $session = new SessionInstance('session');

        $this->sessionHandler->shouldReceive('write')
            ->with(session_id(), 'foo|' . serialize('bar') . 'name|' . serialize('John Doe'))->passthru();

        $session->set([
            'foo' => 'bar',
            'name' => 'John Doe'
        ]);

        self::assertSame('bar', $session->get('foo'));
        self::assertSame('John Doe', $session->get('name'));
    }
}
