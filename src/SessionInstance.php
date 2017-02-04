<?php

namespace NbSessions;

/**
 * Class SessionInstance
 *
 * @package NbSessions
 * @author  Thomas Flori <thflori@gmail.com>
 */
class SessionInstance implements SessionInterface
{
    /** Whether the session has been started or not.
     * @var bool $init
     */
    protected $init = false;

    /** Wether the session has been destroyed or not.
     * @var bool */
    protected $destroyed = false;

    /** The name of the session.
     * @var string $name
     */
    protected $name = '';

    /** The cache of the session data.
     * @var array $data
     */
    protected $data = [];

    /** The cookie params.
     * @var array
     */
    protected $cookieParams = [];

    /** The created SessionNamespaces
     * @var SessionNamespace[]
     */
    protected $namespaces = [];

    /**
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
     * Create a new namespaced section of this session to avoid clashes.
     *
     * @param string $name The namespace of the session
     *
     * @return SessionNamespace
     */
    public function getNamespace($name)
    {
        if (!isset($this->namespaces[$name])) {
            $this->namespaces[$name] = new SessionNamespace($name, $this);
        }

        return $this->namespaces[$name];
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
        if (ini_get('session.use_cookies')) {
            $sendCookie = false;
            if ($this->cookieParams['lifetime'] != 0) {
                $sendCookie = true;
            } elseif ($this->destroyed) {
                $this->destroyed = false;
                $sendCookie = true;
            }

            if ($sendCookie) {
                $this->removePreviousSessionCookie();
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

    /** {@inheritdoc} */
    public function get($key)
    {
        $this->init();

        if (!array_key_exists($key, $this->data)) {
            return null;
        }

        return $this->data[$key];
    }

    /** {@inheritdoc} */
    public function set($data, $value = null)
    {
        $this->init();

        // convert parameter use to array usage
        if (!is_array($data)) {
            $data = [$data => $value];
        }

        // Check that at least one value has been changed before starting up the session
        $changed = false;
        foreach ($data as $key => $val) {
            if ($this->get($key) !== $val) {
                $changed = true;
                break;
            }
        }

        if (!$changed) {
            return $this;
        }

        // Whenever a key is set, we need to start the session up again to store it.
        // When session_start is called it attempts to send the cookie to the browser with the session id in.
        // However if some output has already been sent then this will fail, this is why we suppress errors.
        @session_start();
        foreach ($data as $key => $val) {
            $_SESSION[$key] = $val;
        }
        $this->data = $_SESSION;
        session_write_close();

        return $this;
    }

    public function destroy()
    {
        $this->init();

        // Start the session up, but ignore the error about headers already being sent
        @session_start();

        // Remove the session cookie
        if (ini_get("session.use_cookies")) {
            $this->removePreviousSessionCookie();
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

        session_destroy();
        $_SESSION = [];
        $this->init = false;
        $this->destroyed = true;
        $this->data = [];

        return $this;
    }

    protected function removePreviousSessionCookie()
    {
        // Remove the cookie from session_start()
        $headers = headers_list();
        header_remove();
        $sessionCookie = 'Set-Cookie: ' . $this->name . '=';
        foreach ($headers as $header) {
            // @codeCoverageIgnoreStart
            // headers_list() is always empty in cli - covered in CookieTest
            if (strncmp($header, $sessionCookie, strlen($sessionCookie)) !== 0) {
                header($header);
            }
            // @codeCoverageIgnoreEnd
        }
    }
}
