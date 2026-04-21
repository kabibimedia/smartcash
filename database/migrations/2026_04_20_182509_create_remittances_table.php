<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('remittances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receipt_id')->constrained()->onDelete('cascade');
            $table->decimal('amount_paid', 12, 2);
            $table->date('date_paid');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'])->default('bank_transfer');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('remittances');
    }
};
