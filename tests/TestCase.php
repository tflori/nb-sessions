<?php

namespace NbSessions\Test;

use Mockery\Mock;

class TestCase extends \PHPUnit\Framework\TestCase
{
    /** @var Mock|\SessionHandler */
    protected $sessionHandler;

    protected function setUp()
    {
        parent::setUp();
        $this->sessionHandler = \Mockery::mock(SessionHandler::class)->makePartial();
        session_set_save_handler($this->sessionHandler);
    }

    protected function tearDown()
    {
        parent::tearDown();
        \Mockery::close();
    }
}
