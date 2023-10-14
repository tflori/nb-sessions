<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class GetAndSetTest extends TestCase
{
    /** @var SessionInstance */
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new SessionInstance([], $this->phpWrapper);
    }

    /** @test */
    public function returnsNullForUnknownKeys()
    {
        $session = $this->session;

        $result = $session->get('foobar');

        self::assertNull($result);
    }

    /** @test */
    public function storesData()
    {
        $session = $this->session;

        $session->set('foo', 'bar');

        self::assertSame('bar', $session->get('foo'));
    }

    /** @test */
    public function storesDataToSession()
    {
        $session = $this->session;

        $this->phpWrapper->shouldReceive('sessionWriteClose')->once()->passthru();

        $session->set('foo', 'bar');
    }

    /** @test */
    public function doesNotStoreWhenNothingChanged()
    {
        $session = $this->session;
        $session->set('foo', 'bar');

        $this->phpWrapper->shouldNotReceive('sessionWriteClose');

        $session->set('foo', 'bar');
    }

    /** @test */
    public function setCanHandleArrays()
    {
        $session = $this->session;

        $this->phpWrapper->shouldReceive('sessionWriteClose')->once()->passthru();

        $session->set([
            'foo' => 'bar',
            'name' => 'John Doe'
        ]);

        self::assertSame('bar', $session->get('foo'));
        self::assertSame('John Doe', $session->get('name'));
    }
}
