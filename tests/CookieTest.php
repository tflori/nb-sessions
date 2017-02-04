<?php

namespace NbSessions\Test;

class CookieTest extends \PHPUnit\Framework\TestCase
{
    const SERVER_PORT = 31337;
    private static $pid;

    public static function setUpBeforeClass()
    {
        if ((!isset($_ENV["TRAVIS_PHP_VERSION"]) || $_ENV["TRAVIS_PHP_VERSION"] !== "hhvm") && self::$pid === null) {
            $command = 'php -S localhost:' . self::SERVER_PORT . ' -t tests/public';
            exec('nohup ' . $command . ' > /dev/null 2>&1 & echo $!', $output);
            self::$pid = (int)$output[0];
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

        # HHVM no longer has a built in webserver, so don't run these tests
        if (isset($_ENV["TRAVIS_PHP_VERSION"]) && $_ENV["TRAVIS_PHP_VERSION"] === "hhvm") {
            $this->markTestSkipped("No internal webserver available on HHVM for web tests");
        }

        // empty cookies
        exec('echo "" > /tmp/nbcookies');
    }


    protected static function requestWebserver($url)
    {
        $url = 'http://localhost:' . self::SERVER_PORT . '/' . ltrim($url, '/');

        $fh = fopen('/tmp/nbcurldebug', 'w');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_COOKIEJAR => '/tmp/nbcookies',
            CURLOPT_COOKIEFILE => '/tmp/nbcookies',
            CURLOPT_VERBOSE => true,
            CURLOPT_STDERR => $fh
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

                    case 'expired':
                        if ($value) {
                            self::assertLessThan(time(), $cookie['expires'], 'Expected cookie ' . $cookieName .
                                ' to be expired.');
                        } else {
                            self::assertGreaterThanOrEqual(time(), $cookie['expires'], 'Expected cookie ' .
                                $cookieName . ' not to be expired.');
                        }
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

        self::assertCookieHeader($header, 'nbsession', ['expires' => time() + 300]);
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

    public function testSessionCookieGetNotResend()
    {
        self::requestWebserver('session.php');

        list($header, $body) = self::requestWebserver('session.php');

        self::assertNotCookieHeader($header, 'nbsession');
    }

    public function testTimeLimitedCookieGetResend()
    {
        $url = 'session.php?session_cookie_lifetime=300';
        self::requestWebserver($url);

        list($header, $body) = self::requestWebserver($url);

        self::assertCookieHeader($header, 'nbsession', ['expires' => time() + 300]);
    }

    public function provideCookieParams()
    {
        return [
            []
        ];
    }

    public function testTimeLimitedCookieGetResendWithParams()
    {
        $url = 'session.php?session_cookie_lifetime=300&session_cookie_path=/&session_cookie_domain=localhost' .
            '&session_cookie_secure=false&session_cookie_httponly=false';
        self::requestWebserver($url);

        list($header, $body) = self::requestWebserver($url);

        self::assertCookieHeader($header, 'nbsession', [
            'expires' => time() + 300,
            'path' => '/',
            'domain' => 'localhost',
            'secure' => false,
            'httponly' => false,
        ]);
    }

    public function testCookieGetNotSendTwice()
    {
        list($header, $body) = self::requestWebserver('session.php?session_cookie_lifetime=300');

        self::assertCookieHeader($header, 'nbsession', ['expires' => time() + 300], 1);
    }

    public function testDestroyDeletesTheCookie()
    {
        self::requestWebserver('session.php');

        list($header, $body) = self::requestWebserver('session.php?destroy=true');

        self::assertCookieHeader($header, 'nbsession', ['expired' => true, 'value' => 'deleted']);
    }

    public function testDestroyDeletesCookieWithParams()
    {
        $uri = 'session.php?session_cookie_lifetime=300&session_cookie_path=/&session_cookie_domain=localhost';
        self::requestWebserver($uri);

        list($header, $body) = self::requestWebserver($uri . '&destroy=true');

        self::assertCookieHeader($header, 'nbsession', [
            'expired' => true,
            'value' => 'deleted',
            'path' => '/',
            'domain' => 'localhost',
        ]);
    }

    public function testDestroyAndReuse()
    {
        self::requestWebserver('session.php');

        list($header, $body) = self::requestWebserver('session.php?destroy=true&reuse=true');

        self::assertCookieHeader($header, 'nbsession', ['expired' => false, 'value' => json_decode($body)]);
    }
}
