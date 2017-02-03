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
    /**
     * @var bool $init Whether the session has been started or not.
     */
    protected $init = false;
    /**
     * @var string $name The name of the session.
     */
    protected $name = '';
    /**
     * @var array $data The cache of the session data.
     */
    protected $data = [];
    /**
     * Create a new instance.
     *
     * @param string $name The name of the session
     */
    public function __construct($name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Cannot start session, no name has been specified');
        }
        $this->name = $name;
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

        session_cache_limiter(false);
        session_name($this->name);
        session_start();

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
}
