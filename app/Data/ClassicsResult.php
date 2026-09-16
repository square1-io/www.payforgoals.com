<?php

namespace App\Data;

final readonly class ClassicsResult
{
    /** @param list<Scoreline> $scorelines */
    public function __construct(
        public string $tier,
        public string $decade,
        public int $count,
        public array $scorelines,
        public PassInfo $pass,
    ) {}
}
