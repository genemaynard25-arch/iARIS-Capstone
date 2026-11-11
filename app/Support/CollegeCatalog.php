<?php

namespace App\Support;

class CollegeCatalog
{
    public const NAMES = [
        'CITE' => 'College of Information Technology and Engineering',
        'CBEAM' => 'College of Business, Economics, Accountancy, and Management',
        'CEAS' => 'College of Education, Arts and Sciences',
        'CIHTM' => 'College of International Hospitality and Tourism Management',
        'CCJE' => 'College of Criminal Justice Education',
        'CON' => 'College of Nursing',
        'LAW' => 'College of Law',
    ];

    public static function code(?string $value): string
    {
        $normalize = fn ($name) => preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $name));
        foreach (self::NAMES as $code => $name) {
            if (in_array($normalize($value), [$normalize($code), $normalize($name)], true)) {
                return $code;
            }
        }

        return trim($value ?? '');
    }
}
