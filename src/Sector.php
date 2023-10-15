<?php

namespace BenHolmen\Defrag;

enum Sector
{
    case BAD;
    case ERROR;
    case FAILED;
    case INCOMPLETE;
    case PASSED;
    case PENDING;
    case READING;
    case SKIPPED;
    case UNMOVABLE;
    case UNUSED;
    case USED;
    case WRITING;

    public function formatted(): string
    {
        return match ($this) {
            self::BAD => "\e[40m\e[31mB\e[39;49m",
            self::ERROR => "\e[40m\e[31mE\e[39;49m",
            self::FAILED => "\e[40m\e[31mF\e[39;49m",
            self::INCOMPLETE => "\e[48:5:15m\e[38:5:0mI\e[39;49m",
            self::PENDING => "\e[48:5:15m\e[38:5:0m•\e[39;49m",
            self::PASSED => "\e[48:5:77m\e[38:5:16m•\e[39;49m",
            self::READING => "\e[48:5:21mr\e[39;49m",
            self::SKIPPED => "\e[48:5:15m\e[38:5:0mS\e[39;49m",
            self::UNMOVABLE => "\e[48:5:69mX\e[39;49m",
            self::UNUSED => "\e[48:5:69m░\e[39;49m",
            self::USED => "\e[48:5:11m\e[38:5:69m•\e[39;49m",
            self::WRITING => "\e[48:5:21mW\e[39;49m",
        };
    }
}
