<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\AuditLog;
use App\Support\AdmissionStats;
use App\Support\ApplicantRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CollegeController extends Controller
{
    /**
     * The colleges and their programs (CITE's names and totals as in the dean mockups; the other
     * colleges' programs from their Settings mockups, with placeholder numbers). Same idea as the Executive pages.
     * Later these numbers would come from the admission_stats and applicants tables.
     *
     * applicants / enrolled: one number per academic year in ExecutiveController::YEARS (the last is this year)
     * funnel: this AY — Applied, Took exam, Passed, Paid, Enrolled
     * status: applicants this AY by status — Pending, For Exam, Paid (they add up to this year's applicants)
     * paidLastYear: paid reservations last AY (for the "vs last AY" change)
     */
    private const COLLEGES = [
        'CITE' => ['name' => 'College of Information Technology and Engineering', 'programs' => [
            'BSCS' => ['name' => 'BS Computer Science'],
            'BSIT' => ['name' => 'BS Information Technology'],
            'BSCpE' => ['name' => 'BS Computer Engineering'],
            'BSEE' => ['name' => 'BS Electrical Engineering'],
            'BSECE' => ['name' => 'BS Electronics Engineering'],
            'BSIE' => ['name' => 'BS Industrial Engineering'],
            'BSEMC' => ['name' => 'BS Entertainment & Multimedia Computing'],
            'BSArch' => ['name' => 'BS Architecture'],
        ]],
        'CBEAM' => ['name' => 'College of Business, Economics, Accountancy, and Management', 'programs' => [
            'BSA' => ['name' => 'BS Accountancy'],
            'BSBA' => ['name' => 'BS Business Administration'],
            'BSEntrep' => ['name' => 'BS Entrepreneurship'],
            'BSLM' => ['name' => 'BS Legal Management'],
        ]],
        'CEAS' => ['name' => 'College of Education, Arts and Sciences', 'programs' => [
            'BSEd' => ['name' => 'Bachelor of Secondary Education'],
            'BAMMA' => ['name' => 'BA Multimedia Arts'],
            'BAComm' => ['name' => 'BA Communication'],
            'BSPsych' => ['name' => 'BS Psychology'],
            'BSBio' => ['name' => 'BS Biology'],
        ]],
        'CIHTM' => ['name' => 'College of International Hospitality and Tourism Management', 'programs' => [
            'BSHM' => ['name' => 'BS Hospitality Management'],
            'BSTM' => ['name' => 'BS Tourism Management'],
            'BSCA' => ['name' => 'BS Culinary Arts'],
        ]],
        'CCJE' => ['name' => 'College of Criminal Justice Education', 'programs' => [
            'BSCrim' => ['name' => 'BS Criminology'],
            'BSFS' => ['name' => 'BS Forensic Science'],
        ]],
        // Optional keys: 'title' — how the title reads when it isn't the code ("Dean, College of Nursing");
        // 'label' — the short name on the pages when it isn't the code; 'office' — Settings' office line;
        // 'word' — what the groups are called ('program' by default, 'level' for the Integrated School).
        'CON' => ['name' => 'College of Nursing', 'title' => 'College of Nursing', 'programs' => [
            'BSN' => ['name' => 'BS Nursing'],
        ]],
        // The College of Law (Account Management stores it as the department "College of Law"). Its applicants
        // show their degree and whether they passed the Bar.
        'LAW' => ['name' => 'College of Law', 'title' => 'College of Law', 'label' => 'College of Law', 'middle' => 'For Exam / Interview', 'programs' => [
            'JD' => ['name' => 'Juris Doctor'],
            'LLM' => ['name' => 'Master of Laws'],
            'JSD' => ['name' => 'Doctor of Juridical Science'],
        ]],
        // The Integrated School, for its Principal: the same pages by level instead of program (totals as in the mockup)
        'IS' => ['name' => 'Integrated School', 'title' => 'Integrated School', 'label' => 'Integrated School', 'subtitle' => 'Preschool to Grade 12',
            'office' => 'Integrated School · Preschool to Grade 12', 'word' => 'level', 'programs' => [
                // 'match': what the applicants table calls this level (the import says Kindergarten)
                'K' => ['name' => 'Preschool', 'match' => ['Kindergarten', 'Preschool']],
                'GS' => ['name' => 'Grade School'],
                'JHS' => ['name' => 'Junior High School'],
                'SHS' => ['name' => 'Senior High School'],
            ]],
        // The Assistant Principals: the Principal's pages for their own levels. Each group says which applicants it holds
        // (see inGroup): 'subs' = sub-levels, 'grades' = grade numbers, 'strand' = Senior High strand.
        // The numbers come from the applicants table (realPrograms), so a group needs only its name and what it holds.
        'IS_GS' => ['name' => 'Integrated School', 'title' => 'Grade School', 'label' => 'Grade School', 'subtitle' => 'Preschool to Grade 6',
            'office' => 'Integrated School · Grade School', 'word' => 'level', 'programs' => [
                // Preschool = Nursery 1, Nursery 2 and Kinder
                'PS' => ['name' => 'Preschool', 'subs' => ['kindergarten']],
                'G13' => ['name' => 'Grades 1–3', 'subs' => ['grade_school'], 'grades' => [1, 2, 3]],
                'G46' => ['name' => 'Grades 4–6', 'subs' => ['grade_school'], 'grades' => [4, 5, 6]],
            ]],
        'IS_JHS' => ['name' => 'Integrated School', 'title' => 'Junior High', 'label' => 'Junior High', 'subtitle' => 'Grades 7 to 10',
            'office' => 'Integrated School · Junior High', 'word' => 'level', 'programs' => [
                'G7' => ['name' => 'Grade 7', 'subs' => ['jhs'], 'grades' => [7]],
                'G8' => ['name' => 'Grade 8', 'subs' => ['jhs'], 'grades' => [8]],
                'G9' => ['name' => 'Grade 9', 'subs' => ['jhs'], 'grades' => [9]],
                'G10' => ['name' => 'Grade 10', 'subs' => ['jhs'], 'grades' => [10]],
            ]],
        'IS_SHS' => ['name' => 'Integrated School', 'title' => 'Senior High', 'label' => 'Senior High', 'subtitle' => 'Grades 11 and 12, by strand',
            'office' => 'Integrated School · Senior High', 'word' => 'strand', 'programs' => [
                'STEM' => ['name' => 'STEM', 'strand' => 'STEM'],
                'ABM' => ['name' => 'ABM', 'strand' => 'ABM'],
                'HUMSS' => ['name' => 'HUMSS', 'strand' => 'HUMSS'],
                'GAS' => ['name' => 'GAS', 'strand' => 'GAS'],
                'TVL' => ['name' => 'TVL', 'strand' => 'TVL'],
            ]],
        // The Graduate Programs (under CBEAM), for their Program Chair. 'level': Master's or Doctoral.
        // 'middle': what "For Exam" is called here.
        'GRAD' => ['name' => 'Graduate Programs', 'title' => 'Graduate Programs', 'label' => 'Graduate Programs', 'subtitle' => 'Graduate Programs · under CBEAM',
            'office' => 'Graduate Programs · under CBEAM', 'scope' => 'the Graduate Programs', 'middle' => 'For Exam / Interview', 'programs' => [
                'MBA' => ['name' => 'Master of Business Administration', 'level' => "Master's"],
                'MEd' => ['name' => 'Master of Education', 'level' => "Master's"],
                'MIT' => ['name' => 'Master of Information Technology', 'level' => "Master's"],
                'MPA' => ['name' => 'Master of Public Administration', 'level' => "Master's"],
                'PhD' => ['name' => 'PhD in Education', 'level' => 'Doctoral'],
            ]],
        // iPACE and ETEEAP, for the iPACE Director (who heads both). Two units: each program has a 'unit',
        // and the same program can be in both (so the codes start with I- or E-). Unit totals as in the mockup.
        // 'profiles': the Learner profile on the Briefing, per unit: [title, [[group, [[label, count], …]], …], note]
        'IPACE' => ['name' => 'Institute for Professional Advancement and Continuing Education', 'title' => 'iPACE', 'label' => 'iPACE + ETEEAP',
            'subtitle' => 'Institute for Professional Advancement and Continuing Education · with ETEEAP', 'scope' => 'iPACE and ETEEAP', 'programs' => [
                'I-BSIT' => ['unit' => 'iPACE', 'name' => 'BS Information Technology'],
                'I-BSCS' => ['unit' => 'iPACE', 'name' => 'BS Computer Science'],
                'I-BSBA' => ['unit' => 'iPACE', 'name' => 'BS Business Administration'],
                'I-BSN' => ['unit' => 'iPACE', 'name' => 'BS Nursing'],
                'I-BEED' => ['unit' => 'iPACE', 'name' => 'Bachelor of Elementary Education'],
                'E-BSIT' => ['unit' => 'ETEEAP', 'name' => 'BS Information Technology'],
                'E-BSBA' => ['unit' => 'ETEEAP', 'name' => 'BS Business Administration'],
                'E-BEED' => ['unit' => 'ETEEAP', 'name' => 'Bachelor of Elementary Education'],
                'E-BSN' => ['unit' => 'ETEEAP', 'name' => 'BS Nursing'],
                'E-BSHRM' => ['unit' => 'ETEEAP', 'name' => 'BS Hotel & Restaurant Management'],
            ], 'profiles' => [
                'iPACE' => ['Learner profile', [
                    ['Delivery mode', [['Online', 198], ['Blended', 114]]],
                    ['Working students', [['Working', 236], ['Not working', 76]]],
                ], 'Most iPACE applicants study while working, so most choose Online classes.'],
                'ETEEAP' => ['Applicant profile', [
                    ['Portfolio and competency assessment', [['Not yet submitted', 31], ['Submitted, being assessed', 38], ['Assessment cleared', 25]]],
                    ['Work experience', [['5–9 years', 38], ['10–14 years', 34], ['15 years or more', 22]]],
                    ['Highest educational attainment', [['High school graduate', 19], ['Some college', 47], ['Vocational / technical', 28]]],
                ], 'Average work experience is 11.4 years. ETEEAP requires at least 5 years.'],
            ]],
    ];

    // How a dean is greeted ("Good morning, Engr. Villafuerte"). Names as in the mockups.
    private const HONORIFICS = [
        'Cherrie Villafuerte' => 'Engr.', 'Jasmin De Silva' => 'Dr.', 'Gina Dimaano' => 'Dr.',
        'Evangeline Necio' => 'Ms.', 'Aristotle Balba' => 'Dr.', 'Margie Ebreo' => 'Dr.',
        'Kristine Anne Dimaculangan' => 'Ms.', 'Haidee Angeles' => 'Dr.', 'Erickson Martinez' => 'Dr.', 'Ma. Isabel Romero' => 'Atty.',
    ];

    /**
     * The signed-in dean's college: ['code' => 'CITE', 'name' => …, 'programs' => […]]. The IS Principal gets 'IS'.
     * Reads users.assigned_college, falling back to users.department for older rows that predate
     * that column. Anyone without either (e.g. the Program Chair) is CITE.
     */
    public static function college(): array
    {
        $user = auth()->user();
        $code = $user?->isPrincipal() ? 'IS' : ($user?->isDirector() ? 'IPACE' : ($user?->isGradChair() ? 'GRAD' : null));
        // An Assistant Principal: IS_GS, IS_JHS or IS_SHS, by the level they are assigned to
        if ($user?->isAssistantPrincipal()) {
            $code = ['jhs' => 'IS_JHS', 'shs' => 'IS_SHS'][$user->assigned_sub_level] ?? 'IS_GS';
        }
        $code ??= strtoupper((string) ($user?->assigned_college ?? $user?->department ?? ''));
        // A college can also be stored by its full name ("College of Law")
        $code = isset(self::COLLEGES[$code]) ? $code
            : (collect(self::COLLEGES)->search(fn ($c) => strtoupper($c['name']) === $code) ?: 'CITE');

        return ['code' => $code] + self::COLLEGES[$code];
    }

    public static function programOptions(): array
    {
        $catalog = collect(self::COLLEGES)->pluck('programs')->flatMap(fn ($programs) => array_column($programs, 'name'));

        return $catalog->merge(Applicant::whereNull('archived_at')->distinct()->pluck('program_or_track'))->filter()->unique()->sort()->values()->all();
    }

    private static function inCollege(Applicant $applicant, string $code): bool
    {
        if ($applicant->level !== 'college') {
            return true;
        }

        return self::matchesCollege($applicant->college ?: ApplicantRecords::collegeFor($applicant->program_or_track ?? ''), $code);
    }

    public static function matchesCollege(?string $value, string $code): bool
    {
        $normalize = fn ($name) => preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $name));
        $actual = $normalize($value);

        return in_array($actual, [$normalize($code), $normalize(self::COLLEGES[$code]['name'] ?? '')], true);
    }

    /** Where these pages live for the signed-in user: /principal for the IS Principal, /college otherwise. */
    public static function base(): string
    {
        $user = auth()->user();

        return url($user?->isPrincipalOffice() ? '/principal' : ($user?->isDirector() ? '/director' : '/college'));
    }

    // The applicants.level value a college code's records actually live under.
    private static function levelFor(string $collegeCode): string
    {
        return match ($collegeCode) {
            'IS', 'IS_GS', 'IS_JHS', 'IS_SHS' => 'is',
            'GRAD' => 'graduate_school',
            'IPACE' => 'ipace',
            'LAW' => 'law',
            default => 'college',
        };
    }

    /** Actual school years in chart order, oldest first. */
    private static function schoolYears(): array
    {
        $start = (int) substr(Applicant::max('school_year') ?? now()->format('Y'), 0, 4);

        return array_map(fn ($i) => ($start - $i).'-'.($start - $i + 1), [3, 2, 1, 0]);
    }

    private static function realPrograms(string $collegeCode, array $programs): array
    {
        $level = self::levelFor($collegeCode);
        $applicants = Applicant::whereIn('level', $collegeCode === 'IPACE' ? ['ipace', 'eteeap'] : [$level])
            ->whereNull('archived_at')->get()->filter(fn ($a) => self::inCollege($a, $collegeCode));

        if ($level === 'college' && ! auth()->user()->isProgramChair()) {
            foreach ($applicants->pluck('program_or_track')->filter()->unique() as $name) {
                if (! collect($programs)->contains(fn ($p) => in_array(mb_strtolower(trim($name)), array_map(fn ($value) => mb_strtolower(trim($value)), (array) ($p['match'] ?? $p['name'])), true))) {
                    $programs['P-'.substr(sha1($name), 0, 8)] = ['name' => $name];
                }
            }
        }

        if ($collegeCode === 'IPACE') {
            $programs = [];
            foreach (['ipace' => 'iPACE', 'eteeap' => 'ETEEAP'] as $unitLevel => $unitName) {
                $names = $applicants->where('level', $unitLevel)->pluck('program_or_track')
                    ->map(fn ($name) => trim((string) $name))->unique()->sort()->values();
                foreach ($names as $index => $name) {
                    $programs[strtoupper(substr($unitLevel, 0, 1)).'-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)] = [
                        'unit' => $unitName, 'name' => $name ?: 'Unspecified program', 'match' => $name === '' ? [null, ''] : [$name],
                    ];
                }
            }
        }
        $paidIds = array_keys(ApplicantRecords::paidFees('reservation_fee'));
        $years = self::schoolYears();

        $result = [];
        foreach ($programs as $code => $p) {
            $matched = $applicants->filter(fn ($a) => self::inGroup($a, $p));
            if ($collegeCode === 'IPACE') {
                $matched = $matched->where('level', $p['unit'] === 'ETEEAP' ? 'eteeap' : 'ipace');
            }
            $byYear = array_map(fn ($year) => $matched->where('school_year', $year), $years);
            $matched = $byYear[3];
            $submitted = $matched->count();
            $tookExam = $matched->where('admission_test_status', '!=', 'not_taken')->count();
            $passed = $matched->whereIn('admission_test_status', ['passed', 'reconsidered', 'qualified_other_degree'])->count();
            $paid = $matched->whereIn('id', $paidIds)->count();
            $completed = $matched->where('application_status', 'completed')->count();
            $pending = $matched->whereIn('application_status', ['pooling', 'not_submitted'])->count();
            $forExam = $matched->where('application_status', 'submitted')->count();

            // array_merge, not $p + [...]: array union keeps the LEFT side's value on key
            // collision, which would silently keep the old hardcoded numbers instead of these.
            $result[$code] = array_merge($p, [
                'applicants' => array_map(fn ($rows) => $rows->count(), $byYear),
                'enrolled' => array_map(fn ($rows) => $rows->where('application_status', 'completed')->count(), $byYear),
                'funnel' => [$submitted, $tookExam, $passed, $paid, $completed],
                'status' => [$pending, $forExam, $paid],
                'paidLastYear' => $byYear[2]->whereIn('id', $paidIds)->count(),
            ]);
        }

        return $result;
    }

    /**
     * Is this applicant in this group of programs / levels? A group says what it holds:
     *   'subs' (+ 'grades')  Integrated School sub-levels (kindergarten, grade_school, jhs, shs), and grade numbers
     *   'strand'             a Senior High strand (program_or_track)
     *   otherwise            program_or_track equals its name (or one of its 'match' names)
     */
    private static function inGroup(Applicant $a, array $p): bool
    {
        if (isset($p['strand'])) {
            return self::subLevelOf($a) === 'shs' && strtoupper((string) $a->program_or_track) === $p['strand'];
        }
        if (isset($p['subs'])) {
            if (! in_array(self::subLevelOf($a), $p['subs'], true)) {
                return false;
            }
            if (isset($p['grades'])) {
                preg_match('/\d+/', (string) $a->year_level, $m);

                return isset($m[0]) && in_array((int) $m[0], $p['grades'], true);
            }

            return true;
        }

        return in_array(mb_strtolower(trim((string) $a->program_or_track)), array_map(fn ($name) => mb_strtolower(trim((string) $name)), (array) ($p['match'] ?? $p['name'])), true);
    }

    // An Integrated School applicant's sub-level; older rows without one are read from the program column
    private static function subLevelOf(Applicant $a): ?string
    {
        return $a->sub_level ?: match (true) {
            $a->program_or_track === 'Kindergarten' => 'kindergarten',
            $a->program_or_track === 'Grade School' => 'grade_school',
            $a->program_or_track === 'Junior High School' => 'jhs',
            in_array(strtoupper((string) $a->program_or_track), ['STEM', 'ABM', 'HUMSS', 'GAS', 'TVL', 'ICT', 'HE', 'AFA'], true) => 'shs',
            default => null,
        };
    }

    // The code of the group an applicant is in (null when none)
    private static function groupOf(Applicant $a, array $scope): ?string
    {
        foreach ($scope as $code => $p) {
            if (self::inGroup($a, $p)) {
                return $code;
            }
        }

        return null;
    }

    /**
     * Real applicant rows for the given programs/level, matched the same way as realPrograms().
     * email/phone/address/dob/exam/paid are null: the regular import doesn't fill those in
     * (only the Add/Edit Applicant form does), so they're honestly blank, not fabricated.
     */
    private static function realApplicantsIn(array $scope, string $collegeCode): array
    {
        $level = self::levelFor($collegeCode);
        $statusLabel = fn ($s) => match ($s) {
            'submitted' => 'For Exam',
            'completed' => 'Paid',
            default => 'Pending',
        };

        $paid = ApplicantRecords::paidFees('reservation_fee');

        return Applicant::where('level', $level)->where('school_year', self::schoolYears()[3])->whereNull('archived_at')
            ->get()
            ->filter(fn ($a) => self::inCollege($a, $collegeCode) && self::groupOf($a, $scope) !== null)
            ->map(fn ($a) => [
                'id' => $a->reference_number,
                'name' => "{$a->last_name}, {$a->first_name}",
                'program' => self::groupOf($a, $scope),
                'status' => $statusLabel($a->application_status),
                // Yes on the scholarship question (the type stays with LAMP)
                'scholar' => (bool) $a->applying_for_scholarship,
                'applied' => optional($a->created_at)->toDateString(),
                'updated' => optional($a->updated_at)->format('M j, g:i A'),
                'dob' => optional($a->date_of_birth)->format('F j, Y'),
                'gender' => $a->gender === 'male' ? 'Male' : 'Female',
                'email' => $a->email,
                'phone' => $a->contact_number,
                'address' => $a->address,
                'exam' => null,
                'paid' => isset($paid[$a->id]) ? (string) ($paid[$a->id][1] ?? 'Paid; date not recorded') : null,
            ])
            ->sortByDesc('applied')
            ->values()
            ->all();
    }

    /** Program names, for Settings (Briefing and Display). */
    public static function programNames(): array
    {
        // iPACE and ETEEAP share some programs: list each name once
        return array_values(array_unique(array_column(self::college()['programs'], 'name')));
    }

    /**
     * Counts-only iPACE learner profile, based on fields actually captured by the import.
     * Employer and work history are not part of the current import schema and are not inferred.
     */
    private static function realIpaceProfile(string $level = 'ipace'): array
    {
        $applicants = Applicant::where('level', $level)->where('school_year', self::schoolYears()[3])
            ->whereNull('archived_at')->get();
        $count = fn ($predicate) => $applicants->filter($predicate)->count();
        $modalities = [
            'Online' => $count(fn ($a) => str_contains(strtoupper((string) $a->learning_modalities), 'ONLINE')),
            'Blended' => $count(fn ($a) => str_contains(strtoupper((string) $a->learning_modalities), 'BLENDED')),
            'Face to face' => $count(fn ($a) => str_contains(strtoupper((string) $a->learning_modalities), 'FACE')),
            'Not recorded' => $count(fn ($a) => blank($a->learning_modalities)),
        ];

        return ['Learner profile', [
            ['Learning modality', array_map(fn ($label, $value) => [$label, $value], array_keys($modalities), array_values($modalities))],
            ['Gender', [
                ['Male', $count(fn ($a) => $a->gender === 'male')],
                ['Female', $count(fn ($a) => $a->gender === 'female')],
                ['Not recorded', $count(fn ($a) => blank($a->gender))],
            ]],
            ['Scholarship', [
                ['Applying', $count(fn ($a) => (bool) $a->applying_for_scholarship)],
                ['Not applying', $count(fn ($a) => ! $a->applying_for_scholarship)],
            ]],
        ], 'Counts are based on imported iPACE applicant records. Employer and work-history details are not collected by the current import template.'];
    }

    // What the pages share: the programs in scope, the years, and who is looking.
    // A dean sees every program in their college; a program chair only their own (BS Computer Science for now).
    private function common(): array
    {
        $isChair = auth()->user()->isProgramChair();
        $college = self::college();
        $assignedProgram = auth()->user()->assigned_program;
        $chairPrograms = $assignedProgram && auth()->user()->assigned_college ? ['ASSIGNED' => ['name' => $assignedProgram]] : [];

        return [
            'needsProgramAssignment' => $isChair && $chairPrograms === [],
            'isChair' => $isChair,
            // Short name of what you see: "CITE", "Integrated School", or a program chair's program
            'label' => $isChair ? ($assignedProgram ?: 'Program not assigned') : ($college['label'] ?? $college['code']),
            'college' => ['code' => $college['code'], 'name' => $college['name'], 'subtitle' => $college['subtitle'] ?? $college['name']],
            // What the groups are called ("program" or "level") and what you see ("your college", "your school", "iPACE and ETEEAP")
            'word' => $college['word'] ?? 'program',
            'whole' => $college['scope'] ?? (str_starts_with($college['code'], 'IS') ? 'your school' : 'your college'),
            // What "For Exam" is called ("For Exam / Interview" in the Graduate Programs)
            'middle' => $college['middle'] ?? 'For Exam',
            // iPACE profile counts come from imported applicants. ETEEAP remains unchanged.
            'profiles' => $college['code'] === 'IPACE'
                ? ['iPACE' => self::realIpaceProfile(), 'ETEEAP' => self::realIpaceProfile('eteeap')]
                : ($college['profiles'] ?? []),
            // The address of these pages: /college for deans and program chairs, /principal for the IS Principal
            'base' => self::base(),
            'programs' => $isChair
                ? self::realPrograms($college['code'], $chairPrograms)
                : self::realPrograms($college['code'], $college['programs']),
            'years' => array_map(fn ($year) => 'AY '.substr($year, 0, 4).'–'.substr($year, 7, 2), self::schoolYears()),
        ];
    }

    // Briefing: this week's summary, tiles, "Needs your attention", programs at a glance, reports, Ask iARIS
    public function dashboard()
    {
        $user = auth()->user();
        $common = $this->common();

        return view('college.dashboard', $common + [
            // "Engr. Villafuerte", or the first name when there's no honorific
            'greetName' => isset(self::HONORIFICS[$user->name])
                ? self::HONORIFICS[$user->name].' '.Str::before($user->displayName(), ',')
                : Str::before($user->name, ' '),
            // The 3 newest reports the IATO sent (all of them are on the Reports page)
            'reports' => array_slice(self::received($common['isChair']), 0, 3),
        ]);
    }

    // Applicants: the applicants in scope, view only. ?program=BSCS picks a program first (the Briefing's "See applicants").
    // The iPACE Director gets their own page: iPACE or ETEEAP (?unit=eteeap), each with its own columns.
    public function applicants(Request $request)
    {
        // The IS Principal sees the same page as the admin's Integrated School Records, view only
        if (auth()->user()->isPrincipalOffice()) {
            return app(IsRecordController::class)->index();
        }

        if (auth()->user()->isGradChair()) {
            return app(GraduateRecordController::class)->index();
        }

        if (auth()->user()->role === 'dean') {
            return match (self::college()['code']) {
                'LAW' => app(LawRecordController::class)->index(),
                'GRAD' => app(GraduateRecordController::class)->index(),
                default => app(CollegeRecordController::class)->index(),
            };
        }

        $common = $this->common();
        if (auth()->user()->isDirector()) {
            $unit = $request->query('unit') === 'eteeap' ? 'ETEEAP' : 'iPACE';
            $programs = array_filter($common['programs'], fn ($p) => $p['unit'] === $unit);
            $datasets = self::directorApplicants();
            foreach ($datasets[$unit] as $row) {
                $programs[$row['program']] ??= ['name' => $row['program_name'] ?? $row['program'], 'unit' => $unit];
            }

            return view('college.applicants-director', $common + [
                'unit' => $unit,
                'unitPrograms' => $programs,
                // Applicants in each unit (for the switch at the top)
                'unitTotals' => array_map('count', $datasets),
                'selected' => isset($programs[$request->query('program')]) ? $request->query('program') : '',
                'rows' => $datasets[$unit],
            ]);
        }

        return view('college.applicants', $common + [
            'selected' => isset($common['programs'][$request->query('program')]) ? $request->query('program') : '',
            'rows' => self::realApplicantsIn($common['programs'], $common['college']['code']),
        ]);
    }

    // Analytics: Overview (trend and funnel), Programs, Compare years, AI insights. Worked out by the page from $programs.
    public function analytics()
    {
        $data = $this->common();
        if (auth()->user()->isDirector() || auth()->user()->isGradChair() || auth()->user()->role === 'dean') {
            $current = AdmissionStats::currentSchoolYear();
            $previous = AdmissionStats::previousSchoolYear();
            $levels = auth()->user()->isGradChair() ? ['graduate_school'] : ['ipace', 'eteeap'];
            $deanCollege = auth()->user()->role === 'dean' ? self::college()['code'] : null;
            if ($deanCollege) {
                $levels = [self::levelFor($deanCollege)];
            }

            $counts = function ($year) use ($levels, $deanCollege) {
                if (! $year) {
                    return [null, null, null];
                }
                $applicants = Applicant::where('school_year', $year)
                    ->whereIn('level', $levels)->whereNull('archived_at')->get()
                    ->when($deanCollege && $levels === ['college'], fn ($rows) => $rows->filter(
                        fn ($a) => self::inCollege($a, $deanCollege)
                    ));
                $reserved = $applicants->pluck('id')->intersect(array_keys(ApplicantRecords::paidFees('reservation_fee')))->count();

                return [$applicants->count(), $reserved, $applicants->where('application_status', 'completed')->count()];
            };
            $data['schoolYearComparison'] = [
                'currentLabel' => $current ? 'AY '.$current : 'Current SY (no import)',
                'previousLabel' => $previous ? 'AY '.$previous : 'Previous SY (unavailable)',
                'current' => $counts($current), 'previous' => $counts($previous),
            ];
        }

        return view('college.analytics', $data + ['fieldCounts' => self::fieldCounts($data)]);
    }

    /**
     * "Applicant field counts" (the same eight cards as the admin Analytics page), for the applicants in these programs:
     * what the import reads from the portal file, and the payments. Directors' sample units have no database rows, so 0.
     */
    private static function fieldCounts(array $common): array
    {
        $applicants = Applicant::whereIn('level', $common['college']['code'] === 'IPACE' ? ['ipace', 'eteeap'] : [self::levelFor($common['college']['code'])])
            ->where('school_year', self::schoolYears()[3])->whereNull('archived_at')->get()
            ->filter(fn ($a) => self::inCollege($a, $common['college']['code']) && self::groupOf($a, $common['programs']) !== null);
        $ids = $applicants->pluck('id');
        $paid = fn ($fee) => $ids->intersect(array_keys(ApplicantRecords::paidFees($fee)))->count();

        return [
            ['label' => 'Male', 'value' => $applicants->where('gender', 'male')->count(), 'icon' => 'bi-gender-male', 'tone' => 'blue'],
            ['label' => 'Female', 'value' => $applicants->where('gender', 'female')->count(), 'icon' => 'bi-gender-female', 'tone' => 'red'],
            ['label' => 'Applying for scholarship', 'value' => $applicants->where('applying_for_scholarship', true)->count(), 'icon' => 'bi-award', 'tone' => 'purple'],
            ['label' => 'Paid entrance exam', 'value' => $paid('application_fee'), 'icon' => 'bi-cash-coin', 'tone' => 'green'],
            ['label' => 'Paid reservation', 'value' => $paid('reservation_fee'), 'icon' => 'bi-bookmark-check', 'tone' => 'blue'],
            ['label' => 'NOA issued', 'value' => $applicants->where('noa_issued', true)->count(), 'icon' => 'bi-patch-check', 'tone' => 'green'],
            ['label' => 'PWD', 'value' => $applicants->where('pwd', true)->count(), 'icon' => 'bi-person-wheelchair', 'tone' => 'orange'],
            ['label' => 'SSEN', 'value' => $applicants->where('ssen', true)->count(), 'icon' => 'bi-person-badge', 'tone' => 'orange'],
        ];
    }

    // Reports: what IATO sent, four reports you can generate, and the ones you generated
    public function reports()
    {
        $common = $this->common();

        return view('college.reports', $common + [
            'received' => self::received($common['isChair']),
            // For the "Paid reservations" report: applicants in scope who paid. Real rows never have
            // a 'paid' value (payments aren't tracked yet), so this is honestly empty outside Director.
            'paidRows' => array_values(array_map(fn ($a) => [$a['id'], $a['name'], $common['programs'][$a['program']]['name'] ?? $a['program_name'] ?? $a['program'], $a['paid']],
                array_filter(auth()->user()->isDirector() ? array_merge(...array_values(self::directorApplicants())) : self::realApplicantsIn($common['programs'], $common['college']['code']), fn ($a) => $a['paid']))),
            'history' => DB::table('generated_reports')->where('user_id', auth()->id())->where('template_key', 'like', 'office-%')->latest('id')->limit(100)->get()->map(fn ($row) => self::officeHistoryRow($row))->all(),
        ]);
    }

    private function scopedReport(string $key): array
    {
        $user = auth()->user();
        abort_unless($user->isDeanOrChair() || $user->isPrincipalOffice() || $user->isDirector(), 403);
        $common = $this->common();
        abort_if($common['needsProgramAssignment'], 422, 'Ask IATO to assign this account to a college and program first.');
        $names = ['programs' => 'Applicant Summary', 'paid' => 'Paid Reservations', 'trend' => 'Enrollment Trends', 'funnel' => 'Conversion Funnel'];
        abort_unless(isset($names[$key]), 404);
        $programs = $common['programs'];
        $sum = fn ($field, $i) => array_sum(array_map(fn ($p) => $p[$field][$i], $programs));
        $percentage = fn ($value, $base) => $base > 0 ? round($value / $base * 100, 1).'%' : 'N/A';
        $year = self::schoolYears()[3];
        if ($key === 'programs') {
            $columns = ['Program / Level', 'Pending', 'Submitted', 'Paid Reservation', 'Applicants'];
            $rows = array_values(array_map(fn ($p) => [$p['name'], ...$p['status'], $p['applicants'][3]], $programs));
        } elseif ($key === 'paid') {
            $columns = ['Applicant no.', 'Name', 'Program / Level', 'Paid on'];
            $applicants = $user->isDirector() ? array_merge(...array_values(self::directorApplicants())) : self::realApplicantsIn($programs, $common['college']['code']);
            $rows = [];
            foreach ($applicants as $a) {
                if ($a['paid']) {
                    $rows[] = [$a['id'], $a['name'], $programs[$a['program']]['name'] ?? $a['program_name'] ?? $a['program'], $a['paid']];
                }
            }
        } elseif ($key === 'trend') {
            $columns = ['Academic year', 'Applicants', 'Enrolled (completed applications)', 'Conversion'];
            $rows = [];
            foreach (self::schoolYears() as $i => $schoolYear) {
                $rows[] = [$schoolYear, $sum('applicants', $i), $sum('enrolled', $i), $percentage($sum('enrolled', $i), $sum('applicants', $i))];
            }
        } else {
            $columns = ['Step', 'Applicants', 'Share of applied'];
            $rows = [];
            foreach (['Applied', 'Took exam', 'Passed', 'Paid Reservation', 'Enrolled (completed applications)'] as $i => $stage) {
                $rows[] = [$stage, $sum('funnel', $i), $percentage($sum('funnel', $i), $sum('funnel', 0))];
            }
        }

        return ['title' => $names[$key].' — '.$common['label'], 'sub' => $common['label'].' · SY '.$year, 'head' => $columns, 'rows' => $rows, 'year' => $year];
    }

    private static function officeHistoryRow(object $row): array
    {
        return ['id' => $row->id, 'template' => substr($row->template_key, 7), 'name' => $row->title, 'period' => $row->period, 'at' => $row->created_at, 'download' => route('office.reports.download', $row->id)];
    }

    public function previewOfficeReport(string $key)
    {
        return response()->json($this->scopedReport($key));
    }

    public function generateOfficeReport(Request $request)
    {
        $data = $request->validate(['key' => 'required|in:programs,paid,trend,funnel', 'format' => 'required|in:XLSX,PDF,CSV']);
        $table = $this->scopedReport($data['key']);
        $id = DB::transaction(function () use ($request, $data, $table) {
            $id = DB::table('generated_reports')->insertGetId([
                'user_id' => $request->user()->id, 'generated_by_name' => $request->user()->name,
                'template_key' => 'office-'.$data['key'], 'title' => $table['title'], 'recipient' => $request->user()->roleInfo()[0].' (self)',
                'period' => 'AY '.$table['year'], 'school_year' => $table['year'], 'format' => $data['format'],
                'snapshot' => json_encode([['title' => $table['title'], 'columns' => $table['head'], 'rows' => $table['rows']]], JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);
            AuditLog::record('report', 'Reports', 'Office Report Generated', 'Generated report '.$id.': '.$table['title'].'. Saved snapshot for the assigned scope.');

            return $id;
        });

        return response()->json(['history' => self::officeHistoryRow(DB::table('generated_reports')->where('id', $id)->first())]);
    }

    public function downloadOfficeReport(Request $request, int $report)
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isDeanOrChair() || $user->isPrincipalOffice() || $user->isDirector(), 403);
        $saved = DB::table('generated_reports')->where('id', $report)->first();
        abort_unless($saved && str_starts_with($saved->template_key ?? '', 'office-'), 404);

        return app(ReportExportController::class)->download($request, $report);
    }

    // Messages with IATO: the same page as the Executive Office's
    public function messages()
    {
        return view('executive.messages', ['threads' => self::threads(), 'base' => self::base()]);
    }

    /** Conversations IATO answered (the number on the sidebar's Messages link). */
    public static function answeredMessages(): int
    {
        return collect(self::threads())->where('status', 'answered')->count();
    }

    // Your conversations with IATO, newest first (CITE's is the one in the mockup; the other deans have none yet)
    private static function threads(): array
    {
        if (self::college()['code'] !== 'CITE' || auth()->user()->isProgramChair()) {
            return [];
        }

        return [
            ['id' => 'MSG-0112', 'topic' => 'Question about the data', 'subject' => 'BS Architecture applicants look low', 'status' => 'waiting', 'messages' => [
                ['from' => 'me', 'at' => 'Jun 12, 2026 · 9:14 AM', 'text' => 'BS Architecture shows 72 applicants, but our department head counted more walk-in applications last week. Are the walk-ins already encoded?'],
            ]],
        ];
    }

    /**
     * Reports the IATO sent to this office, newest first.
     * status: new or read. 'kind' picks the table you get when you download it (see the Reports page).
     */
    public static function received(bool $isChair): array
    {
        return [];
    }

    /**
     * Director applicants: iPACE preview records and imported ETEEAP records.
     * iPACE: delivery mode, year level, working student, employer. ETEEAP: age, education, work history, portfolio.
     */
    private static function directorApplicants(): array
    {
        $programs = self::realPrograms('IPACE', self::COLLEGES['IPACE']['programs']);
        $ipaceCodes = [];
        foreach ($programs as $code => $program) {
            if (($program['unit'] ?? null) === 'iPACE') {
                $ipaceCodes[$program['name']] = $code;
            }
        }
        $preview = ['iPACE' => array_map(function ($a) use ($ipaceCodes) {
            $programName = $a['program_label'] ?: 'Unspecified program';

            return [
                'id' => $a['id'], 'name' => trim($a['last'].', '.$a['first']),
                'program' => $ipaceCodes[$programName] ?? 'I-OTHER',
                'program_name' => $programName,
                'mode' => $a['program'] === '—' ? 'Not recorded' : $a['program'],
                'year' => $a['year'], 'working' => $a['working'], 'employer' => $a['employer'],
                'status' => $a['status'], 'applied' => $a['applied'],
                'updated' => $a['updated'], 'dob' => $a['dob'],
                'gender' => $a['gender'], 'email' => $a['email'],
                'phone' => $a['contact'], 'address' => $a['address'],
                'exam' => $a['exam'], 'paid' => $a['paid'],
                'exam_status' => $a['exam_status'], 'exam_paid' => $a['exam_paid'],
                'preview' => false,
            ];
        }, ApplicantRecords::forLevel('ipace'))];
        $preview['ETEEAP'] = array_map(function ($a) {
            $assessment = match ($a['program']) {
                'Cleared' => 'cleared', 'Submitted' => 'submitted', default => 'pending'
            };

            return [
                'id' => $a['id'], 'name' => trim($a['last'].', '.$a['first']),
                'program' => 'E-'.$a['group'], 'program_name' => $a['program_label'],
                'age' => $a['age'], 'education' => $a['education'], 'workYears' => null, 'work' => [],
                'employer' => null, 'assess' => $assessment, 'portfolio' => $a['program'],
                'status' => $a['status'] === 'For Exam' ? 'For Assessment' : $a['status'],
                'applied' => $a['applied'], 'updated' => $a['updated'], 'dob' => $a['dob'],
                'gender' => $a['gender'], 'email' => $a['email'], 'phone' => $a['contact'], 'address' => $a['address'],
                'paid' => $a['paid'],
                'exam_status' => $a['exam_status'], 'exam_paid' => $a['exam_paid'], 'preview' => false,
            ];
        }, ApplicantRecords::forLevel('eteeap'));

        return $preview;
    }

    private static function directorPreviewApplicants(): array
    {
        $paid = fn ($on, $amount) => ['paid' => $on, 'amount' => $amount];

        return [
            'iPACE' => [
                ['id' => 'APP-2026-I005', 'name' => 'Villanueva, Rodel Santos C.', 'program' => 'I-BEED', 'mode' => 'Online', 'year' => '2nd Year', 'working' => 'Yes', 'employer' => 'DepEd Lipa · Teacher Aide',
                    'dob' => 'June 20, 1998', 'gender' => 'Male', 'email' => 'rs.villanueva@dlsl.edu.ph', 'phone' => '0921 500 5555', 'address' => 'Lipa City, Batangas',
                    'status' => 'For Exam', 'applied' => '2026-05-11', 'updated' => 'Jun 4, 11:00 AM', 'exam' => 'Jun 18, 2026 · 9:00 AM'] + $paid(null, null),
                ['id' => 'APP-2026-I004', 'name' => 'Torres, Angelica Mae D.', 'program' => 'I-BSN', 'mode' => 'Blended', 'year' => '1st Year', 'working' => 'Yes', 'employer' => 'Adventist Medical Center · Nursing Aide',
                    'dob' => 'October 5, 2002', 'gender' => 'Female', 'email' => 'am.torres@dlsl.edu.ph', 'phone' => '0920 600 4444', 'address' => 'Santo Tomas, Batangas',
                    'status' => 'Pending', 'applied' => '2026-05-16', 'updated' => 'May 16, 3:00 PM', 'exam' => null] + $paid(null, null),
                ['id' => 'APP-2026-I002', 'name' => 'Garcia, Joanna Marie T.', 'program' => 'I-BSBA', 'mode' => 'Blended', 'year' => '1st Year', 'working' => 'Yes', 'employer' => 'SM Lipa · Sales Associate',
                    'dob' => 'July 30, 2001', 'gender' => 'Female', 'email' => 'jm.garcia@dlsl.edu.ph', 'phone' => '0918 900 2222', 'address' => 'Batangas City, Batangas',
                    'status' => 'For Exam', 'applied' => '2026-05-09', 'updated' => 'Jun 3, 2:00 PM', 'exam' => 'Jun 15, 2026 · 10:00 AM'] + $paid(null, null),
                ['id' => 'APP-2026-I001', 'name' => 'Lim, Kenneth Andrew P.', 'program' => 'I-BSIT', 'mode' => 'Online', 'year' => '2nd Year', 'working' => 'Yes', 'employer' => 'BDO Unibank · IT Associate',
                    'dob' => 'April 12, 1999', 'gender' => 'Male', 'email' => 'ka.lim@dlsl.edu.ph', 'phone' => '0917 800 1111', 'address' => 'Lipa City, Batangas',
                    'status' => 'Paid', 'applied' => '2026-05-05', 'updated' => 'Jun 1, 9:00 AM', 'exam' => 'Jun 10, 2026 · 9:00 AM'] + $paid('Jun 1, 2026', '₱2,000.00'),
                ['id' => 'APP-2026-I003', 'name' => 'Ramos, Aldrich Jose B.', 'program' => 'I-BSCS', 'mode' => 'Online', 'year' => '3rd Year', 'working' => 'No', 'employer' => null,
                    'dob' => 'February 14, 2000', 'gender' => 'Male', 'email' => 'aj.ramos@dlsl.edu.ph', 'phone' => '0919 700 3333', 'address' => 'Tanauan City, Batangas',
                    'status' => 'Paid', 'applied' => '2026-05-03', 'updated' => 'May 28, 10:00 AM', 'exam' => 'Jun 8, 2026 · 8:00 AM'] + $paid('May 28, 2026', '₱2,000.00'),
            ],

        ];
    }
}
