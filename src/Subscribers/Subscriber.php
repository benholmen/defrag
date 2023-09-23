<?php

declare(strict_types=1);

namespace BenHolmen\Defrag\Subscribers;

use BenHolmen\Defrag\DefragPrinter;

abstract class Subscriber
{
    public function __construct(
        private readonly DefragPrinter $printer,
    ) {
    }

    final protected function printer(): DefragPrinter
    {
        return $this->printer;
    }
}
