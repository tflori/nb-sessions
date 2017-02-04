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

    /** {@inheritdoc} */
    public function get($key)
    {
        return $this->session->get($this->prefix . $key);
    }

    /** {@inheritdoc} */
    public function set($data, $value = null)
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
}
