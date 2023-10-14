<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class DeleteTest extends TestCase
{
    /** @var SessionInstance */
    protected $session;

    protected function setUp(): void
    {
        parent::setUp();
        $this->session = new SessionInstance([], $this->phpWrapper);
    }

    /** @test */
    public function deletedKeysDoNotExists()
    {
        $session = $this->session;
        $session->set('foo', 'bar');

        $session->delete('foo');

        self::assertNull($session->get('foo'));
        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    /** @test */
    public function deleteWorksWithMultipleKeys()
    {
        $session = $this->session;
        $session->set('foo', 'bar');
        $session->set('sense', 42);

        $session->delete('foo', 'sense');

        self::assertNull($session->get('foo'));
        self::assertNull($session->get('sense'));
    }

    /** @test */
    public function deletesInSessionFile()
    {
        $session = $this->session;
        $session->set('sense', 42);
        $session->set('foo', 'bar');

        $this->phpWrapper->shouldReceive('sessionWriteClose')
            ->once()->andReturnUsing(function () {
                // we expect that $_SESSION only contains "sense" when writing
                self::assertSame(['sense' => 42], $_SESSION);
            });

        $session->delete('foo');
    }

    /** @test */
    public function returnsSessionInstance()
    {
        $session = $this->session;
        $session->set('foo', 'bar');

        $result = $session->delete('foo');

        self::assertSame($session, $result);
    }

    /** @test */
    public function doesNotWriteWhenNothingDeleted()
    {
        $session = $this->session;
        $session->set('foo', 'bar');

        $this->phpWrapper->shouldNotReceive('sessionWriteClose');

        $session->delete('sense');
    }

    /** @test */
    public function destroysTheSessionWhenLastKeyGotDeleted()
    {
        $session = $this->session;
        $session->set('foo', 'bar');

        $this->phpWrapper->shouldReceive('sessionDestroy')->once()->passthru();

        $session->delete('foo');
    }

    /** @test */
    public function doesNotDestroyWhenConfigured()
    {
        $session = new SessionInstance(['destroyEmpty' => false], $this->phpWrapper);
        $session->set('foo', 'bar');

        $this->phpWrapper->shouldNotReceive('sessionDestroy');

        $session->delete('foo');
    }
}
