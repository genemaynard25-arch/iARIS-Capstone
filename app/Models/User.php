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

#[Fillable(["name", "email", "password", "department", "status"])]
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

    public function canAccess(string $module): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return \App\Models\RolePermission::where("role", $this->role)
            ->where("module", $module)
            ->where("enabled", true)
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
        [$first, $last, $suffix] = $this->nameParts();

        return $first === '' ? $last : trim("{$last}, {$first} {$suffix}");
    }

    // "Cherrie Villafuerte" -> "CV"; "Edmundo L. Fernandez FSC" -> "EF"
    public function initials(): string
    {
        [$first, $last] = $this->nameParts();

        return mb_strtoupper(mb_substr($first !== '' ? $first : $last, 0, 1) . ($first !== '' ? mb_substr($last, 0, 1) : ''));
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

    public function isProgramChair(): bool
    {
        return $this->role === "program_chair";
    }

    // The seeded SHS principal has role 'admin' but is assigned to IS / SHS,
    // so either the 'principal' role or the SHS assignment counts.
    public function isShsPrincipal(): bool
    {
        return $this->role === "principal" ||
            $this->assigned_sub_level === "shs";
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
        if ($this->isShsPrincipal()) {
            return ["SHS Principal", "Senior High School", "bi-building"];
        }
        if ($this->role !== "admin" && $this->activeTempAdminGrant()) {
            return ["Temporary Admin", "IATO", "bi-hourglass-split"];
        }

        return match ($this->role) {
            "admin" => ["Super Admin", "IATO", "bi-star-fill"],
            "iato_staff" => ["IATO Staff", "IATO", "bi-person-badge"],
            // A dean's college: assigned_college once the backend adds it, the department until then (CITE by default)
            "dean" => ["Dean", strtoupper($this->assigned_college ?? $this->department ?? "CITE"), "bi-mortarboard"],
            "program_chair" => ["Program Chair", "BSCS", "bi-mortarboard"],
            "registrar" => ["Registrar", "Registrar's Office", "bi-person-vcard"],
            "lamp" => ["LAMP Office", "LAMP", "bi-award"],
            "president" => ["President", "Executive Office", "bi-bank"],
            "vice_president" => ["Vice President", "Executive Office", "bi-bank"],
            "chancellor" => ["Chancellor", "Executive Office", "bi-bank"],
            "vice_chancellor" => ["Vice Chancellor", "Executive Office", "bi-bank"],
            default => [ucwords(str_replace("_", " ", $this->role ?? "User")), "iARIS", "bi-person"],
        };
    }
}
