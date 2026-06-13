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
       Schema::create('tickets', function (Blueprint $table) {
        $table->id();
        $table->string('purpose');
        $table->string('prefix', 1);
        $table->unsignedInteger('number'); // 1, 2, 3, 4...
        $table->string('ticket_no')->unique(); // E0001
        $table->enum('status', ['waiting', 'serving', 'done'])->default('waiting');
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
