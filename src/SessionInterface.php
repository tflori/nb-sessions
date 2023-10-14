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
    public function get(string $key);

    /**
     * Store value(s) in session.
     *
     * @param string|array $data Either the key or a key => value array to store
     * @param mixed $value If $data is a key then store this value in session
     * @return static
     */
    public function set($data, $value = null): self;

    /**
     * Delete $keys from session
     *
     * @param string ...$keys
     * @return static
     */
    public function delete(string ...$keys): self;
}
