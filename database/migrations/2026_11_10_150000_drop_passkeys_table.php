<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

// Passkeys aren't a supported login method here (Features::passkeys() was never enabled - see
// config/fortify.php). The table's own create migration is removed rather than left unused;
// this drops it for any database that already ran that migration before this change.
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('passkeys');
    }

    public function down(): void
    {
        // Intentionally no-op: passkeys aren't supported, so there's nothing to recreate.
    }
};
