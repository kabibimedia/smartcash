<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obligation_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_received', 12, 2);
            $table->date('date_received');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'])->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
    }
};
