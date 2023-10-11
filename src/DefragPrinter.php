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

    private float $percentDiskUsed = 42 / 100;

    private Collection $disk;

    private int $width = 85;

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
                \PHPUnit\Event\Test\MarkedIncomplete::class => Sector::FAILED,
                \PHPUnit\Event\Test\Skipped::class => Sector::FAILED,
            }
        );
    }

    public function testRunnerExecutionFinished($event): void
    {
        $this->completeTest();

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
            // $unusedIndex = $this->disk->search(Sector::UNUSED);
            $unusedIndex = $this->disk
                ->filter(fn ($sector) => $sector === Sector::UNUSED)
                ->keys()
                ->filter(fn ($index) => $index < $this->testCount)
                ->random();
            $this->disk->put($unusedIndex, Sector::WRITING);
        }

        $this->testsCompleted++;

        $this->writeAll();
    }

    private function initHdd(): void
    {
        $this->disk = collect()
            ->pad($this->testCount - 1, Sector::PENDING)
            ->pad(ceil($this->testCount / $this->percentDiskUsed), Sector::UNUSED)
            ->shuffle()
            ->prepend(Sector::UNMOVABLE);
    }

    private function writeAll(): void
    {
        $action = $this->testCount === $this->testsCompleted
            ? 'Complete'
            : collect(['Reading...', 'Writing...', 'Updating FAT...'])->random();

        $this->writeOutput(
            "\e[48;5;15m\e[107m\e[1m  Optimize "
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
        return str_repeat("\e{$this->defaultColors} \e[39;49m", $n);
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
            floor($this->testsCompleted / $this->testCount * 100),
            3,
            ' ',
            STR_PAD_LEFT
        );

        $progressBarWidth = $this->boxWidth;
        $progressBar = str_repeat('█', (int) ceil($this->testsCompleted / $this->testCount * $progressBarWidth))
            . str_repeat('░', floor(($this->testCount - $this->testsCompleted) / $this->testCount * $progressBarWidth));

        $blockRatio = str_pad(
            '1 block = 1 test',
            29,
            ' ',
        );

        $used = (Sector::USED)->formatted();
        $unused = (Sector::UNUSED)->formatted();
        $reading = (Sector::READING)->formatted();
        $writing = (Sector::WRITING)->formatted();
        $bad = (Sector::BAD)->formatted();
        $unmovable = (Sector::UNMOVABLE)->formatted();

        return
              "{$this->defaultColors}┌──────────────── \e[33mStatus\e[97m ────────────────┐ "
            . "{$this->defaultColors}┌──────────────── \e[33mLegend\e[97m ────────────────┐\e[39;49m" . PHP_EOL
            . "{$this->defaultColors}│ Cluster {$cluster}                    {$percent}% │ "
            . "│ {$used}{$this->defaultColors} - Used           {$unused}{$this->defaultColors} - Unused          │\e[39;49m" . PHP_EOL
            . "{$this->defaultColors}│ {$progressBar} │ "
            . "│ {$reading}{$this->defaultColors} - Reading        {$writing}{$this->defaultColors} - Writing         │\e[39;49m" . PHP_EOL
            . "{$this->defaultColors}│ {$elapsedTime} │ "
            . "│ {$bad}{$this->defaultColors} - Bad            {$unmovable}{$this->defaultColors} - Unmovable       │\e[39;49m" . PHP_EOL
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
