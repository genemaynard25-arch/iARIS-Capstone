<?php

namespace App\Support;

/**
 * Buckets a free-text address (as imported - "Tanauan City, Batangas", "Calamba, Laguna", ...)
 * into one of the four groups the dashboard's "Applicants by Location" chart shows. Matches on
 * substrings since the portal export has no separate city/province columns to key on.
 */
class AddressClassifier
{
    public const GROUPS = ['Lipa City', 'Batangas', 'CALABARZON', 'Others'];

    public static function classify(?string $address): string
    {
        $value = strtoupper(trim((string) $address));

        if ($value === '') {
            return 'Others';
        }

        if (str_contains($value, 'LIPA')) {
            return 'Lipa City';
        }

        if (str_contains($value, 'BATANGAS')) {
            return 'Batangas';
        }

        // The rest of CALABARZON (Region IV-A), Batangas itself already its own bucket above.
        // Quezon City is Metro Manila, not Quezon province - excluded so it doesn't match here.
        if (preg_match('/\b(CAVITE|LAGUNA|RIZAL)\b|\bQUEZON\b(?!\s*CITY)/', $value)) {
            return 'CALABARZON';
        }

        return 'Others';
    }
}
