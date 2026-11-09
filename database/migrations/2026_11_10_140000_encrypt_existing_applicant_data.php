<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

// Applicants imported before FR-09 (encrypt applicant PII at rest) are still plain text, and the
// model's 'encrypted' cast can't read them ("The payload is invalid"). This encrypts those values
// once. Values that are already encrypted are left alone, so it is safe to run on any database.
return new class extends Migration
{
    private const COLUMNS = ['first_name', 'last_name', 'gender', 'feeder_school', 'email', 'contact_number', 'address'];

    public function up(): void
    {
        DB::table('applicants')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $changes = [];
                foreach (self::COLUMNS as $column) {
                    $value = $row->$column;
                    if ($value === null || $value === '') {
                        continue;
                    }
                    try {
                        Crypt::decryptString($value);   // already encrypted
                    } catch (DecryptException) {
                        $changes[$column] = Crypt::encryptString($value);
                    }
                }
                if ($changes) {
                    DB::table('applicants')->where('id', $row->id)->update($changes);
                }
            }
        });
    }

    public function down(): void
    {
        // Leave the data encrypted: the model reads it encrypted from now on.
    }
};
