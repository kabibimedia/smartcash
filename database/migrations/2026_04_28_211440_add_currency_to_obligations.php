<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('obligations', function (Blueprint $table) {
            $table->enum('currency', ['GHS', 'USD', 'EUR', 'GBP', 'NGN'])->default('GHS')->after('amount_expected');
        });
    }

    public function down(): void
    {
        Schema::table('obligations', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
