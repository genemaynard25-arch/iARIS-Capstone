<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/** Keeps encrypted-name searching on the server without storing a plaintext search index. */
class ApplicantSearch
{
    public static function apply(Builder $query, string $search, bool $alphabetical = false): void
    {
        if ($search === '' && ! $alphabetical) {
            return;
        }
        $needle = mb_strtolower(trim($search));
        $matches = [];
        (clone $query)->reorder()->select(['id', 'first_name', 'last_name', 'middle_initial', 'reference_number'])
            ->chunkById(500, function ($rows) use (&$matches, $needle) {
                foreach ($rows as $a) {
                    [$first, $mi] = ApplicantRecords::nameParts($a);
                    $name = mb_strtolower(trim($a->last_name.', '.$first.($mi ? ' '.$mi.'.' : '')));
                    if ($needle === '' || str_contains($name, $needle) || str_contains(mb_strtolower($a->reference_number), $needle)) {
                        $matches[$a->id] = $name;
                    }
                }
            });
        if (! $matches) {
            $query->whereRaw('1 = 0');

            return;
        }
        $query->whereIntegerInRaw('id', array_keys($matches));
        if ($alphabetical) {
            asort($matches, SORT_NATURAL | SORT_FLAG_CASE);
            $query->orderByRaw('FIELD(id,'.implode(',', array_keys($matches)).')');
        }
    }
}
