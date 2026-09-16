<?php

namespace App\Data;

final readonly class TrialResult
{
    public function __construct(
        public string $tier,
        public Scoreline $scoreline,
        public string $note,
    ) {}
}
