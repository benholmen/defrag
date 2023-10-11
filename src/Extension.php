<?php

declare(strict_types=1);

namespace BenHolmen\Defrag;

use BenHolmen\Defrag\Subscribers\TestErrored;
use BenHolmen\Defrag\Subscribers\TestFailed;
use BenHolmen\Defrag\Subscribers\TestMarkedIncomplete;
use BenHolmen\Defrag\Subscribers\TestPassed;
use BenHolmen\Defrag\Subscribers\TestRunnerExecutionFinished;
use BenHolmen\Defrag\Subscribers\TestSkipped;
use BenHolmen\Defrag\Subscribers\TestSuiteExecutionStarted;
use PHPUnit\Runner\Extension\Extension as PHPUnitExtension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

class Extension implements PHPUnitExtension
{
    public function bootstrap(
        Configuration $configuration,
        Facade $facade,
        ParameterCollection $parameters
    ): void {
        if (
            $configuration->noOutput()
            || $configuration->noProgress()
        ) {
            return;
        }

        $defragPrinter = new DefragPrinter;

        $facade->registerSubscribers(
            new TestSuiteExecutionStarted($defragPrinter),
            new TestRunnerExecutionFinished($defragPrinter),
            new TestErrored($defragPrinter),
            new TestFailed($defragPrinter),
            new TestPassed($defragPrinter),
            new TestSkipped($defragPrinter),
            new TestMarkedIncomplete($defragPrinter),
        );

        $facade->replaceProgressOutput();
    }
}
