<?php

namespace NbSessions\Test;

use NbSessions\PhpWrapper;

class PhpWrapperMock extends PhpWrapper
{
    protected $sessionId = '';

    public $sessionData = [];

    public function iniGet(string $key)
    {
        return ini_get($key);
    }

    public function sessionId(string $id = null): string
    {
        if ($id) {
            $this->sessionId = $id;
        }

        return $this->sessionId;
    }

    public function sessionStart(array $options = []): bool
    {
        $_SESSION = $this->sessionData;
        return true;
    }

    public function sessionCreateId(string $prefix = '')
    {
        static $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $id = '';
        for ($i = 0; $i < 16; $i++) {
            $id .= $chars[random_int(0, 61)];
        }
        return $id;
    }

    public function sessionWriteClose(): void
    {
        $this->sessionData = $_SESSION ?? [];
    }

    public function sessionDestroy(): bool
    {
        return true;
    }

    public function setCookie(...$args): bool
    {
        return true;
    }
}
