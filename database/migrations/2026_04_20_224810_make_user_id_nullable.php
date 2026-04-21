<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE obligations SET user_id = NULL WHERE user_id IS NULL');
        DB::statement('UPDATE receipts SET user_id = NULL WHERE user_id IS NULL');
        DB::statement('UPDATE remittances SET user_id = NULL WHERE user_id IS NULL');
    }

    public function down(): void
    {
        //
    }
};
