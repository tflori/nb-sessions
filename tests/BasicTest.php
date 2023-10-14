<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;

class BasicTest extends TestCase
{
    /** @test */
    public function getsTheNameFromIni()
    {
        $this->phpWrapper->shouldReceive('iniGet')->with('session.name')
            ->once()->andReturn('foo');

        new SessionInstance([], $this->phpWrapper);
    }

    /** @test */
    public function canBeInitialized()
    {
        $session = new SessionInstance([], $this->phpWrapper);

        self::assertInstanceOf(SessionInstance::class, $session);
    }

    /** @test */
    public function doesNotStartSessionWithoutInteraction()
    {
        $this->phpWrapper->shouldNotReceive('sessionStart');

        new SessionInstance([], $this->phpWrapper);
    }

    /** @test */
    public function doesNotStartASessionWhenNoSessionIdGiven()
    {
        $session = new SessionInstance([], $this->phpWrapper);
        $this->phpWrapper->shouldNotReceive('sessionStart');

        $session->get('foo');
    }

    /** @test */
    public function resetsSessionVariablesSetOutside()
    {
        $_SESSION['foo'] = 'bar';
        $_COOKIE['session'] = 'abc123';
        $session = new SessionInstance(['name' => 'session'], $this->phpWrapper);

        $session->get('foo');

        self::assertArrayNotHasKey('foo', $_SESSION);
    }

    /** @test */
    public function startsSessionWhenCookiePresent()
    {
        $_COOKIE['session'] = 'abc123';
        $session = new SessionInstance(['name' => 'session'], $this->phpWrapper);

        $this->phpWrapper->shouldReceive('sessionStart')->once()->andReturn(true);

        $session->get('foo');
    }

    /** @test */
    public function closesSessionAfterInitialization()
    {
        $_COOKIE['session'] = 'abc123';
        $session = new SessionInstance(['name' => 'session'], $this->phpWrapper);

        $this->phpWrapper->shouldReceive('sessionStart')->once()->andReturn(true)->ordered();
        $this->phpWrapper->shouldReceive('sessionWriteClose')->once()->andReturn(true)->ordered();

        $session->get('foo');
    }

    /** @test */
    public function storesTheSessionDataOnInitialization()
    {

        $_COOKIE['session'] = 'abc123';
        $this->phpWrapper->sessionData = ['foo' => 'bar'];
        $session = new SessionInstance(['name' => 'session'], $this->phpWrapper);

        self::assertSame('bar', $session->get('foo'));
    }

    /** @test */
    public function doesNotReReadSessionWithoutChanges()
    {
        $_COOKIE['session'] = 'abc123';
        $this->phpWrapper->sessionData = ['foo' => 'bar'];
        $session = new SessionInstance(['name' => 'session'], $this->phpWrapper);
        $foo = $session->get('foo');

        $this->phpWrapper->shouldNotReceive('sessionStart');

        $session->set('foo', $foo);
    }
}
