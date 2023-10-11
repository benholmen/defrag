<?php

namespace BenHolmen\Defrag\Subscribers;

use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;

class TestRunnerExecutionFinished extends Subscriber implements ExecutionFinishedSubscriber
{
    public function notify(ExecutionFinished $event): void
    {
        $this->printer()->testRunnerExecutionFinished($event);
    }
}
