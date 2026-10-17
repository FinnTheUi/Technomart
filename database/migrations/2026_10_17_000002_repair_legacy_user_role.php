<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Some early local databases were created before roles were added to the
        // original users migration. Repair those databases without deleting data.
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('tenant')->after('email');
            });
        }
    }

    public function down(): void
    {
        // Intentionally irreversible: role data must not be discarded on rollback.
    }
};
