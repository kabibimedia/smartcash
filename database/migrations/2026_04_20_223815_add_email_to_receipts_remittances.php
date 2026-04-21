<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->string('email')->nullable()->after('notes');
        });
        Schema::table('remittances', function (Blueprint $table) {
            $table->string('email')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('receipts', function (Blueprint $table) {
            $table->dropColumn('email');
        });
        Schema::table('remittances', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
