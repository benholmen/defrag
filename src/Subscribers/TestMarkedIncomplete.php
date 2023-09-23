<?php

namespace BenHolmen\Defrag\Subscribers;

use PHPUnit\Event\Test\MarkedIncomplete;
use PHPUnit\Event\Test\MarkedIncompleteSubscriber;

class TestMarkedIncomplete extends Subscriber implements MarkedIncompleteSubscriber
{
    public function notify(MarkedIncomplete $event): void
    {
        $this->printer()->testCompleted($event);
    }
}
