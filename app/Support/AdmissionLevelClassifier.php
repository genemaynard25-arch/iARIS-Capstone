<?php

namespace App\Support;

/**
 * Works out level / sub_level / college from the two things the real portal export
 * gives us per applicant: the section header above their row (e.g. "COLLEGE OF
 * BUSINESS, ECONOMICS, ACCOUNTANCY, AND MANAGEMENT  (172)") and their own YEAR LEVEL
 * cell (e.g. "1st Year", "Grade 7", "Nursery", "Master's").
 *
 * The section header is the primary signal (it names the actual department/program/
 * grade-level group); YEAR LEVEL fills in the Integrated School sub_level when the
 * header alone doesn't say (and is the fallback when there's no usable header at all).
 */
class AdmissionLevelClassifier
{
    /** Strips a trailing "(123)" applicant count off a section header. */
    public static function cleanHeader(string $header): string
    {
        return trim(preg_replace('/\s*\(\d+\)\s*$/', '', $header));
    }

    /**
     * @return array{level: string, sub_level: ?string, college: ?string}
     */
    public static function classify(?string $sectionHeader, ?string $yearLevel): array
    {
        $header = strtoupper(trim((string) $sectionHeader));
        $year = strtoupper(trim((string) $yearLevel));

        if (str_contains($header, 'ETEEAP') || str_contains($year, 'ETEEAP')) {
            return ['level' => 'eteeap', 'sub_level' => null, 'college' => null];
        }

        if (str_contains($header, 'IPACE') || str_contains($header, 'I-PACE')) {
            return ['level' => 'ipace', 'sub_level' => null, 'college' => null];
        }

        if (str_contains($header, 'LAW') || str_contains($header, 'JURIS DOCTOR')) {
            return ['level' => 'law', 'sub_level' => null, 'college' => null];
        }

        if (preg_match('/\b(MASTER|DOCTORAL?|DOCTOR OF|GRADUATE)\b/', $header)
            || preg_match('/\b(MASTER|DOCTORAL?|DOCTOR OF)\b/', $year)) {
            return ['level' => 'graduate_school', 'sub_level' => null, 'college' => null];
        }

        $isSubLevel = self::isSubLevel($header) ?? self::isSubLevel($year);
        if ($isSubLevel !== null) {
            return ['level' => 'is', 'sub_level' => $isSubLevel, 'college' => null];
        }

        // Default: College. The header (minus the trailing count) is the college name.
        return [
            'level' => 'college',
            'sub_level' => null,
            'college' => $sectionHeader ? self::cleanHeader($sectionHeader) : null,
        ];
    }

    /** Grade/level text -> 'kindergarten' | 'grade_school' | 'jhs' | 'shs', or null if it isn't IS at all. */
    private static function isSubLevel(string $text): ?string
    {
        if ($text === '') {
            return null;
        }

        if (preg_match('/\b(NURSERY|PRE[\s-]?SCHOOL|KINDER(GARTEN)?)\b/', $text)) {
            return 'kindergarten';
        }

        if (preg_match('/\bSENIOR\s*HIGH\b|\bSHS\b/', $text)
            || preg_match('/\b(STEM|ABM|HUMSS|GAS|TVL|ICT|HE|AFA)\b/', $text)) {
            return 'shs';
        }

        if (preg_match('/\bJUNIOR\s*HIGH\b|\bJHS\b/', $text)) {
            return 'jhs';
        }

        if (preg_match('/\bGRADE\s*SCHOOL\b/', $text)) {
            return 'grade_school';
        }

        if (preg_match('/\bGRADE\s*(\d{1,2})\b/', $text, $m)) {
            $grade = (int) $m[1];

            return match (true) {
                $grade >= 11 => 'shs',
                $grade >= 7 => 'jhs',
                $grade >= 1 => 'grade_school',
                default => null,
            };
        }

        return null;
    }
}
