<?php

namespace NbSessions\Test;

use Mockery\Adapter\Phpunit\MockeryTestCase;
use Mockery as m;

abstract class TestCase extends MockeryTestCase
{
    /** @var m\Mock|PhpWrapperMock */
    protected $phpWrapper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phpWrapper = m::mock(PhpWrapperMock::class)->makePartial();
    }
}
