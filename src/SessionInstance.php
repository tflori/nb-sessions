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
    /** Whether the session has been started.
     * @var bool */
    protected $init = false;

    /** Weather the cookie got sent
     * @var bool */
    protected $cookieSent = false;

    /** The name of the session.
     * @var string $name */
    protected $name = '';

    /** The cache of the session data.
     * @var array $data */
    protected $data = [];

    /** The created SessionNamespaces
     * @var SessionNamespace[] */
    protected $namespaces = [];

    /** @var PhpWrapper */
    protected $php;

    /** @var array */
    protected $options = [];

    /** @var string */
    protected $sessionId = null;

    public function __construct(array $options = [], PhpWrapper $php = null)
    {
        $this->php = $php ?? new PhpWrapper();

        $options['cookie_lifetime'] = $options['cookie_lifetime'] ??
            $this->php->iniGet('session.cookie_lifetime') ?? 0;
        $options['cookie_path'] = $options['cookie_path'] ??
            $this->php->iniGet('session.cookie_path') ?? '/';
        $options['cookie_domain'] = $options['cookie_domain'] ??
            $this->php->iniGet('session.cookie_domain') ?? '';
        $options['cookie_secure'] = $options['cookie_secure'] ??
            $this->php->iniGet('session.cookie_secure') ?? false;
        $options['cookie_httponly'] = $options['cookie_httponly'] ??
            $this->php->iniGet('session.cookie_httponly') ?? true;
        $options['cookie_samesite'] = $options['cookie_samesite'] ??
            ($this->php->iniGet('session.cookie_samesite') ?: '');
        $options['destroyEmpty'] = $options['destroyEmpty'] ?? true;

        $this->options = $options;
        $this->name = $options['name'] ?? $this->php->iniGet('session.name');
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

    public function get(string $key)
    {
        $this->init();

        return array_key_exists($key, $this->data) ? $this->data[$key] : null;
    }

    public function set($data, $value = null): SessionInterface
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

        $this->updateSession($data);

        return $this;
    }

    public function delete(string ...$keys): SessionInterface
    {
        $this->init();

        $keys = func_get_args();

        $keyExists = false;
        foreach ($keys as $key) {
            if (array_key_exists($key, $this->data)) {
                $keyExists = true;
                $this->data[$key] = null;
            }
        }

        if (!$keyExists) {
            return $this;
        }

        $this->updateSession($this->data);

        return $this;
    }

    /**
     * Destroy the session
     *
     * Delete the session data from memory and from storage. Delete the cookie if used and reset the session object.
     *
     * @return static
     */
    public function destroy(): self
    {
        $this->startSession();
        $this->php->sessionDestroy();

        $this->sendCookie(true);

        $_SESSION = [];
        $this->data = $_SESSION;
        $this->init = false;
        $this->sessionId = null;

        return $this;
    }

    /**
     * Reload the session data from save handler
     *
     * @return static
     */
    public function refresh(): self
    {
        $this->updateSession();
        return $this;
    }

    /**
     * Ensure the session data is loaded into cache.
     *
     * @return void
     */
    protected function init(): void
    {
        // run once
        if ($this->init) {
            return;
        }
        $this->init = true;

        if (empty($_COOKIE[$this->name])) {
            return;
        }

        $this->updateSession();
    }

    protected function sendCookie($delete = false)
    {
        if ($this->cookieSent && !$delete ||
            !$delete && isset($_COOKIE[$this->name]) && $this->options['cookie_lifetime'] == 0
        ) {
            return;
        }

        $id = $this->sessionId;
        $time = $this->options['cookie_lifetime'] > 0 ? time() + $this->options['cookie_lifetime'] : 0;

        // Remove the session cookie
        if ($delete) {
            $id = "";
            $time = 1;
            unset($_COOKIE[$this->name]);
        } else {
            $this->cookieSent = true;
        }

        $this->php->setCookie(
            $this->name,
            $id,
            [
                'expires' => $time,
                'path' => $this->options['cookie_path'],
                'domain' => $this->options['cookie_domain'],
                'secure' => $this->options['cookie_secure'],
                'httponly' => $this->options['cookie_httponly'],
                'samesite' => $this->options['cookie_samesite'],
            ]
        );
    }

    protected function updateSession(array $data = [])
    {
        if ($this->sessionId === null) {
            $this->sessionId = $_COOKIE[$this->name] ?? $this->php->sessionCreateId();
            $this->php->sessionId($this->sessionId);
        }

        // Whenever a key is set, we need to start the session up again to store it.
        $this->startSession(); // @todo what if we can't start the session?

        foreach ($data as $key => $val) {
            if ($val === null) {
                unset($_SESSION[$key]);
                // destroy the session when empty
                if (empty($_SESSION) && $this->options['destroyEmpty']) {
                    $this->destroy();
                    return;
                }
            } else {
                $_SESSION[$key] = $val;
            }
        }
        $this->data = $_SESSION ?? [];

        $this->sendCookie();

        // write and close to avoid locks
        $this->php->sessionWriteClose();
    }

    protected function startSession(): bool
    {
        return $this->php->sessionStart([
            'use_cookies' => false, // we will take care of cookies
            // everything else is configured via ini settings
        ]);
    }
}
