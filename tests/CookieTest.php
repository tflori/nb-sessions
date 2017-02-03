<?php

namespace NbSessions\Test;

use PHPUnit\Framework\TestCase;

class CookieTest extends TestCase
{
    const SERVER_PORT = 31337;
    private static $pid;

    public static function setUpBeforeClass()
    {
        if (self::$pid === null) {
            $command = 'php -S localhost:' . self::SERVER_PORT . ' -t tests/public';
            exec('nohup ' . $command . ' > /dev/null 2>&1 & echo $!', $output);
            self::$pid = (int) $output[0];
            do {
                usleep(10000);
            } while (!@fsockopen('localhost', self::SERVER_PORT));

            register_shutdown_function(function () {
                echo "stopping webserver with pid " . self::$pid . "... ";
                exec('kill ' . self::$pid);
                echo "done\n";
            });
        }

        parent::setUpBeforeClass();
    }

    protected function setUp()
    {
        parent::setUp();

        // empty cookies
        exec('echo "" > /tmp/nbcookies');
    }


    protected static function requestWebserver($url)
    {
        $url = 'http://localhost:' . self::SERVER_PORT . '/' . ltrim($url, '/');

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER         => true,
            CURLOPT_COOKIEJAR      => '/tmp/nbcookies',
            CURLOPT_COOKIEFILE     => '/tmp/nbcookies',
        ]);
        $response = curl_exec($ch);

        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        return [$header, $body];
    }

    protected static function assertCookieHeader($header, $cookieName, $params = [], $count = null)
    {
        $cookies = array_filter(explode("\r\n", trim($header)), function ($header) use ($cookieName) {
            return substr($header, 0, 12) === 'Set-Cookie: ' &&
                    substr($header, 12, strlen($cookieName)) === $cookieName;
        });

        // cookie header existence
        if ($count === 0) {
            self::assertEmpty($cookies, 'Expected headers not to contain cookie ' . $cookieName . '.');
        } elseif ($count != null) {
            self::assertCount(
                $count,
                $cookies,
                'Expected headers to contain cookie ' . $cookieName . ' exactly ' . $count . ' times ' .
                'but headers contained it ' . count($cookies) . ' times.'
            );
        } else {
            self::assertNotEmpty($cookies, 'Expected header to contain cookie ' . $cookieName . '.');
        }

        // check params
        if (!empty($params) && count($cookies) > 0) {
            $cookie = call_user_func(function ($header) {
                $cookieRaw = substr($header, 12);
                $cookie = [
                    'path' => null,
                    'domain' => null,
                    'expires' => null,
                    'secure' => false,
                    'httponly' => false,
                ];

                $parts = explode(';', $cookieRaw);
                list($cookie['name'], $cookie['value']) = explode('=', trim(array_shift($parts)));

                foreach ($parts as $part) {
                    @list($key, $value) = explode('=', trim($part));
                    if ($value === null) {
                        $value = true;
                    }
                    switch (strtolower($key)) {
                        case 'expires':
                            $cookie['expires'] = strtotime($value);
                            break;

                        default:
                            $cookie[strtolower($key)] = $value;
                            break;
                    }
                }

                return $cookie;
            }, array_shift($cookies));

            foreach ($params as $param => $value) {
                switch ($param) {
                    case 'value':
                        self::assertSame($value, $cookie['value'], 'Expected cookie ' . $cookieName .
                                                                   ' to be set to ' . $value . '.');
                        break;

                    case 'path':
                        self::assertSame($value, $cookie['path'], 'Expected cookie ' . $cookieName .
                                                                  ' to be limited to path ' . $value . '.');
                        break;

                    case 'domain':
                        self::assertSame($value, $cookie['domain'], 'Expected cookie ' . $cookieName .
                                                                    ' to be limited to domain ' . $value . '.');
                        break;

                    case 'expires':
                        self::assertEquals($value, $cookie['expires'], 'Expected cookie ' . $cookieName .
                                           ' to expire at ' . date('Y-m-d H:i:s', $value) . '.', 1);
                        break;

                    case 'secure':
                        self::assertSame($value, $cookie['secure'], 'Expected cookie ' . $cookieName .
                                         ' to be limited to secure connections.');
                        break;

                    case 'httponly':
                        self::assertSame($value, $cookie['httponly'], 'Expected cookie ' . $cookieName .
                                         ' to be limited to http.');
                        break;
                }
            }
        }
    }

    protected static function assertNotCookieHeader($header, $cookieName)
    {
        self::assertCookieHeader($header, $cookieName, [], 0);
    }

    public function testSendsSessionCookieByDefault()
    {
        list($header, $body) = self::requestWebserver('session.php');

        self::assertCookieHeader($header, 'nbsession');
    }

    public function testDoesNotCookie()
    {
        list($header, $body) = self::requestWebserver('session.php?use_cookies=false');

        self::assertNotCookieHeader($header, 'nbsession');
    }

    public function testCookieValueMatchesSessionId()
    {
        list($header, $body) = self::requestWebserver('session.php');

        self::assertCookieHeader($header, 'nbsession', ['value' => json_decode($body)]);
    }

    public function testCookiePathToBeSet()
    {
        list($header, $body) = self::requestWebserver('session.php?session_cookie_path=/product');

        self::assertCookieHeader($header, 'nbsession', ['path' => '/product']);
    }

    public function testCookieDomainToBeSet()
    {
        list($header, $body) = self::requestWebserver('session.php?session_cookie_domain=example.com');

        self::assertCookieHeader($header, 'nbsession', ['domain' => 'example.com']);
    }

    public function testCookieExpiresToBeSet()
    {
        list($header, $body) = self::requestWebserver('session.php?session_cookie_lifetime=300');

        self::assertCookieHeader($header, 'nbsession', ['expires' => time()+300]);
    }

    public function testCookieSecureToBeSet()
    {
        list($header, $body) = self::requestWebserver('session.php?session_cookie_secure=true');

        self::assertCookieHeader($header, 'nbsession', ['secure' => true]);
    }

    public function testCookieHttpOnlyToBeSet()
    {
        list($header, $body) = self::requestWebserver('session.php?session_cookie_httponly=true');

        self::assertCookieHeader($header, 'nbsession', ['httponly' => true]);
    }
}
