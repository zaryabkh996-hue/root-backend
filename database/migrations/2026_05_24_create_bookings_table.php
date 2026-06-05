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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('custodian_id')->constrained('users')->onDelete('cascade');
            $table->date('booking_date');
            $table->string('booking_time'); // e.g., "12:00pm"
            $table->text('message')->nullable();
            $table->string('session_type')->nullable();
            $table->integer('session_duration')->nullable(); // in minutes
            $table->string('platform_link')->nullable();
            $table->string('booking_reference')->nullable()->unique();
            $table->decimal('amount_charged_usd', 8, 2)->nullable();
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('custodian_id');
            $table->index('booking_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
