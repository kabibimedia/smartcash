<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obligations', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('amount_expected', 12, 2);
            $table->decimal('amount_received', 12, 2)->default(0);
            $table->date('due_date');
            $table->enum('frequency', ['monthly', 'quarterly', 'one-time'])->default('monthly');
            $table->enum('status', ['pending', 'partially_paid', 'received', 'remitted', 'overdue'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('obligations');
    }
};
