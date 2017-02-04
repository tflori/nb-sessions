<?php

namespace NbSessions;

interface SessionInterface
{
    /**
     * Get value with $key from the session.
     *
     * @param string $key
     * @return mixed
     */
    public function get($key);

    /**
     * Store value(s) in session.
     *
     * @param string|array $data Either the key or a key => value array to store
     * @param mixed $value If $data is a key then store this value in session
     * @return self
     */
    public function set($data, $value = null);
}
