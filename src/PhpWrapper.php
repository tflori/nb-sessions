<?php

namespace NbSessions;

/** @codeCoverageIgnore This class is to mock all the php calls this library is doing */
class PhpWrapper
{
    public function iniGet(string $key)
    {
        return ini_get($key);
    }

    public function sessionId(string $id = null): string
    {
        return session_id($id);
    }

    public function sessionStart(array $options = []): bool
    {
        return session_start($options);
    }

    public function sessionCreateId(string $prefix = '')
    {
        return session_create_id($prefix);
    }

    public function sessionWriteClose(): void
    {
        session_write_close();
    }

    public function sessionDestroy(): bool
    {
        return session_destroy();
    }

    public function setCookie(...$args)
    {
        return setcookie(...$args);
    }
}
