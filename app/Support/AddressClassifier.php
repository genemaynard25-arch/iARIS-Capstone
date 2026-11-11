<?php

namespace App\Support;

/** Province-level groups inferred only from explicit place names in the free-text address. */
class AddressClassifier
{
    public const GROUPS = ['Batangas', 'Cavite', 'Laguna', 'Rizal', 'Quezon', 'Other / Unspecified province'];

    public const COLORS = ['Batangas' => '#00674F', 'Cavite' => '#339879', 'Laguna' => '#7FC4AB', 'Rizal' => '#C9E8DC', 'Quezon' => '#2558D9', 'Other / Unspecified province' => '#859791'];

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
