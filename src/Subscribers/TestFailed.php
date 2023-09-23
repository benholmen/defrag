<?php

namespace BenHolmen\Defrag\Subscribers;

use PHPUnit\Event\Test\Failed;
use PHPUnit\Event\Test\FailedSubscriber;

class TestFailed extends Subscriber implements FailedSubscriber
{
    public function notify(Failed $event): void
    {
        $this->printer()->testCompleted($event);
    }
}
