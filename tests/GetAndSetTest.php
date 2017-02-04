<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class GetAndSetTest extends TestCase
{
    public function testReturnsNullForUnknownKeys()
    {
        $session = new SessionInstance('session');

        $result = $session->get('foobar');

        self::assertNull($result);
    }

    public function testStoresData()
    {
        $session = new SessionInstance('session');

        $session->set('foo', 'bar');

        self::assertSame('bar', $session->get('foo'));
    }

    public function testStoresDataToSession()
    {
        $session = new SessionInstance('session');

        $this->sessionHandler->shouldReceive('write')->once()
            ->with(session_id(), 'foo|' . serialize('bar'))->passthru();

        $session->set('foo', 'bar');
    }

    public function testDoesNotStoreWhenNothingChanged()
    {
        $session = new SessionInstance('session');
        $session->set('foo', 'bar');

        $this->sessionHandler->shouldNotReceive('write')
            ->with(session_id(), 'foo|' . serialize('bar'))->passthru();

        $session->set('foo', 'bar');
    }

    public function testSetCanHandleArrays()
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
