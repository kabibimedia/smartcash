<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new columns as nullable first
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('id');
            $table->string('first_name')->nullable()->after('surname');
            $table->string('other_names')->nullable()->after('first_name');
            $table->date('date_of_birth')->nullable()->after('other_names');
        });

        // Copy existing 'name' data to new fields
        DB::table('users')->get()->each(function ($user) {
            $nameParts = explode(' ', $user->name, 2);
            DB::table('users')->where('id', $user->id)->update([
                'surname' => $nameParts[0] ?? 'Unknown',
                'first_name' => $nameParts[1] ?? ($nameParts[0] ?? 'User'),
            ]);
        });

        // Make required fields NOT NULL and drop old 'name' column
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable(false)->change();
            $table->string('first_name')->nullable(false)->change();
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add 'name' column back
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->after('id');
        });

        // Combine surname and first_name back to name
        DB::table('users')->get()->each(function ($user) {
            $name = trim($user->surname . ' ' . $user->first_name);
            DB::table('users')->where('id', $user->id)->update([
                'name' => $name ?: 'Unknown User',
            ]);
        });

        // Drop new columns
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['surname', 'first_name', 'other_names', 'date_of_birth']);
        });
    }
};
