<?php

namespace BenHolmen\Defrag\Subscribers;

use PHPUnit\Event\TestRunner\Finished;
use PHPUnit\Event\TestRunner\FinishedSubscriber;

class TestRunnerFinished extends Subscriber implements FinishedSubscriber
{
    public function notify(Finished $event): void
    {
        $this->printer()->testRunnerFinished($event);
    }
}
