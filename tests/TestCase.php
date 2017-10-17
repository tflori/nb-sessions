<?php

namespace NbSessions\Test;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration;

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

        ini_set('session.use_cookies', 1);
    }


    /**
     * Performs assertions shared by all tests of a test case. This method is
     * called before execution of a test ends and before the tearDown method.
     */
    protected function assertPostConditions()
    {
        $this->addMockeryExpectationsToAssertionCount();
        \Mockery::close();
        parent::assertPostConditions();
    }

    protected function addMockeryExpectationsToAssertionCount()
    {
        $container = \Mockery::getContainer();
        if ($container != null) {
            $count = $container->mockery_getExpectationCount();
            $this->addToAssertionCount($count);
        }
    }
}
