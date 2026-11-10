<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Backs the login page's "Request Access" form (App\Support\AccessRequests was the
// placeholder) and the Access Requests tab on User Accounts (Approve / Decline).
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('password'); // hashed; used to create the account on approval
            $table->string('position'); // free text chosen on the form, not a `users.role` value
            $table->string('department');
            $table->string('reason')->nullable();
            $table->enum('status', ['waiting', 'approved', 'declined'])->default('waiting');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('note')->nullable(); // shown to a declined requester
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_requests');
    }
};
