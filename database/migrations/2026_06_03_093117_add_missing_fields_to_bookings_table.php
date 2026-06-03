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
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('session_type')->nullable()->after('message');
            $table->integer('session_duration')->nullable()->after('session_type'); // in minutes
            $table->string('platform_link')->nullable()->after('session_duration');
            $table->string('booking_reference')->nullable()->unique()->after('platform_link');
            $table->decimal('amount_charged_usd', 8, 2)->nullable()->after('booking_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn([
                'session_type',
                'session_duration',
                'platform_link',
                'booking_reference',
                'amount_charged_usd'
            ]);
        });
    }
};
