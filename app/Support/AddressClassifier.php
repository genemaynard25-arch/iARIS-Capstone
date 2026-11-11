<?php

namespace App\Support;

/** Province-level groups inferred only from explicit place names in the free-text address. */
class AddressClassifier
{
    public const GROUPS = ['Batangas', 'Cavite', 'Laguna', 'Rizal', 'Quezon', 'Other / Unspecified province'];

    public static function classify(?string $address): string
    {
        $value = strtoupper(trim((string) $address));
        if (preg_match('/\b(QUEZON CITY|METRO MANILA|NCR)\b/', $value)) {
            return 'Other / Unspecified province';
        }
        foreach (['Batangas', 'Cavite', 'Laguna', 'Rizal', 'Quezon'] as $province) {
            if (preg_match('/\b'.strtoupper($province).'\b/', $value)) {
                return $province;
            }
        }
        if (preg_match('/\bLIPA(?: CITY)?\b/', $value)) {
            return 'Batangas';
        }

        return 'Other / Unspecified province';
    }
}
