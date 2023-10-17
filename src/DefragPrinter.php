<?php

namespace BenHolmen\Defrag;

use BenHolmen\Defrag\Traits\WritesOutput;
use Illuminate\Support\Collection;
use Laravel\Prompts\Concerns\Colors;
use Laravel\Prompts\Concerns\Cursor;

class DefragPrinter
{
    use Colors;
    use Cursor;
    use WritesOutput;

    private int $testCount;

    private int $testsCompleted = 0;

    private int $sectorRatio;

    private float $startTime;

    private Collection $disk;

    private int $width = 85;

    private int $sectorCount = 932;

    private int $hddWidth;

    private int $boxWidth;

    private int $totalHeight;

    private string $defaultColors = "\e[48:5:69;38:5:15m";

    public function testSuiteExecutionStarted($event): void
    {
        if (empty($this->testCount)) {
            $this->init($event->testSuite()->count());
        }
    }

    public function testCompleted($event): void
    {
        $this->completeTest(
            match ($event::class) {
                \PHPUnit\Event\Test\Passed::class => Sector::PASSED,
                \PHPUnit\Event\Test\Errored::class => Sector::ERROR,
                \PHPUnit\Event\Test\Failed::class => Sector::FAILED,
                \PHPUnit\Event\Test\MarkedIncomplete::class => Sector::INCOMPLETE,
                \PHPUnit\Event\Test\Skipped::class => Sector::SKIPPED,
            }
        );
    }

    public function testRunnerExecutionFinished($event): void
    {
        $this->shutdown();
    }

    private function init(int $testCount): void
    {
        $this->startTime = microtime(true);

        $this->testCount = $testCount;
        $this->sectorRatio = ceil($this->testCount / $this->sectorCount);

        $this->initHdd();

        $this->hddWidth = $this->width - 4;
        $this->boxWidth = floor($this->width / 2) - 4;

        $this->totalHeight = 2 // top
            + ceil($this->disk->count() / $this->hddWidth) // hdd
            + 7 // status, legend
            + 1; // bottom

        $this->hideCursor();

        $this->writeAll();
    }

    private function completeTest($sector = Sector::PASSED): void
    {
        if (
            $sector === Sector::PASSED
            && $this->testsCompleted < $this->testCount
            && $this->testsCompleted % $this->sectorRatio
        ) {
            $this->testsCompleted++;

            $this->writeAll();

            return;
        }

        // find the reading block and set it to unused status
        $readingIndex = $this->disk->search(Sector::READING);
        if ($readingIndex !== false) {
            $this->disk->put($readingIndex, Sector::UNUSED);
        }

        // find the writing block and set it to the specified status
        $writingIndex = $this->disk->search(Sector::WRITING);
        if ($writingIndex !== false) {
            $this->disk->put($writingIndex, $sector);
        }

        // find a pending sector, if any, and set it to reading
        if ($this->disk->search(Sector::PENDING)) {
            $this->disk->put(
                $this->disk
                    ->filter(fn ($sector) => $sector === Sector::PENDING)
                    ->keys()
                    ->shuffle()
                    ->first(),
                Sector::READING
            );

            // find the first unused sector and set it to writing
            $this->disk->put(
                $this->disk
                    ->filter(fn ($sector) => $sector === Sector::UNUSED)
                    ->keys()
                    ->first(),
                Sector::WRITING
            );
        }

        $this->testsCompleted++;

        $this->writeAll();
    }

    private function initHdd(): void
    {
        $this->disk = collect()
            ->pad((int) ceil($this->testCount / $this->sectorRatio) - 2, Sector::PENDING)
            ->add(Sector::WRITING)
            ->add(Sector::READING)
            ->pad($this->sectorCount, Sector::UNUSED)
            ->shuffle()
            ->prepend(Sector::UNMOVABLE);
    }

    private function writeAll(): void
    {
        $action = $this->testCount === $this->testsCompleted
            ? 'Complete'
            : collect(['Reading...', 'Writing...', 'Updating FAT...'])->random();

        $this->writeOutput(
            "\e[107m\e[30m\e[107m\e[1m  Optimize "
            . str_repeat(' ', $this->width - 21)
            . "F1=Help   \e[39;49m\e[22m"
            . PHP_EOL

            . $this->bgBlock($this->width) . PHP_EOL

            . $this->hddOutput()

            . $this->bgBlock($this->width) . PHP_EOL

            . $this->statusOutput()

            . "\e[107m\e[91m\e[1m  {$action}"
            . str_repeat(' ', 66 - strlen($action))
            . "| PHPUnit Defrag \e[39;49m\e[22m"
        );
    }

    private function bgBlock(int $n = 1): string
    {
        return "\e{$this->defaultColors}"
            . str_repeat(' ', $n)
            . "\e[39;49m";
    }

    private function hddOutput(): string
    {
        return $this->disk
            ->map(fn ($sector) => $sector->formatted())
            ->chunk($this->hddWidth)
            ->map(fn ($row) => $this->bgBlock(2)
                . $row->implode('')
                . $this->bgBlock(2 + $this->hddWidth - $row->count())
                . PHP_EOL
            )
            ->implode('');
    }

    private function statusOutput(): string
    {
        $cluster = $this->italic(
            str_pad(
                string: $this->testsCompleted,
                length: 6,
            )
        );

        $elapsedTime = str_pad(
            'Elapsed Time: '.$this->elapsedTimeFormatted(),
            $this->boxWidth,
            ' ',
            STR_PAD_BOTH
        );

        $percent = str_pad(
            (int) floor($this->testsCompleted / $this->testCount * 100),
            3,
            ' ',
            STR_PAD_LEFT
        );

        $progressBarFilledWidth = (int) min($this->boxWidth, ceil($this->testsCompleted / $this->testCount * $this->boxWidth));
        $progressBar = str_repeat('█', $progressBarFilledWidth) . str_repeat('░', $this->boxWidth - $progressBarFilledWidth);

        $sectorRatio = str_pad(
            '1 block = ' . $this->sectorRatio . ($this->sectorRatio === 1 ? ' test' : ' tests'),
            29,
            ' ',
        );

        $passed = (Sector::PASSED)->formatted();
        $failed = (Sector::FAILED)->formatted();
        $reading = (Sector::READING)->formatted();
        $writing = (Sector::WRITING)->formatted();
        $skipped = (Sector::SKIPPED)->formatted();
        $unmovable = (Sector::UNMOVABLE)->formatted();

        return
              "{$this->defaultColors}┌──────────────── Status ────────────────┐ "
            . "{$this->defaultColors}┌──────────────── Legend ────────────────┐\e[49m" . PHP_EOL
            . "{$this->defaultColors}│ Cluster {$cluster}                    {$percent}% │ "
            . "│ {$passed}{$this->defaultColors} - Passed         {$failed}{$this->defaultColors} - Failed          │\e[49m" . PHP_EOL
            . "{$this->defaultColors}│ {$progressBar} │ "
            . "│ {$reading}{$this->defaultColors} - Reading        {$writing}{$this->defaultColors} - Writing         │\e[49m" . PHP_EOL
            . "{$this->defaultColors}│ {$elapsedTime} │ "
            . "│ {$skipped}{$this->defaultColors} - Skipped        {$unmovable}{$this->defaultColors} - Unmovable       │\e[49m" . PHP_EOL
            . "{$this->defaultColors}│            Full Optimization           │ "
            . "│ Drive C: {$sectorRatio} │\e[49m" . PHP_EOL
            . "{$this->defaultColors}└────────────────────────────────────────┘ └────────────────────────────────────────┘\e[39;49m" . PHP_EOL;
    }

    private function elapsedTimeFormatted(): string
    {
        $elapsedSeconds = (int) (microtime(true) - $this->startTime);

        $hours = floor($elapsedSeconds / 3600);
        $minutes = floor((int) ($elapsedSeconds / 60) % 60);
        $seconds = $elapsedSeconds % 60;

        return sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
    }

    private function shutdown(): void
    {
        $this->showCursor();
    }
}
