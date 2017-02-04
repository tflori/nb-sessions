<?php

namespace NbSessions\Test;

class SessionHandler extends \SessionHandler
{
    /** @var mixed[][] */
    protected $data;

    /** @var string[] */
    protected $raw;

    public function close()
    {
        return true;
    }

    public function destroy($session_id)
    {
        if (isset($this->raw[$session_id])) {
            unset($this->raw[$session_id]);
            unset($this->data[$session_id]);
        }
        return true;
    }

    public function gc($maxlifetime)
    {
    }

    public function open($save_path, $session_name)
    {
        return true;
    }

    public function read($session_id)
    {
        return isset($this->raw[$session_id]) ? $this->raw[$session_id] : '';
    }

    public function write($session_id, $session_data)
    {
        $this->raw[$session_id] = $session_data;

        $data = [];
        $offset = 0;
        while ($offset < strlen($session_data)) {
            if (!strstr(substr($session_data, $offset), "|")) {
                throw new \Exception("invalid data, remaining: " . substr($session_data, $offset));
            }
            $pos = strpos($session_data, "|", $offset);
            $key = substr($session_data, $offset, $pos - $offset);
            $offset = $pos + 1;
            $value = unserialize(substr($session_data, $offset));
            $data[$key] = $value;
            $offset += strlen(serialize($value));
        }
        $this->data[$session_id] = $data;

        return true;
    }
}
