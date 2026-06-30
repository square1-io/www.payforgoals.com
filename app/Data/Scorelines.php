<?php

namespace App\Data;

/**
 * The Scoreline catalogue.
 *
 * Every entry is a famous football scoreline — and nothing more. Team names are,
 * regrettably, a premium feature (coming soon).
 *
 * Shape per entry:
 *   id      int     stable identifier for /match/{id}
 *   score   string  the scoreline, e.g. "7-1" (rendered with an en dash in the API)
 *   year    int     the year it happened (a small, harmless breadcrumb)
 *   stage   string  the occasion, kept deliberately vague
 *   decade  string  one of 80s|90s|00s — the Decade Pass buckets
 */
class Scorelines
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'id' => 1,
                'score' => '7-1',
                'year' => 2014,
                'stage' => 'World Cup semi-final',
                'decade' => '00s',
            ],
            [
                'id' => 2,
                'score' => '3-3',
                'year' => 2005,
                'stage' => 'Champions League final',
                'decade' => '00s',
            ],
            [
                'id' => 3,
                'score' => '4-3',
                'year' => 1996,
                'stage' => 'Premier League, April',
                'decade' => '90s',
            ],
            [
                'id' => 4,
                'score' => '2-1',
                'year' => 1999,
                'stage' => 'Champions League final, stoppage time',
                'decade' => '90s',
            ],
            [
                'id' => 5,
                'score' => '5-1',
                'year' => 2001,
                'stage' => 'World Cup qualifier, away',
                'decade' => '00s',
            ],
            [
                'id' => 6,
                'score' => '0-0',
                'year' => 1990,
                'stage' => 'World Cup knockout',
                'decade' => '90s',
            ],
            [
                'id' => 7,
                'score' => '6-1',
                'year' => 2009,
                'stage' => 'League derby, away',
                'decade' => '00s',
            ],
            [
                'id' => 8,
                'score' => '3-2',
                'year' => 1989,
                'stage' => 'Title decider, final day, last minute',
                'decade' => '80s',
            ],
            [
                'id' => 9,
                'score' => '4-1',
                'year' => 1986,
                'stage' => 'World Cup quarter-final',
                'decade' => '80s',
            ],
            [
                'id' => 10,
                'score' => '5-4',
                'year' => 1987,
                'stage' => 'Cup tie, replayed',
                'decade' => '80s',
            ],
            [
                'id' => 11,
                'score' => '2-2',
                'year' => 1998,
                'stage' => 'World Cup last 16',
                'decade' => '90s',
            ],
            [
                'id' => 12,
                'score' => '8-2',
                'year' => 2011,
                'stage' => 'League fixture',
                'decade' => '00s',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>|null
     */
    public static function find(int $id): ?array
    {
        foreach (self::all() as $entry) {
            if ($entry['id'] === $id) {
                return $entry;
            }
        }

        return null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function forDecade(string $decade): array
    {
        return array_values(array_filter(
            self::all(),
            fn (array $entry) => $entry['decade'] === $decade,
        ));
    }

    public static function random(): array
    {
        $all = self::all();

        return $all[array_rand($all)];
    }

    /**
     * The public, team-name-free representation of a scoreline — the home and
     * away goals as separate fields, names conspicuously absent.
     *
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    public static function present(array $entry): array
    {
        [$home, $away] = array_map('intval', explode('-', $entry['score']));

        return [
            'id' => $entry['id'],
            'home_score' => $home,
            'away_score' => $away,
            'year' => $entry['year'],
            'stage' => $entry['stage'],
            'decade' => $entry['decade'],
            'teams' => null,
        ];
    }
}
