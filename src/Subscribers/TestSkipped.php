<?php

namespace BenHolmen\Defrag\Subscribers;

use PHPUnit\Event\Test\Skipped;
use PHPUnit\Event\Test\SkippedSubscriber;

class TestSkipped extends Subscriber implements SkippedSubscriber
{
    public function notify(Skipped $event): void
    {
        $this->printer()->testCompleted($event);
    }
}
