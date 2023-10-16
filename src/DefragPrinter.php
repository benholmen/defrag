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
            $this->testCount = $event->testSuite()->count();
            $this->init();
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

    private function init(): void
    {
        $this->startTime = microtime(true);

        $this->initHdd();

        $this->hddWidth = $this->width - 4;
        $this->boxWidth = floor($this->width / 2) - 4;

        $this->totalHeight = 2 // top
            + ceil($this->disk->count() / $this->hddWidth) // hdd
            + 7 // status, legend
            + 1; // bottom

        $this->writeAll();

        $this->hideCursor();
    }

    private function completeTest($sector = Sector::PASSED): void
    {
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
                    ->random(),
                Sector::READING
            );

            // find the first unused sector and set it to writing
            $unusedIndex = $this->disk
                ->filter(fn ($sector) => $sector === Sector::UNUSED)
                ->keys()
                ->shuffle()
                ->sort(fn ($index) => $index > $this->testCount)
                ->first();
            $this->disk->put($unusedIndex, Sector::WRITING);
        }

        $this->testsCompleted++;

        $this->writeAll();
    }

    private function initHdd(): void
    {
        $this->disk = collect()
            ->pad($this->testCount - 1, Sector::PENDING)
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
        $gridWidth = $this->width - 4;

        return $this->disk
            ->map(fn ($sector) => $sector->formatted())
            ->chunk($gridWidth)
            ->map(fn ($row) => $this->bgBlock(2)
                . $row->implode('')
                . $this->bgBlock(2 + $gridWidth - $row->count())
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

        $blockRatio = str_pad(
            '1 block = 1 test',
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
              "{$this->defaultColors}┌──────────────── \e[33mStatus\e[97m ────────────────┐ "
            . "{$this->defaultColors}┌──────────────── \e[33mLegend\e[97m ────────────────┐\e[39;49m" . PHP_EOL
            . "{$this->defaultColors}│ Cluster {$cluster}                    {$percent}% │ "
            . "│ {$passed}{$this->defaultColors} - Passed         {$failed}{$this->defaultColors} - Failed          │\e[39;49m" . PHP_EOL
            . "{$this->defaultColors}│ {$progressBar} │ "
            . "│ {$reading}{$this->defaultColors} - Reading        {$writing}{$this->defaultColors} - Writing         │\e[39;49m" . PHP_EOL
            . "{$this->defaultColors}│ {$elapsedTime} │ "
            . "│ {$skipped}{$this->defaultColors} - Skipped        {$unmovable}{$this->defaultColors} - Unmovable       │\e[39;49m" . PHP_EOL
            . "{$this->defaultColors}│            Full Optimization           │ "
            . "│ Drive C: {$blockRatio} │\e[39;49m" . PHP_EOL
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
