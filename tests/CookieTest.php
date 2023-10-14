<?php

namespace NbSessions\Test;

use NbSessions\SessionInstance;
use Mockery as m;
use Hamcrest\Matchers;

class CookieTest extends TestCase
{
    /** @test */
    public function setsACookieWithSessionName()
    {
        $session = new SessionInstance(['name' => 'foo_session'], $this->phpWrapper);

        $this->phpWrapper->shouldReceive('setCookie')
            ->with('foo_session', Matchers::matchesPattern('/^[a-zA-Z0-9]{16}$/'), m::andAnyOtherArgs())
            ->once()->andReturn(true);

        $session->set('foo', 'bar');
    }

    /** @test */
    public function doesNotStartASessionWhenCookieNotSet()
    {
        $session = new SessionInstance(['name' => 'foo_session'], $this->phpWrapper);

        $this->phpWrapper->shouldNotReceive('setCookie');

        $session->get('foo');
    }

    /** @test */
    public function getsTheSessionIdFromTheCookie()
    {
        $_COOKIE['foo_session'] = 'abc123';
        $session = new SessionInstance(['name' => 'foo_session'], $this->phpWrapper);

        $this->phpWrapper->shouldReceive('sessionId')->with('abc123')->once();

        $session->get('foo');
    }

    /** @test */
    public function getsTheCookieParametersFromOptions()
    {
        $session = new SessionInstance([
            'name' => 'foo_session',
            'cookie_lifetime' => 3600,
            'cookie_path' => '/admin/',
            'cookie_domain' => '.example.com',
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Strict',
        ], $this->phpWrapper);

        $this->phpWrapper->shouldReceive('setCookie')
            ->withArgs(function ($key, $value, $options) {
                if ($key === 'foo_session') {
                    self::assertNotEmpty($value);

                    self::assertArrayHasKey('expires', $options);
                    self::assertEqualsWithDelta(time()+3600, $options['expires'], 1);

                    self::assertArrayHasKey('path', $options);
                    self::assertSame('/admin/', $options['path']);

                    self::assertArrayHasKey('domain', $options);
                    self::assertSame('.example.com', $options['domain']);

                    self::assertArrayHasKey('secure', $options);
                    self::assertSame(true, $options['secure']);

                    self::assertArrayHasKey('httponly', $options);
                    self::assertSame(true, $options['httponly']);

                    self::assertArrayHasKey('samesite', $options);
                    self::assertSame('Strict', $options['samesite']);

                    return true;
                }
                return false;
            })->once()->andReturn(true);

        $session->set('foo', 'bar');
    }

    /** @test */
    public function fallsBackToCookieParametersFromSessionConfig()
    {
        $session = new SessionInstance([
            'name' => 'foo_session',
        ], $this->phpWrapper);

        $this->phpWrapper->shouldReceive('setCookie')
            ->withArgs(function ($key, $value, $options) {
                if ($key === 'foo_session') {
                    self::assertNotEmpty($value);

                    self::assertArrayHasKey('expires', $options);
                    if (ini_get('session.cookie_lifetime') > 0) {
                        self::assertEqualsWithDelta(time()+ini_get('session.cookie_lifetime'), $options['expires'], 1);
                    } else {
                        self::assertSame(0, $options['expires']);
                    }

                    self::assertArrayHasKey('path', $options);
                    self::assertSame(ini_get('session.cookie_path'), $options['path']);

                    self::assertArrayHasKey('domain', $options);
                    self::assertSame(ini_get('session.cookie_domain'), $options['domain']);

                    self::assertArrayHasKey('secure', $options);
                    self::assertSame(ini_get('session.cookie_secure'), $options['secure']);

                    self::assertArrayHasKey('httponly', $options);
                    self::assertSame(ini_get('session.cookie_httponly'), $options['httponly']);

                    self::assertArrayHasKey('samesite', $options);
                    self::assertSame(ini_get('session.cookie_samesite'), $options['samesite']);

                    return true;
                }
                return false;
            })->once()->andReturn(true);

        $session->set('foo', 'bar');
    }

    /** @test */
    public function sessionCookieGetsNotResend()
    {
        $_COOKIE['foo_session'] = 'abc123';
        $session = new SessionInstance([
            'name' => 'foo_session',
            'cookie_lifetime' => 0,
        ], $this->phpWrapper);

        $this->phpWrapper->shouldNotReceive('setCookie');

        $session->set('foo', 'bar');
    }

    /** @test */
    public function timeLimitedCookieGetsResend()
    {
        $_COOKIE['foo_session'] = 'abc123';
        $session = new SessionInstance([
            'name' => 'foo_session',
            'cookie_lifetime' => 300,
        ], $this->phpWrapper);

        $this->phpWrapper->shouldReceive('setCookie')
            ->with('foo_session', 'abc123', m::andAnyOtherArgs())
            ->once()->andReturn(true);

        $session->set('foo', 'bar');
    }

    /** @test */
    public function destroyDeletesTheCookie()
    {
        $_COOKIE['foo_session'] = 'abc123';
        $session = new SessionInstance([
            'name' => 'foo_session',
        ], $this->phpWrapper);
        $session->set('foo', 'bar');

        $this->phpWrapper->shouldReceive('setCookie')
            ->with('foo_session', '', m::andAnyOtherArgs())
            ->once()->andReturn(true);

        $session->destroy();
    }

    /** @test */
    public function reusingADestroyedSessionCreatesANewSessionId()
    {
        $_COOKIE['foo_session'] = 'abc123';
        $session = new SessionInstance([
            'name' => 'foo_session',
        ], $this->phpWrapper);
        $session->set('foo', 'bar');
        $session->destroy();

        $this->phpWrapper->shouldReceive('sessionCreateId')->andReturn('xyz098');
        $this->phpWrapper->shouldReceive('setCookie')
            ->with('foo_session', 'xyz098', m::andAnyOtherArgs())
            ->once()->andReturn(true);

        $session->set('foo', 'bar');
    }
}
