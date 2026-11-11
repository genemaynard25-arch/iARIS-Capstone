<?php

namespace App\Support;

/** Province-level groups inferred only from explicit place names in the free-text address. */
class AddressClassifier
{
    public const PROVINCES = ['Abra', 'Agusan del Norte', 'Agusan del Sur', 'Aklan', 'Albay', 'Antique', 'Apayao', 'Aurora', 'Basilan', 'Bataan', 'Batanes', 'Batangas', 'Benguet', 'Biliran', 'Bohol', 'Bukidnon', 'Bulacan', 'Cagayan', 'Camarines Norte', 'Camarines Sur', 'Camiguin', 'Capiz', 'Catanduanes', 'Cavite', 'Cebu', 'Cotabato', 'Davao de Oro', 'Davao del Norte', 'Davao del Sur', 'Davao Occidental', 'Davao Oriental', 'Dinagat Islands', 'Eastern Samar', 'Guimaras', 'Ifugao', 'Ilocos Norte', 'Ilocos Sur', 'Iloilo', 'Isabela', 'Kalinga', 'La Union', 'Laguna', 'Lanao del Norte', 'Lanao del Sur', 'Leyte', 'Maguindanao del Norte', 'Maguindanao del Sur', 'Marinduque', 'Masbate', 'Misamis Occidental', 'Misamis Oriental', 'Mountain Province', 'Negros Occidental', 'Negros Oriental', 'Northern Samar', 'Nueva Ecija', 'Nueva Vizcaya', 'Occidental Mindoro', 'Oriental Mindoro', 'Palawan', 'Pampanga', 'Pangasinan', 'Quezon', 'Quirino', 'Rizal', 'Romblon', 'Samar', 'Sarangani', 'Siquijor', 'Sorsogon', 'South Cotabato', 'Southern Leyte', 'Sultan Kudarat', 'Sulu', 'Surigao del Norte', 'Surigao del Sur', 'Tarlac', 'Tawi-Tawi', 'Zambales', 'Zamboanga del Norte', 'Zamboanga del Sur', 'Zamboanga Sibugay'];

    public const GROUPS = [...self::PROVINCES, 'Other / Unspecified province'];

    public const COLORS = ['Batangas' => '#00674F', 'Cavite' => '#339879', 'Laguna' => '#7FC4AB', 'Rizal' => '#C9E8DC', 'Quezon' => '#2558D9', 'Other / Unspecified province' => '#859791'];

    public static function colors(): array
    {
        static $colors = null;
        if ($colors !== null) {
            return $colors;
        }
        $colors = self::COLORS;
        foreach (self::PROVINCES as $province) {
            $colors[$province] ??= 'hsl('.(hexdec(substr(sha1($province), 0, 6)) % 360).', 55%, 42%)';
        }

        return $colors;
    }

    public static function classify(?string $address): string
    {
        $value = strtoupper(trim((string) $address));
        if (preg_match('/\b(QUEZON CITY|METRO MANILA|NCR)\b/', $value)) {
            return 'Other / Unspecified province';
        }
        static $patterns = null;
        if ($patterns === null) {
            $provinces = self::PROVINCES;
            usort($provinces, fn ($a, $b) => strlen($b) <=> strlen($a));
            $patterns = [];
            foreach ($provinces as $province) {
                $pattern = str_replace([' ', '\-'], ['[\s-]+', '[\s-]+'], preg_quote(strtoupper($province), '/'));
                $exclude = match ($province) {
                    'Cagayan' => '(?!\s+DE\s+ORO)', 'Isabela' => '(?!\s+CITY)', 'Cotabato' => '(?!\s+CITY)', default => ''
                };
                $patterns[$province] = '/\b'.$pattern.'\b'.$exclude.'/';
            }
        }
        foreach (array_reverse(preg_split('/[,;]/', $value)) as $component) {
            foreach ($patterns as $province => $pattern) {
                if (preg_match($pattern, trim($component))) {
                    return $province;
                }
            }
        }
        foreach (['/\bLIPA(?:\s+CITY)?\b/' => 'Batangas', '/\bBALANGA(?:\s+CITY)?\b/' => 'Bataan', '/\bCAGAYAN\s+DE\s+ORO\b/' => 'Misamis Oriental', '/\bCOMPOSTELA\s+VALLEY\b/' => 'Davao de Oro'] as $pattern => $province) {
            if (preg_match($pattern, $value)) {
                return $province;
            }
        }

        return 'Other / Unspecified province';
    }
}
