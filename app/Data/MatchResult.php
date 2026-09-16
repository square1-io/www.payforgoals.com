<?php

namespace App\Data;

final readonly class MatchResult
{
    public function __construct(
        public string $tier,
        public Scoreline $scoreline,
    ) {}
}
