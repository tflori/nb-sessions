<?php

namespace NbSessions;

/**
 * Class Session
 *
 * @package NbSessions
 * @author  Thomas Flori <thflori@gmail.com>
 * @codeCoverageIgnore Simple singleton forward
 *
 * @method static SessionNamespace getNamespace(string $name)
 * @method static mixed get(string $key)
 * @method static SessionInstance set(string|array $key, mixed $value = null)
 * @method static SessionInstance delete(string $key)
 * @method static SessionInstance destroy()
 */
class Session
{
    /** @var SessionInstance */
    protected static $instance;

    public static function __callStatic($method, $args)
    {
        return call_user_func_array([static::getInstance(), $method], $args);
    }

    protected function getInstance(): SessionInstance
    {
        if (!static::$instance) {
            static::$instance = new SessionInstance(new PhpWrapper());
        }

        return static::$instance;
    }

    protected function __construct()
    {
    }
}
