<?php

namespace NbSessions;

/**
 * Class Session
 *
 * @package NbSessions
 * @author  Thomas Flori <thflori@gmail.com>
 * @codeCoverageIgnore Simple singleton forward
 */
class Session
{
    /** @var SessionInstance */
    protected static $instance;

    public static $name = 'nbs-session';

    public static function __callStatic($method, $args)
    {
        return call_user_func_array([static::getInstance(), $method], $args);
    }

    /**
     * @return SessionInstance
     */
    protected function getInstance()
    {
        if (!static::$instance) {
            static::$instance = new Session(static::$name);
        }

        return static::$instance;
    }

    protected function __construct()
    {
    }
}
