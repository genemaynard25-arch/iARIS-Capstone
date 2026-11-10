{{-- Presentation catalog: connected values stay in $modules; missing controls use mockup defaults. --}}
@php
    $catalog = json_decode(<<<'JSON'
{
    "roles": [
        {
            "key": "admin",
            "mockKey": "sa",
            "name": "Super Admin",
            "icon": "bi-star-fill",
            "tone": "primary",
            "scope": "Institution-wide",
            "description": "Full control of iARIS. Only one Super Admin at a time; the role moves only through Super User Succession.",
            "preview": false
        },
        {
            "key": "iato_staff",
            "mockKey": "staff",
            "name": "IATO Staff",
            "icon": "bi-person-badge",
            "tone": "primary",
            "scope": "Institution-wide",
            "description": "Day-to-day admissions work. Their edits and imports go to the Review Queue before others see them.",
            "preview": false
        },
        {
            "key": "dean",
            "mockKey": "dean",
            "name": "Dean",
            "icon": "bi-person-workspace",
            "tone": "info",
            "scope": "Own college only",
            "description": "One per college: CBEAM, CEAS, CIHTM, CITE, CON, CCJE, and the College of Law. Read-only, except generating reports for their college.",
            "preview": false
        },
        {
            "key": "program_chair",
            "mockKey": "chair",
            "name": "Program Chair, Graduate Programs",
            "icon": "bi-mortarboard",
            "tone": "info",
            "scope": "Graduate Programs only",
            "description": "Heads the Graduate Programs (MBA, MEd, MIT, MPA, PhD). Read-only, except generating reports for the Graduate Programs.",
            "preview": false
        },
        {
            "key": "principal",
            "mockKey": "shs",
            "name": "Integrated School Principal",
            "icon": "bi-building",
            "tone": "secondary",
            "scope": "Integrated School",
            "description": "Heads the Integrated School (Preschool to Grade 12). Views its applicants and reports.",
            "preview": false
        },
        {
            "key": "assistant_principal",
            "mockKey": "ap",
            "name": "Assistant Principal",
            "icon": "bi-buildings",
            "tone": "secondary",
            "scope": "Own levels only",
            "description": "One each for Grade School (Preschool and Grades 1–6), Junior High (Grades 7–10), and Senior High (Grades 11–12). Views those applicants and reports.",
            "preview": false
        },
        {
            "key": "registrar",
            "mockKey": "reg",
            "name": "Registrar",
            "icon": "bi-person-vcard",
            "tone": "info",
            "scope": "Assigned unit (College or IS)",
            "description": "College Registrar and Integrated School Registrar. View-only access to the records of their unit.",
            "preview": false
        },
        {
            "key": "lamp",
            "mockKey": "lamp",
            "name": "LAMP Office",
            "icon": "bi-award",
            "tone": "warning",
            "scope": "Scholarships",
            "description": "Lasallian Assistance Management Program, under the VP for Advancement. Confirms or flags scholars; every change notifies IATO for review.",
            "preview": false
        },
        {
            "key": "president",
            "mockKey": "pres",
            "name": "President",
            "icon": "bi-bank",
            "tone": "danger",
            "scope": "Institution-wide",
            "description": "Institution-level dashboards and reports. Can generate reports and reply in report threads.",
            "preview": false
        },
        {
            "key": "chancellor",
            "mockKey": "chan",
            "name": "Chancellor",
            "icon": "bi-bank2",
            "tone": "danger",
            "scope": "Institution-wide",
            "description": "Institution-level dashboards and reports. Can generate reports and reply in report threads.",
            "preview": false
        },
        {
            "key": "vice_chancellor",
            "mockKey": "vc",
            "name": "Vice Chancellor",
            "icon": "bi-person-workspace",
            "tone": "danger",
            "scope": "Institution-wide",
            "description": "Academics; Research, Innovation & Internationalization; and Mission. Views dashboards and reports.",
            "preview": false
        },
        {
            "key": "vice_president",
            "mockKey": "vp",
            "name": "Vice President",
            "icon": "bi-briefcase",
            "tone": "danger",
            "scope": "Institution-wide",
            "description": "Administration, Advancement, and Finance, under the Office of the President. Views dashboards and reports.",
            "preview": false
        },
        {
            "key": "director",
            "mockKey": "alh",
            "name": "IPACE Director",
            "icon": "bi-book",
            "tone": "warning",
            "scope": "iPACE and ETEEAP",
            "description": "Director of the Institute for Professional Advancement and Continuing Education. View-only access to iPACE and ETEEAP records and reports.",
            "preview": false
        },
        {
            "key": "toic",
            "mockKey": "toic",
            "name": "TOIC (System Support)",
            "icon": "bi-hdd-rack",
            "tone": "secondary",
            "scope": "System data only",
            "description": "Manages iARIS and fixes it when something fails: problems reported by users, backups, restarts, security, and maintenance. Sees no applicant records, reports, or Messages from other offices.",
            "preview": false
        }
    ],
    "defaults": {
        "sa": {
            "dash": "2L",
            "apps": "2L",
            "ar": "2L",
            "ai": "2L",
            "is": "2L",
            "col": "2L",
            "grad": "2L",
            "law": "2L",
            "ipace": "2L",
            "eteeap": "2L",
            "sch": "2L",
            "imp": "2L",
            "rq": "2L",
            "audit": "2L",
            "acc": "2L",
            "users": "2L",
            "succ": "2L",
            "set": "2L"
        },
        "staff": {
            "dash": "1",
            "apps": "2",
            "ar": "2",
            "ai": "1",
            "is": "2",
            "col": "2",
            "grad": "2",
            "law": "2",
            "ipace": "2",
            "eteeap": "2",
            "sch": "2",
            "imp": "2",
            "rq": "0L",
            "audit": "1",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "dean": {
            "dash": "1",
            "apps": "1",
            "ar": "2",
            "ai": "1",
            "is": "0",
            "col": "1",
            "grad": "0",
            "law": "1",
            "ipace": "0",
            "eteeap": "0",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "chair": {
            "dash": "1",
            "apps": "1",
            "ar": "2",
            "ai": "1",
            "is": "0",
            "col": "0",
            "grad": "1",
            "law": "0",
            "ipace": "0",
            "eteeap": "0",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "shs": {
            "dash": "1",
            "apps": "1",
            "ar": "1",
            "ai": "1",
            "is": "1",
            "col": "0",
            "grad": "0",
            "law": "0",
            "ipace": "0",
            "eteeap": "0",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "ap": {
            "dash": "1",
            "apps": "1",
            "ar": "1",
            "ai": "1",
            "is": "1",
            "col": "0",
            "grad": "0",
            "law": "0",
            "ipace": "0",
            "eteeap": "0",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "reg": {
            "dash": "1",
            "apps": "1",
            "ar": "1",
            "ai": "1",
            "is": "1",
            "col": "1",
            "grad": "1",
            "law": "1",
            "ipace": "1",
            "eteeap": "1",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "lamp": {
            "dash": "1",
            "apps": "0",
            "ar": "1",
            "ai": "1",
            "is": "0",
            "col": "0",
            "grad": "0",
            "law": "0",
            "ipace": "0",
            "eteeap": "0",
            "sch": "2",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "pres": {
            "dash": "1",
            "apps": "0",
            "ar": "2",
            "ai": "1",
            "is": "0",
            "col": "0",
            "grad": "0",
            "law": "0",
            "ipace": "0",
            "eteeap": "0",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "chan": {
            "dash": "1",
            "apps": "0",
            "ar": "2",
            "ai": "1",
            "is": "0",
            "col": "0",
            "grad": "0",
            "law": "0",
            "ipace": "0",
            "eteeap": "0",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "vp": {
            "dash": "1",
            "apps": "0",
            "ar": "1",
            "ai": "1",
            "is": "0",
            "col": "0",
            "grad": "0",
            "law": "0",
            "ipace": "0",
            "eteeap": "0",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "vc": {
            "dash": "1",
            "apps": "0",
            "ar": "1",
            "ai": "1",
            "is": "0",
            "col": "0",
            "grad": "0",
            "law": "0",
            "ipace": "0",
            "eteeap": "0",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "alh": {
            "dash": "1",
            "apps": "1",
            "ar": "1",
            "ai": "1",
            "is": "0",
            "col": "0",
            "grad": "0",
            "law": "0",
            "ipace": "1",
            "eteeap": "1",
            "sch": "0",
            "imp": "0",
            "rq": "0L",
            "audit": "0",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        },
        "toic": {
            "dash": "0L",
            "apps": "0L",
            "ar": "0L",
            "ai": "0L",
            "is": "0L",
            "col": "0L",
            "grad": "0L",
            "law": "0L",
            "ipace": "0L",
            "eteeap": "0L",
            "sch": "0L",
            "imp": "0L",
            "rq": "0L",
            "audit": "0L",
            "acc": "0L",
            "users": "0L",
            "succ": "0L",
            "set": "0L"
        }
    },
    "moduleKeys": {
        "dashboard": "dash",
        "applicants": "apps",
        "analytics": "ar",
        "reports": "ar",
        "ai_chatbot": "ai",
        "college": "col",
        "is": "is",
        "graduate": "grad",
        "law": "law",
        "ipace": "ipace",
        "eteeap": "eteeap",
        "scholars": "sch",
        "import": "imp",
        "review_queue": "rq",
        "edit": "rq",
        "audit": "audit",
        "access": "acc",
        "accounts": "users",
        "succession": "succ",
        "system_settings": "set"
    }
}
JSON, true, 512, JSON_THROW_ON_ERROR);

    $existingRoles = collect($roles)->keyBy('key');
    $roles = collect($catalog['roles'])->map(fn (array $role): array => array_replace($existingRoles->get($role['key'], []), $role))->all();

    $addModule = fn (string $key, string $name, string $description, bool $adminOnly = false): array => [
        'key' => $key, 'name' => $name, 'description' => $description,
        'on' => [], 'preview' => true, 'adminOnly' => $adminOnly,
    ];
    $modules['Main'][] = $addModule('ai_chatbot', 'AI Chatbot', 'Read-only answers based on the records a role can see');
    $records = collect($modules['Records'])->keyBy('key');
    $modules['Records'] = [
        $records['is'], $records['college'],
        $addModule('graduate', 'Graduate Programs', 'Master’s and doctoral programs'),
        $addModule('law', 'College of Law', 'JD, LLM, and JSD'),
        $addModule('ipace', 'iPACE', 'Institute for Professional Advancement and Continuing Education'),
        $addModule('eteeap', 'ETEEAP', 'Expanded Tertiary Education Equivalency and Accreditation Program'),
        $records['scholars'],
    ];
    $system = collect($modules['System'])->keyBy('key');
    $modules['System'] = [
        $records['import'],
        $addModule('review_queue', 'Review Queue', 'Approve staff changes', true),
        $system['edit'], $system['audit'], $system['access'], $system['accounts'],
        $addModule('succession', 'Super User Succession', 'Hand over the Super User role', true),
        $addModule('system_settings', 'System Settings', 'Academic year, fees, and retention', true),
    ];

    $previewState = function (array $role, array $module) use ($catalog): array {
        $value = $catalog['defaults'][$role['mockKey']][$catalog['moduleKeys'][$module['key']]] ?? '0';
        return ['level' => (int) $value, 'locked' => str_contains($value, 'L')];
    };
    $isPreview = fn (array $role, array $module): bool => ! empty($role['preview']) || ! empty($module['preview']);
    $permissionLevel = function (array $role, array $module) use ($isPreview, $previewState, $editorPermissions): int {
        if ($role['key'] === 'toic') {
            return 0;
        }
        if ($isPreview($role, $module)) {
            return $previewState($role, $module)['level'];
        }
        if ($role['key'] === 'admin') {
            return 2;
        }
        if (! in_array($role['key'], $module['on'])) {
            return 0;
        }
        return in_array($module['key'], $editorPermissions[$role['key']] ?? [], true) ? 2 : 1;
    };
    $permissionLocked = fn (array $role, array $module): bool => in_array($role['key'], ['admin', 'toic'], true) || ! empty($module['adminOnly']) || ($isPreview($role, $module) && $previewState($role, $module)['locked']);
@endphp

    {{-- Both permission views share the database-backed controls below. --}}
    <section class="ac-permissions card border-0" aria-labelledby="permissionsTitle">
        <div class="ac-permissions-header d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
                <h2 id="permissionsTitle">Role Permissions</h2>
                <p>Choose a role and set its module access. View is read-only; Edit allows changes for accounts with editor access.</p>
            </div>
            <div class="ac-view-switch btn-group" role="group" aria-label="Permission view">
                <input type="radio" class="btn-check" name="permView" id="viewByRole" value="role" checked>
                <label class="btn btn-sm" for="viewByRole"><i class="bi bi-person-gear me-1" aria-hidden="true"></i> By role</label>
                <input type="radio" class="btn-check" name="permView" id="viewCompare" value="compare">
                <label class="btn btn-sm" for="viewCompare"><i class="bi bi-grid-3x3 me-1" aria-hidden="true"></i> Compare all roles</label>
            </div>
        </div>

        <div class="ac-permissions-body" id="byRoleView">
            <nav class="ac-roles" aria-label="Choose a role">
                @foreach ($roles as $role)
                    <button type="button" class="ac-role-button {{ $loop->index === 1 ? 'is-selected' : '' }}" data-pick-role="{{ $role['key'] }}" aria-pressed="{{ $loop->index === 1 ? 'true' : 'false' }}" aria-controls="role-panel-{{ $role['key'] }}">
                        <span class="ac-role-icon bg-{{ $role['tone'] }}-subtle text-{{ $role['tone'] }}-emphasis"><i class="bi {{ $role['icon'] }}" aria-hidden="true"></i></span>
                        <span class="ac-role-text"><strong>{{ $role['name'] }} @if ($role['preview'])<span class="ac-preview-badge">Preview</span>@endif</strong><small>{{ $role['scope'] }}</small></span>
                        <span class="ac-role-count" data-role-user-count="{{ $role['key'] }}" aria-label="Users assigned to {{ $role['name'] }}" title="Users assigned to this role">{{ number_format($roleUserCounts[$role['key']] ?? 0) }}</span>
                        <span class="ac-role-dirty d-none" data-role-dirty="{{ $role['key'] }}" aria-label="Unsaved changes"></span>
                    </button>
                @endforeach
            </nav>

            <div class="ac-role-details">
                @foreach ($roles as $role)
                    <section class="{{ $loop->index === 1 ? '' : 'd-none' }}" id="role-panel-{{ $role['key'] }}" data-role-panel="{{ $role['key'] }}" aria-labelledby="role-title-{{ $role['key'] }}">
                        <div class="ac-role-heading d-flex align-items-start gap-3">
                            <span class="ac-role-icon ac-role-icon-lg bg-{{ $role['tone'] }}-subtle text-{{ $role['tone'] }}-emphasis"><i class="bi {{ $role['icon'] }}" aria-hidden="true"></i></span>
                            <div>
                                <h3 id="role-title-{{ $role['key'] }}">{{ $role['name'] }}</h3>
                                <p>{{ $role['description'] }}</p>
                                <div class="ac-role-meta"><span><i class="bi bi-grid" aria-hidden="true"></i> <b data-module-count="{{ $role['key'] }}">{{ collect($modules)->flatten(1)->filter(fn ($module) => $permissionLevel($role, $module) > 0)->count() }}</b> modules enabled</span><a href="{{ url('/accounts') }}">Manage accounts <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></div>
                            </div>
                        </div>

                        @foreach ($modules as $section => $sectionModules)
                            <h4 class="ac-module-section">{{ $section }}</h4>
                            @foreach ($sectionModules as $module)
                                @php
                                    $isAdmin = $role['key'] === 'admin';
                                    $preview = $isPreview($role, $module);
                                    $locked = $permissionLocked($role, $module);
                                    $level = $permissionLevel($role, $module);
                                @endphp
                                <div class="ac-module-row" data-permission-row data-row-role="{{ $role['key'] }}" data-row-module="{{ $module['key'] }}">
                                    <div class="ac-module-text"><strong>{{ $module['name'] }} @if ($preview && ! in_array($module['key'], ['ai_chatbot', 'law', 'review_queue', 'graduate', 'eteeap'], true))<span class="ac-preview-badge">Preview</span>@endif</strong><small>{{ $module['description'] }}</small></div>
                                    @if ($locked)
                                        <span class="ac-locked"><i class="bi bi-lock-fill" aria-hidden="true"></i> {{ $level > 0 ? ($isAdmin ? 'Full access' : 'View only') : (! empty($module['adminOnly']) ? 'Super Admin only' : 'No access') }}</span>
                                    @else
                                        <div class="ac-levels btn-group" role="group" aria-label="{{ $role['name'] }}: {{ $module['name'] }}">
                                            @foreach ([0 => 'None', 1 => 'View', 2 => 'Edit'] as $value => $label)
                                                <button type="button" class="btn {{ $level === $value ? 'is-selected' : '' }}" data-level-choice="{{ $value }}" data-choice-role="{{ $role['key'] }}" data-choice-module="{{ $module['key'] }}" data-preview-choice="{{ $preview ? 'true' : 'false' }}" aria-pressed="{{ $level === $value ? 'true' : 'false' }}">{{ $label }}</button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        @endforeach
                    </section>
                @endforeach
            </div>
        </div>

        <div class="table-responsive d-none ac-compare" id="compareView">
            <table class="table align-middle mb-0 matrix-table">
                <caption class="visually-hidden">Module access by role. View and Edit levels are configured in the By role view.</caption>
                <thead><tr><th scope="col">Module</th>
                    @foreach ($roles as $role)
                        <th scope="col" class="text-center"><span class="ac-role-icon bg-{{ $role['tone'] }}-subtle text-{{ $role['tone'] }}-emphasis mx-auto mb-1"><i class="bi {{ $role['icon'] }}" aria-hidden="true"></i></span><span class="d-block">{{ $role['name'] }}</span>@if ($role['preview'])<span class="ac-preview-badge">Preview</span>@endif</th>
                    @endforeach
                </tr></thead>
                <tbody>
                    @foreach ($modules as $section => $sectionModules)
                        <tr class="ac-matrix-section"><th scope="rowgroup">{{ $section }}</th><td colspan="{{ count($roles) }}"></td></tr>
                        @foreach ($sectionModules as $module)
                            <tr><th scope="row"><strong>{{ $module['name'] }}</strong>@if (! empty($module['preview']) && ! in_array($module['key'], ['ai_chatbot', 'law', 'review_queue', 'graduate', 'eteeap'], true))<span class="ac-preview-badge">Preview</span>@endif<small>{{ $module['description'] }}</small></th>
                                @foreach ($roles as $role)
                                    @php
                                        $isAdmin = $role['key'] === 'admin';
                                        $preview = $isPreview($role, $module);
                                        $locked = $permissionLocked($role, $module);
                                        $level = $permissionLevel($role, $module);
                                        $on = $level > 0;
                                    @endphp
                                    <td class="text-center">
                                        @if ($preview)
                                            <input type="hidden" data-preview-perm data-initial-level="{{ $level }}" data-role="{{ $role['key'] }}" data-module="{{ $module['key'] }}" value="{{ $level }}">
                                            <span class="ac-preview-level ac-preview-level-{{ $level }}" data-preview-level-label>{{ ['None', 'View', 'Edit'][$level] }}</span>
                                        @else
                                        <div class="form-switch d-inline-block p-0 m-0"><input class="form-check-input m-0" type="checkbox" role="switch" aria-label="{{ $role['name'] }}: {{ $module['name'] }}" {{ $on ? 'checked' : '' }}
                                            @if ($locked) disabled title="{{ $role['key'] === 'toic' ? 'TOIC has no access to these modules' : ($isAdmin ? 'The Super Admin always has access' : 'Super Admin only') }}"
                                            @else data-perm data-role="{{ $role['key'] }}" data-module="{{ $module['key'] }}" data-role-name="{{ $role['name'] }}" data-module-name="{{ $module['name'] }}"
                                            @endif></div>
                                        @endif
                                        @if ($locked)<div class="ac-matrix-lock"><i class="bi bi-lock-fill" aria-hidden="true"></i></div>@endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Canonical editor values come from the existing RolePermission records. --}}
        <div hidden>
            @foreach ($roles as $role)
                @foreach (collect($modules)->flatten(1) as $module)
                    @unless ($isPreview($role, $module) || $permissionLocked($role, $module))
                        <input type="checkbox" data-edit-perm data-role="{{ $role['key'] }}" data-role-name="{{ $role['name'] }}" data-module="{{ $module['key'] }}" data-module-name="{{ $module['name'] }}" {{ in_array($module['key'], $editorPermissions[$role['key']] ?? [], true) ? 'checked' : '' }}>
                    @endunless
                @endforeach
            @endforeach
        </div>
        <div class="ac-permission-note"><i class="bi bi-lock-fill me-1" aria-hidden="true"></i> Preview controls use mockup data and apply only to this page. Connected permissions save to iARIS; locked permissions cannot be changed.</div>
        <div class="ac-save-bar d-none" id="permissionSaveBar" aria-label="Unsaved permission changes">
            <span id="unsavedBadge" role="status"></span>
            <button type="button" class="btn btn-light border fw-semibold" id="discardButton" disabled>Discard</button>
            <button type="button" class="btn btn-primary fw-semibold" id="saveButton" disabled><i class="bi bi-floppy me-1" aria-hidden="true"></i> <span id="permissionSaveLabel">Save Changes</span></button>
        </div>
    </section>
