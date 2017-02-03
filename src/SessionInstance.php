<?php

namespace NbSessions;

/**
 * Class SessionInstance
 *
 * @package NbSessions
 * @author  Thomas Flori <thflori@gmail.com>
 */
class SessionInstance
{
    /** Whether the session has been started or not.
     * @var bool $init */
    protected $init = false;

    /** The name of the session.
     * @var string $name */
    protected $name = '';

    /** The cache of the session data.
     * @var array $data */
    protected $data = [];

    /** The cookie params.
     * @var array */
    protected $cookieParams = [];

    /**
     * Create a new instance.
     *
     * @param string $name The name of the session
     * @param array $cookieParams Cookie parameters to be set before init
     */
    public function __construct($name, $cookieParams = [])
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Cannot start session, no name has been specified');
        }

        $this->name = $name;
        $this->cookieParams = array_merge(session_get_cookie_params(), $cookieParams);
    }

    /**
     * Ensure the session data is loaded into cache.
     *
     * @return void
     */
    protected function init()
    {
        if ($this->init) {
            return;
        }

        $this->init = true;

        session_set_cookie_params(
            $this->cookieParams['lifetime'],
            $this->cookieParams['path'],
            $this->cookieParams['domain'],
            $this->cookieParams['secure'],
            $this->cookieParams['httponly']
        );

        session_name($this->name);
        session_start();

        // refresh time limited cookies on each use
        if (ini_get('session.use_cookies') && $this->cookieParams['lifetime'] != 0) {

            // @codeCoverageIgnoreStart
            // headers_list() is always empty in cli - covered in CookieTest
            $cookieSent = false;
            foreach (headers_list() as $header) {
                if (substr($header, 0, 12) === 'Set-Cookie: ' &&
                    substr($header, 12, strlen($this->name)) === $this->name
                ) {
                    $cookieSent = true;
                }
            }
            // @codeCoverageIgnoreEnd

            if (!$cookieSent) {
                setcookie(
                    $this->name,
                    session_id(),
                    time() + $this->cookieParams['lifetime'],
                    $this->cookieParams['path'],
                    $this->cookieParams['domain'],
                    $this->cookieParams['secure'],
                    $this->cookieParams['httponly']
                );
            }
        }

        $this->data = $_SESSION;

        // close the session to avoid locks
        session_write_close();
    }

    /**
     * Get a value from the session data cache.
     *
     * @param string $key The name of the name to retrieve
     *
     * @return mixed
     */
    public function get($key)
    {
        $this->init();

//        if (!array_key_exists($key, $this->data)) {
            return null;
//        }

//        return $this->data[$key];
    }

    public function destroy()
    {
        $this->init();

//        # Start the session up, but ignore the error about headers already being sent
//        @session_start();
//
//        # Delete session as suggested in php docs
//        $_SESSION = [];
//
        # Remove the session cookie
        if (ini_get("session.use_cookies")) {
            setcookie(
                $this->name,
                "",
                1,
                $this->cookieParams['path'],
                $this->cookieParams['domain'],
                $this->cookieParams['secure'],
                $this->cookieParams['httponly']
            );
        }
//
//        # Destroy the session to remove all remaining session data (on server)
//        session_destroy();
//
//        # Reset the session data
//        $this->init = false;
//        $this->data = [];

        return $this;
    }
}
