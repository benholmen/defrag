<?php

namespace BenHolmen\Defrag\Traits;

use Laravel\Prompts\Concerns\Cursor;
use Laravel\Prompts\Output\ConsoleOutput;

trait WritesOutput
{
    use Cursor;

    private $lastOutput;

    private function writeOutput($output): void
    {
        $lines = explode(PHP_EOL, $output);

        if ($this->lastOutput) {
            $this->moveCursor(0, -count($lines));
        }

        $lastIndex = -1;
        foreach (array_diff_assoc($lines, $this->lastOutput ?? []) as $index => $line) {
            $this->moveCursor(0, $index - $lastIndex - 1);
            $lastIndex = $index;

            $this->writeDirectly($line . PHP_EOL);
        }

        $this->lastOutput = $lines;

        $this->moveCursor(0, count($lines) - $lastIndex - 1);
    }

    protected static function writeDirectly(string $message): void
    {
        (new ConsoleOutput)->writeDirectly($message);
    }
}
