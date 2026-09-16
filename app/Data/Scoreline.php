<?php

namespace App\Data;

final readonly class Scoreline
{
    /** @param list<string>|null $teams */
    public function __construct(
        public int $id,
        public int $home_score,
        public int $away_score,
        public int $year,
        public string $stage,
        public string $decade,
        public ?array $teams,
    ) {}
}
