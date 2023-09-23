<?php

namespace BenHolmen\Defrag\Subscribers;

use PHPUnit\Event\TestSuite\Started;
use PHPUnit\Event\TestSuite\StartedSubscriber;

class TestSuiteExecutionStarted extends Subscriber implements StartedSubscriber
{
    public function notify(Started $event): void
    {
        $this->printer()->testSuiteExecutionStarted($event);
    }
}
