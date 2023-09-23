<?php

namespace BenHolmen\Defrag\Subscribers;

use PHPUnit\Event\Test\Errored;
use PHPUnit\Event\Test\ErroredSubscriber;

class TestErrored extends Subscriber implements ErroredSubscriber
{
    public function notify(Errored $event): void
    {
        $this->printer()->testCompleted($event);
    }
}
