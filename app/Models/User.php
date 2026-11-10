<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(["name", "email", "password", "department", "status", "role", "assigned_college", "assigned_level"])]
#[Hidden(["password", "remember_token"])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "last_login_at" => "datetime",
        ];
    }
    /**
     * Which pages / sidebar a user gets. Used by the layout and HomeController.
     */
    public function isLamp(): bool
    {
        return $this->role === "lamp";
    }

    /**
     * Which pages / sidebar a user gets. Used by the layout and HomeController.
     */
    public function isAdmin(): bool
    {
        if ($this->role === "admin") {
            return true;
        }
        return \App\Models\TempAdmin::active()
            ->where("user_id", $this->id)
            ->exists();
    }
    // Whether the user can access a module. Admins can access everything; other users must have a RolePermission with enabled = true.
   public function canAccess(string $module): bool
{
    if ($this->isToic()) {
        return false;
    }
    if ($this->isAdmin()) {
        return true;
    }

    return \App\Models\RolePermission::where("role", $this->role)
        ->where("module", $module)
        ->where("enabled", true)
        ->exists();
    }
    // Whether the user can edit a module. Admins can edit everything; other users must have access_type 'editor' and a RolePermission with can_edit = true.
    public function canEdit(string $module): bool
    {
        if ($this->isToic()) {
            return false;
        }
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->access_type !== 'editor') {
            return false;
        }

        return \App\Models\RolePermission::where("role", $this->role)
            ->where("module", $module)
            ->where("enabled", true)
            ->where("can_edit", true)
            ->exists();
    }
    // Grants temporary admin privileges to a user. Returns the TempAdmin model if the user has an active grant, or null otherwise.
    public function activeTempAdminGrant(): ?\App\Models\TempAdmin
    {
        return \App\Models\TempAdmin::active()
            ->where("user_id", $this->id)
            ->first();
    }

    // The Chancellor (one of the Executive Office roles; see isExecutive() below)
    public function isChancellor(): bool
    {
        return $this->role === "chancellor";
    }

    // Name parts: "Edmundo L. Fernandez FSC" -> [first and middle "Edmundo L.", last "Fernandez", suffix "FSC"]
    private function nameParts(): array
    {
        $parts = preg_split('/\s+/', trim($this->name ?? ''));
        $suffix = count($parts) > 2 && preg_match('/^(FSC|Jr\.?|Sr\.?|II|III|IV)$/i', end($parts)) ? array_pop($parts) : '';
        $last = array_pop($parts);
        // Surname particles stay with the last name: "Jasmin De Silva" -> "De Silva", "Ana Dela Cruz" -> "Dela Cruz"
        while (count($parts) > 1 && preg_match('/^(De|Del|Dela|Delos|Los|San|Santa|Sta\.|Van|Von)$/i', end($parts))) {
            $last = array_pop($parts) . ' ' . $last;
        }

        return [implode(' ', $parts), $last, $suffix];
    }

    // "Cherrie Villafuerte" -> "Villafuerte, Cherrie"; "Edmundo L. Fernandez FSC" -> "Fernandez, Edmundo L. FSC"
    public function displayName(): string
    {
        // A team account ("TOIC Support Team") is shown as written
        if ($this->isToic()) {
            return $this->name;
        }
        [$first, $last, $suffix] = $this->nameParts();

        return $first === '' ? $last : trim("{$last}, {$first} {$suffix}");
    }

    // "Cherrie Villafuerte" -> "CV"; "Edmundo L. Fernandez FSC" -> "EF"
    public function initials(): string
    {
        if ($this->isToic()) {
            return "TO";
        }
        [$first, $last] = $this->nameParts();

        return mb_strtoupper(mb_substr($first !== '' ? $first : $last, 0, 1) . ($first !== '' ? mb_substr($last, 0, 1) : ''));
    }

    // TOIC (system support): sees how iARIS runs (uptime, backups, security, issues), never applicant data. Pages at /toic.
    public function isToic(): bool
    {
        return $this->role === "toic";
    }

    // The iPACE Director, who also heads ETEEAP: the Dean pages for both, at /director
    public function isDirector(): bool
    {
        return $this->role === "director";
    }

    // Executive Office: the President, the Chancellor, Vice Presidents and Vice Chancellors.
    // They share the same read-only, university-wide pages at /executive.
    public function isExecutive(): bool
    {
        return in_array($this->role, ["president", "vice_president", "chancellor", "vice_chancellor"]);
    }

    // A Vice President or Vice Chancellor: same pages as the President and Chancellor,
    // but view only (no Report Preferences). Their area is kept in `department`, e.g. "Finance".
    public function isVice(): bool
    {
        return in_array($this->role, ["vice_president", "vice_chancellor"]);
    }

    // "Vice President for Finance", "Vice Chancellor for Academics", or just the role without a department
    public function executiveTitle(): string
    {
        $title = $this->role === "vice_chancellor" ? "Vice Chancellor" : "Vice President";

        return $this->department ? "{$title} for {$this->department}" : $title;
    }

    // Dean of a college, or a Program Chair: a read-only College dashboard at /college.
    // A dean sees every program in their college; a program chair sees only their program.
    public function isDeanOrChair(): bool
    {
        return $this->role === "dean" || $this->role === "program_chair";
    }

    // A college's Program Chair, locked to one program (BS Computer Science for now).
    // The Graduate Programs chair is also a 'program_chair', but assigned to graduate_school: see isGradChair().
    public function isProgramChair(): bool
    {
        return $this->role === "program_chair" && ! $this->isGradChair();
    }

    // The Program Chair of the Graduate Programs: the Dean pages for every graduate program
    public function isGradChair(): bool
    {
        return $this->role === "program_chair" && $this->assigned_level === "graduate_school";
    }

    // The seeded SHS principal has role 'admin' but is assigned to IS / SHS, so the SHS assignment decides
    // (a 'principal' assigned to SHS also counts). A 'principal' without it is the IS Principal (below).
    public function isShsPrincipal(): bool
    {
        return $this->role !== "assistant_principal" && $this->assigned_sub_level === "shs";
    }

    // The Integrated School Principal: every IS level (Kindergarten to Senior High), view only.
    // Same pages as a Dean (CollegeController), by level instead of program, at /principal.
    public function isPrincipal(): bool
    {
        return $this->role === "principal" && ! $this->isShsPrincipal();
    }

    // An Assistant Principal: one each for Grade School (Preschool to Grade 6), Junior High and Senior High.
    // The same pages as the Principal (CollegeController, at /principal), only for their own levels.
    // users.assigned_sub_level says which: 'grade_school', 'jhs' or 'shs'.
    public function isAssistantPrincipal(): bool
    {
        return $this->role === "assistant_principal";
    }

    // The Principal or an Assistant Principal: the pages at /principal
    public function isPrincipalOffice(): bool
    {
        return $this->isPrincipal() || $this->isAssistantPrincipal();
    }

    // "Grade School", "Junior High" or "Senior High" for an Assistant Principal
    public function assistantLevel(): ?string
    {
        return $this->isAssistantPrincipal()
            ? ["grade_school" => "Grade School", "kindergarten" => "Grade School", "jhs" => "Junior High", "shs" => "Senior High"][$this->assigned_sub_level] ?? "Grade School"
            : null;
    }

    // How the role is shown to people: [label, office, Bootstrap icon].
    // Used by the sidebar's "Signed in as" card and the two-factor setup page.
    public function roleInfo(): array
    {
        // Registrars: one for College (and Graduate, Law, iPACE, ETEEAP), one for the Integrated School
        if ($this->role === "registrar") {
            return [$this->assigned_level === "is" ? "IS Registrar" : "College Registrar", "Registrar's Office", "bi-person-vcard"];
        }
        // Vice Presidents and Vice Chancellors: "VP for Finance", "VC for Academics"
        if ($this->isVice() && $this->department) {
            return [($this->role === "vice_chancellor" ? "VC" : "VP") . " for {$this->department}", "Executive Office", "bi-bank"];
        }
        if ($this->isAssistantPrincipal()) {
            return ["Assistant Principal", $this->assistantLevel(), "bi-building"];
        }
        if ($this->isShsPrincipal()) {
            return ["SHS Principal", "Senior High School", "bi-building"];
        }
        if ($this->isPrincipal()) {
            return ["Principal", "Integrated School", "bi-building"];
        }
        if ($this->role !== "admin" && $this->activeTempAdminGrant()) {
            return ["Temporary Admin", "IATO", "bi-hourglass-split"];
        }

        return match ($this->role) {
            "admin" => ["Super Admin", "IATO", "bi-star-fill"],
            "iato_staff" => ["IATO Staff", "IATO", "bi-person-badge"],
            // A dean's college: assigned_college once the backend adds it, the department until then (CITE by default)
            "dean" => ["Dean", $this->assigned_college ?? $this->department ?? "CITE", "bi-mortarboard"],
            "program_chair" => ["Program Chair", $this->isGradChair() ? "Graduate Programs" : "BSCS", "bi-mortarboard"],
            "registrar" => ["Registrar", "Registrar's Office", "bi-person-vcard"],
            "lamp" => ["LAMP Office", "LAMP", "bi-award"],
            "toic" => ["TOIC", "System Support", "bi-tools"],
            "director" => ["Director", "iPACE", "bi-mortarboard"],
            "president" => ["President", "Executive Office", "bi-bank"],
            "vice_president" => ["Vice President", "Executive Office", "bi-bank"],
            "chancellor" => ["Chancellor", "Executive Office", "bi-bank"],
            "vice_chancellor" => ["Vice Chancellor", "Executive Office", "bi-bank"],
            default => [ucwords(str_replace("_", " ", $this->role ?? "User")), "iARIS", "bi-person"],
        };
    }
}
