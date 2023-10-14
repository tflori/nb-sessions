<?php

namespace NbSessions;

class SessionNamespace implements SessionInterface
{
    /** The prefix for this namespace.
     * @var string */
    protected $prefix;

    /** @var SessionInstance */
    protected $session;

    /**
     * @param string $name
     * @param SessionInstance $session
     */
    public function __construct($name, SessionInstance $session)
    {
        $this->session = $session;
        $this->prefix = '__' . md5($name) . '__';
    }

    public function get(string $key)
    {
        return $this->session->get($this->prefix . $key);
    }

    public function set($data, $value = null): SessionInterface
    {
        if (!is_array($data)) {
            $data = [$data => $value];
        }

        $prefixed = [];
        foreach ($data as $key => $value) {
            $prefixed[$this->prefix . $key] = $value;
        }

        $this->session->set($prefixed);

        return $this;
    }

    public function delete(string ...$keys): SessionInterface
    {
        $this->session->delete(...array_map(function ($key) {
            return $this->prefix . $key;
        }, $keys));

        return $this;
    }
}
