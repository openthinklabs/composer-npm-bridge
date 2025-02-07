<?php

namespace Eloquent\Composer\NpmBridge\Exception;

use Exception;
use PHPUnit\Framework\TestCase;

class NpmCommandFailedExceptionTest extends TestCase
{
    public function testException()
    {
        $cause = new Exception();
        $exception = new NpmCommandFailedException('command', 'output', $cause);

        $this->assertSame('command', $exception->command());
        $this->assertSame("Execution of `command` failed with the following output:\noutput", $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertSame($cause, $exception->getPrevious());
    }
}
