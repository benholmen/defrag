<?php

declare(strict_types=1);

namespace BenHolmen\Defrag\Tests;

use PHPUnit\Framework\TestCase;

class DefragTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        usleep(rand(4_000, 100_000));
    }

    /** @test */
    public function it_answers_the_question()
    {
        $this->assertEquals((int) 42, 'The Answer to the Ultimate Question of Life, The Universe, and Everything');
    }

    /** @test */
    public function it_has_an_incomplete_test()
    {
        $this->markTestIncomplete();
    }

    /** @test */
    public function it_skips_a_test()
    {
        $this->markTestSkipped('This test has not been implemented yet.');
    }

    /**
     * @test
     *
     * @dataProvider muchData
     */
    public function it_runs_many_tests()
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function paradoxically_some_tests_fail()
    {
        $this->assertTrue(false);
    }

    /** @test */
    public function it_throws_an_exception()
    {
        throw new \Exception('I meant to do that.');
    }

    public static function muchData(): array
    {
        return array_map(
            fn () => [],
            range(1, 495),
        );
    }
}
