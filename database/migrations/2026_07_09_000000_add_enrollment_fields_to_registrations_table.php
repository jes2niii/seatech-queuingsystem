<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->string('enrollee_type')->nullable()->after('id');
            $table->string('referral_type')->nullable()->after('enrollee_type');
            $table->string('referral_source')->nullable()->after('referral_type');
            $table->date('enrollment_date')->nullable()->after('referral_source');
            $table->string('srn')->nullable()->after('last_name');
            $table->string('application_no')->nullable()->after('srn');
        });
    }

    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->dropColumn([
                'enrollee_type',
                'referral_type',
                'referral_source',
                'enrollment_date',
                'srn',
                'application_no',
            ]);
        });
    }
};
