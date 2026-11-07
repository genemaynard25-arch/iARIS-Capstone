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

    // President / Chancellor office: a read-only, university-wide dashboard
    public function isChancellor(): bool
    {
        return $this->role === "chancellor";
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
        if ($this->isShsPrincipal()) {
            return ["SHS Principal", "Senior High School", "bi-building"];
        }
        if ($this->role !== "admin" && $this->activeTempAdminGrant()) {
            return ["Temporary Admin", "IATO", "bi-hourglass-split"];
        }

        return match ($this->role) {
            "admin" => ["Super Admin", "IATO", "bi-star-fill"],
            "iato_staff" => ["IATO Staff", "IATO", "bi-person-badge"],
            "dean" => ["Dean", "CITE", "bi-mortarboard"],
            "program_chair" => ["Program Chair", "BSCS", "bi-mortarboard"],
            "registrar" => ["Registrar", "Registrar's Office", "bi-person-vcard"],
            "lamp" => ["LAMP Office", "LAMP", "bi-award"],
            "chancellor" => ["Chancellor", "Executive Office", "bi-bank"],
            default => [ucwords(str_replace("_", " ", $this->role ?? "User")), "iARIS", "bi-person"],
        };
    }
}
