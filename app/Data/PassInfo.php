<?php

namespace App\Data;

final readonly class PassInfo
{
    public function __construct(
        public string $scope,
        public int $grantsPerPurchase,
        public ?string $session,
        public string $note,
    ) {}
}
