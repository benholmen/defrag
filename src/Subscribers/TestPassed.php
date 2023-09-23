<?php

namespace BenHolmen\Defrag\Subscribers;

use PHPUnit\Event\Test\Passed;
use PHPUnit\Event\Test\PassedSubscriber;

class TestPassed extends Subscriber implements PassedSubscriber
{
    public function notify(Passed $event): void
    {
        $this->printer()->testCompleted($event);
    }
}
